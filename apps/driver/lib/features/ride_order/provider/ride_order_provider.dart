import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/models/place_info.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/pusher_service.dart';
import '../../../core/services/location_service.dart';
import '../model/active_ride_model.dart';
import '../repository/ride_order_repository.dart';

/// Sequential ride status flow.
class RideStatus {
  static const accepted = 'accepted';
  static const goToPickup = 'go_to_pickup';
  static const confirmArrival = 'confirm_arrival';
  static const pickedUp = 'picked_up';
  static const startRide = 'start_ride';
  static const droppedOff = 'dropped_off';
  static const completed = 'completed';
  static const cancelled = 'cancelled';

  /// Any backend status that means the ride is no longer active for the driver.
  static bool isCancelled(String? status) {
    final s = (status ?? '').toLowerCase();
    return s.contains('cancel') || s == 'rejected';
  }
}

class RideOrderProvider extends ChangeNotifier {
  final RideOrderRepository _repository;
  final LocationService _location;
  final PusherService _pusher;

  RideOrderProvider(this._repository, this._location, this._pusher);

  ActiveRideModel? activeRide;
  bool loading = false;
  bool updating = false;
  bool cancelledByCustomer = false;
  String? error;
  int? _subscribedOrderId;
  Timer? _statusPoll;

  /// Authoritative fare from the accept response. The fare is fixed for the
  /// trip, so we keep applying it over subsequent order-detail reloads (which
  /// may omit the full breakdown).
  FareInfo? _acceptedFare;

  /// Seed the fare captured from the accept response so the ride screen shows
  /// the exact backend figures. Ignores an all-zero fare so an older backend
  /// (no fare object) leaves the order-detail values untouched.
  void seedAcceptedFare(FareInfo fare) {
    if (fare.isEmpty) return;
    _acceptedFare = fare;
    final ride = activeRide;
    if (ride != null) {
      activeRide = ride.copyWith(fare: fare);
      notifyListeners();
    }
  }

  /// How often we re-check the order with the backend as a safety net in case
  /// the realtime cancel event is missed.
  static const _pollInterval = Duration(seconds: 12);

  String get currentStatus => activeRide?.status ?? RideStatus.accepted;

  Future<void> loadActiveOrder(int orderId) async {
    loading = true;
    notifyListeners();
    try {
      activeRide = await _repository.loadActiveOrder(orderId);
      // Preserve the authoritative fare from the accept response when the
      // order-detail payload omits the full breakdown.
      if (activeRide != null && _acceptedFare != null) {
        activeRide = activeRide!.copyWith(fare: _acceptedFare);
      }
      if (RideStatus.isCancelled(activeRide?.status)) {
        cancelledByCustomer = true;
      }
      startHighFrequencyTracking();
      _subscribeToOrder(orderId);
      _startStatusPolling(orderId);
      error = null;
    } on ApiException catch (e) {
      error = e.message;
    }
    loading = false;
    notifyListeners();
  }

  /// Seed from an already-known model (e.g. just accepted).
  void seed(ActiveRideModel ride) {
    activeRide = ride;
    startHighFrequencyTracking();
    _subscribeToOrder(ride.orderId);
    _startStatusPolling(ride.orderId);
    notifyListeners();
  }

  void _subscribeToOrder(int orderId) {
    _subscribedOrderId = orderId;
    _pusher.subscribeToOrder(
      orderId,
      onStatusUpdated: (data) {
        // A status update can itself carry a cancelled state.
        if (RideStatus.isCancelled(data['status']?.toString())) {
          _markCancelled();
        }
      },
      onCancelled: (_) => _markCancelled(),
    );
  }

  void _markCancelled() {
    if (cancelledByCustomer) return;
    cancelledByCustomer = true;
    _stopStatusPolling();
    notifyListeners();
  }

  /// Safety net: even if the realtime cancel event never arrives, periodically
  /// re-check the order so a customer/admin cancellation still pulls the driver
  /// out of the ride screen.
  void _startStatusPolling(int orderId) {
    _statusPoll?.cancel();
    _statusPoll = Timer.periodic(_pollInterval, (_) async {
      if (cancelledByCustomer) return;
      try {
        final latest = await _repository.loadActiveOrder(orderId);
        if (RideStatus.isCancelled(latest?.status)) _markCancelled();
      } catch (_) {
        // Best-effort; ignore transient errors and try again next tick.
      }
    });
  }

  void _stopStatusPolling() {
    _statusPoll?.cancel();
    _statusPoll = null;
  }

  Future<bool> updateStatus(String status, {String? otp}) async {
    final ride = activeRide;
    if (ride == null) return false;
    updating = true;
    error = null;
    notifyListeners();
    try {
      final ok = await _repository.updateStatus(ride.orderId, status, otp: otp);
      if (ok) activeRide = ride.copyWith(status: status);
      updating = false;
      notifyListeners();
      return ok;
    } on ApiException catch (e) {
      error = e.message;
      updating = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> verifyOtpAndPickup(String otp) =>
      updateStatus(RideStatus.pickedUp, otp: otp);

  Future<bool> completeRide() async {
    final ok = await updateStatus(RideStatus.completed);
    if (ok) {
      // Trip over → drop back to idle location cadence.
      await _location.setUpdateInterval(AppConstants.locationIntervalIdle);
    }
    return ok;
  }

  Future<bool> rateCustomer(int rating, String? comment) async {
    final ride = activeRide;
    if (ride == null) return false;
    try {
      return await _repository.rate(ride.orderId, rating, comment);
    } on ApiException catch (e) {
      error = e.message;
      notifyListeners();
      return false;
    }
  }

  Future<void> openNavigation() async {
    final ride = activeRide;
    if (ride == null) return;
    // Target depends on current step.
    final target = _isBeforePickup ? ride.pickup : ride.drop;
    await _launchMaps(target);
  }

  bool get _isBeforePickup =>
      currentStatus == RideStatus.accepted ||
      currentStatus == RideStatus.goToPickup ||
      currentStatus == RideStatus.confirmArrival;

  Future<void> _launchMaps(PlaceInfo place) async {
    final uri = Uri.parse('google.navigation:q=${place.lat},${place.lng}');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      final web = Uri.parse(
          'https://www.google.com/maps/dir/?api=1&destination=${place.lat},${place.lng}');
      await launchUrl(web, mode: LaunchMode.externalApplication);
    }
  }

  Future<void> callCustomer() async {
    final phone = activeRide?.customer.phone;
    if (phone == null || phone.isEmpty) return;
    // Launch the dialer directly. `canLaunchUrl` for `tel:` returns false on
    // Android 11+ when the dialer isn't declared in <queries>, which silently
    // suppressed the call — so don't gate on it; just try and ignore failures.
    final uri = Uri.parse('tel:$phone');
    try {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      // No dialer available — nothing more we can do.
    }
  }

  void startHighFrequencyTracking() {
    _location.setUpdateInterval(AppConstants.locationIntervalActive);
  }

  void clear() {
    _unsubscribe();
    _stopStatusPolling();
    activeRide = null;
    _acceptedFare = null;
    cancelledByCustomer = false;
    notifyListeners();
  }

  void _unsubscribe() {
    final id = _subscribedOrderId;
    if (id != null) {
      _pusher.unsubscribeFromOrder(id);
      _subscribedOrderId = null;
    }
  }

  @override
  void dispose() {
    _unsubscribe();
    _stopStatusPolling();
    super.dispose();
  }
}
