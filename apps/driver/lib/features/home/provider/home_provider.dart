import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/pusher_service.dart';
import '../../../core/services/location_service.dart';
import '../../auth/model/driver_model.dart';
import '../../order_request/model/order_request_model.dart';
import '../model/document_alert.dart';
import '../repository/home_repository.dart';

class HomeProvider extends ChangeNotifier {
  final HomeRepository _repository;
  final LocationService _location;
  final PusherService _pusher;

  HomeProvider(this._repository, this._location, this._pusher);

  bool isOnline = false;
  bool togglingOnline = false;
  bool loading = false;
  String? error;

  Position? currentPosition;
  DriverModel? driver;

  String todayEarning = '0.00';
  int todayTrips = 0;

  List<String> blockReasons = [];
  List<DocumentAlert> expiringDocs = [];
  String dueAmount = '0.00';
  bool dueExceeded = false;
  double dueLimit = 0;

  ActiveOrderRef? activeOrderRef;
  bool get hasActiveOrder => activeOrderRef != null;

  // Wired from the UI to avoid provider-to-provider coupling.
  void Function(OrderRequestModel request)? onOrderRequest;
  void Function(int? orderId)? onOrderCancelled;

  Future<void> loadDriverData() async {
    loading = true;
    notifyListeners();
    try {
      final data = await _repository.loadHomeData();
      driver = data.driver;
      isOnline = data.driver?.isOnline ?? false;
      todayEarning = data.todayEarning;
      todayTrips = data.todayTrips;
      dueAmount = data.dueAmount;
      dueExceeded = data.dueExceeded;
      dueLimit = data.dueLimit;
      expiringDocs = data.expiringDocs;
      error = null;
      if (isOnline) {
        // The driver is already online per the backend, but the presence/zone
        // is only registered when the Online button posts the current location
        // to /toggle-online. On a fresh launch that never happens, so the
        // matching engine has no fresh location and dispatches no requests
        // until the driver manually toggles. Run the exact same activation the
        // Online button performs so ride requests start flowing immediately.
        await _activateOnline();
      } else {
        // Even offline, seed a one-shot location so the map centers on the
        // driver instead of the fallback (Dhaka) coordinates.
        await _seedCurrentLocation();
      }
    } on ApiException catch (e) {
      error = e.message;
    }
    loading = false;
    notifyListeners();
  }

  /// Replace the cached driver after it was updated elsewhere (e.g. the Edit
  /// Profile screen, which uses [ProfileProvider]). Keeps the drawer/header in
  /// sync without the side effects of a full [loadDriverData] reload.
  void setDriver(DriverModel? updated) {
    if (updated == null) return;
    driver = updated;
    // Keep the due banner in sync too — a recharge clears/reduces the due
    // without going through a full [loadDriverData], so refresh these derived
    // fields from the new driver instead of leaving them stale.
    dueAmount = updated.dueAmount;
    final due = double.tryParse(updated.dueAmount) ?? 0;
    dueExceeded = dueLimit > 0 && due >= dueLimit;
    notifyListeners();
  }

  /// Returns true if the toggle succeeded. On failure (e.g. blocked), the
  /// caller should show [blockReasons].
  Future<bool> toggleOnline(bool online) async {
    togglingOnline = true;
    error = null;
    notifyListeners();

    bool ok;
    if (online) {
      ok = await _activateOnline();
    } else {
      ok = await _deactivateOnline();
    }

    togglingOnline = false;
    notifyListeners();
    return ok;
  }

  /// Full "go online" activation — the single source of truth for everything the
  /// Online button does: request location permission, push the current location
  /// to the backend (which resolves the driver's zone / presence), then start
  /// location tracking and subscribe to the realtime order-request channel.
  ///
  /// Reused by [loadDriverData] on startup so an already-online driver receives
  /// ride requests without toggling. Idempotent: location tracking re-subscribes
  /// cleanly, Pusher init is guarded, and [_subscribeToOrderRequests] skips a
  /// duplicate subscription — so calling this more than once is safe. Returns
  /// false (with [blockReasons] set) when permission is denied or the backend
  /// blocks the driver, mirroring the button's behavior exactly.
  Future<bool> _activateOnline() async {
    final granted = await _location.requestPermissions();
    if (!granted) {
      blockReasons = ['Location permission is required to go online.'];
      return false;
    }

    // Send current location so the backend can auto-resolve the zone.
    double? lat;
    double? lng;
    try {
      final pos = await _location.getCurrentLocation();
      currentPosition = pos;
      lat = pos.latitude;
      lng = pos.longitude;
    } catch (_) {}

    final result = await _repository.toggleOnline(true, lat: lat, lng: lng);
    if (!result.success) {
      blockReasons = result.blockReasons;
      return false;
    }

    isOnline = result.isOnline;
    blockReasons = [];
    if (isOnline) {
      await _startLocationTracking();
      await _subscribeToOrderRequests();
    }
    return true;
  }

  Future<bool> _deactivateOnline() async {
    final result = await _repository.toggleOnline(false);
    if (!result.success) {
      blockReasons = result.blockReasons;
      return false;
    }
    isOnline = result.isOnline;
    blockReasons = [];
    await _stopLocationTracking();
    await _unsubscribeOrderRequests();
    return true;
  }

  /// One-shot fetch of the driver's current location (no background tracking).
  /// Used to seed the map when offline so the driver sees themselves on the
  /// map without having to go online first. Safe to call repeatedly — silently
  /// skips if permission is denied.
  Future<void> _seedCurrentLocation() async {
    try {
      final granted = await _location.requestPermissions();
      if (!granted) return;
      currentPosition = await _location.getCurrentLocation();
      notifyListeners();
    } catch (_) {
      // Best-effort; map will fall back to its default center.
    }
  }

  Future<void> _startLocationTracking() async {
    // Idle frequency until a trip starts.
    await _location.setUpdateInterval(AppConstants.locationIntervalIdle);
    await _location.startBackgroundTracking(onLocation: (pos) {
      currentPosition = pos;
      _repository.updateLocation(
        lat: pos.latitude,
        lng: pos.longitude,
        heading: pos.heading,
        speed: pos.speed,
      );
      notifyListeners();
    });
    // Seed an immediate position so the map centers right away.
    try {
      currentPosition = await _location.getCurrentLocation();
      notifyListeners();
    } catch (_) {}
  }

  Future<void> _stopLocationTracking() async {
    await _location.stopBackgroundTracking();
  }

  // Driver id currently subscribed to the realtime channel, or null when not
  // subscribed. Guards against duplicate Pusher subscriptions when the online
  // activation runs more than once (startup + lifecycle resume).
  int? _subscribedDriverId;

  Future<void> _subscribeToOrderRequests() async {
    final id = driver?.id;
    if (id == null) return;
    if (_subscribedDriverId == id) return; // already subscribed — idempotent
    try {
      final ready = await _pusher.init();
      if (!ready) return; // no pusher key configured — FCM is the backup
      await _pusher.subscribeToDriverChannel(
        id,
        onNewOrderRequest: (data) {
          final req = OrderRequestModel.fromJson(data.cast<String, dynamic>());
          onOrderRequest?.call(req);
        },
        onOrderRequestCancelled: (data) =>
            onOrderCancelled?.call(_extractOrderId(data)),
      );
      _subscribedDriverId = id;
    } catch (_) {
      // Realtime is non-fatal; FCM remains the backup channel.
    }
  }

  Future<void> _unsubscribeOrderRequests() async {
    final id = driver?.id;
    _subscribedDriverId = null;
    if (id == null) return;
    try {
      await _pusher.unsubscribeDriverChannel(id);
    } catch (_) {}
  }

  /// Re-subscribe after the app returns to foreground (lifecycle). The socket
  /// may have dropped while backgrounded, so clear the guard to force a fresh
  /// subscription rather than skipping it as a duplicate.
  Future<void> reconnectRealtime() async {
    if (!isOnline) return;
    _subscribedDriverId = null;
    await _subscribeToOrderRequests();
  }

  int? _extractOrderId(Map data) {
    final raw = data['order_id'] ?? data['id'];
    return raw is int ? raw : int.tryParse(raw?.toString() ?? '');
  }

  Future<void> checkActiveOrder() async {
    activeOrderRef = await _repository.activeOrder();
    notifyListeners();
  }

  /// Switch location cadence when a trip starts/ends.
  Future<void> setTripActive(bool active) async {
    await _location.setUpdateInterval(active
        ? AppConstants.locationIntervalActive
        : AppConstants.locationIntervalIdle);
  }

  /// Clears all cached driver state and stops realtime work. Called when the
  /// session ends (logout / account deletion) so the next sign-in starts from
  /// a clean slate instead of briefly showing the previous driver's data.
  Future<void> reset() async {
    await _stopLocationTracking();
    await _unsubscribeOrderRequests();
    driver = null;
    isOnline = false;
    togglingOnline = false;
    loading = false;
    error = null;
    currentPosition = null;
    todayEarning = '0.00';
    todayTrips = 0;
    blockReasons = [];
    expiringDocs = [];
    dueAmount = '0.00';
    dueExceeded = false;
    dueLimit = 0;
    activeOrderRef = null;
    notifyListeners();
  }

  @override
  void dispose() {
    _stopLocationTracking();
    super.dispose();
  }
}
