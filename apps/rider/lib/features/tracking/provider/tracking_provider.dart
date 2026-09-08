import 'dart:async';

import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/network/pusher_service.dart';
import '../../ride/model/ride_status_model.dart';
import '../model/active_order_model.dart';
import '../model/tracking_model.dart';
import '../repository/tracking_repository.dart';

class TrackingProvider extends ChangeNotifier {
  final TrackingRepository _repository;
  final PusherService _pusher;

  TrackingProvider(this._repository, this._pusher);

  TrackingModel? tracking;
  String orderType = 'ride'; // ride/parcel
  int? orderId;

  LatLng? driverPosition;
  double driverBearing = 0;
  String? error;

  // Pusher gives instant updates; this poll always runs as a safety net.
  Timer? _fallbackPoll;

  // Throttle map marker renders to avoid rebuilding on every packet.
  DateTime? _lastLocationRender;
  static const _renderInterval = Duration(seconds: 1);

  /// Check for an in-progress order to resume into after an app restart.
  /// Returns null when there's none (or on error/offline).
  Future<ActiveOrderModel?> checkActiveOrder() async {
    try {
      final active = await _repository.getActiveOrder();
      return active.hasActive ? active : null;
    } on ApiException {
      return null;
    }
  }

  // ---- Init ----
  Future<void> initTracking(int orderId, String type) async {
    this.orderId = orderId;
    orderType = type;
    await refreshStatus();
    await _subscribe(orderId);
    _startStatusPolling();
  }

  Future<void> _subscribe(int orderId) async {
    await _pusher.subscribeToOrder(
      orderId,
      onDriverAccepted: (data) {
        if (data['driver'] != null) {
          tracking = (tracking ?? TrackingModel(status: 'accepted')).copyWith(
            status: 'accepted',
            driver: DriverInfo.fromJson(
                Map<String, dynamic>.from(data['driver'])),
          );
          notifyListeners();
        }
      },
      onLocationUpdated: (data) => _updateDriverPosition(
        _toDouble(data['lat']),
        _toDouble(data['lng']),
        _toDouble(data['bearing']),
      ),
      onStatusUpdated: (data) {
        final status = data['status'] as String?;
        if (status != null) {
          tracking = (tracking ?? TrackingModel(status: status))
              .copyWith(status: status);
          notifyListeners();
        }
      },
      onCompleted: (data) {
        tracking = (tracking ?? TrackingModel(status: 'completed'))
            .copyWith(status: 'completed');
        notifyListeners();
      },
      onCancelled: (data) {
        tracking = (tracking ?? TrackingModel(status: 'cancelled'))
            .copyWith(status: 'cancelled');
        notifyListeners();
      },
    );
  }

  // ---- Map throttle ----
  void _updateDriverPosition(double? lat, double? lng, double? bearing) {
    if (lat == null || lng == null) return;
    driverPosition = LatLng(lat, lng);
    if (bearing != null) driverBearing = bearing;
    tracking = tracking?.copyWith(driverLat: lat, driverLng: lng);

    // Render at most once per [_renderInterval]; the map widget animates
    // smoothly between the rendered points so movement stays fluid.
    final now = DateTime.now();
    if (_lastLocationRender == null ||
        now.difference(_lastLocationRender!) >= _renderInterval) {
      _lastLocationRender = now;
      notifyListeners();
    }
  }

  // ---- Status polling safety net ----
  // Pusher delivers instant updates, but a private-channel subscription can
  // silently fail to receive events (e.g. broadcasting-auth issues) even while
  // the socket reports CONNECTED. So we always keep a low-frequency poll
  // running as a safety net; it's cheap and guarantees the trip state and
  // driver details keep updating as the driver progresses.
  void _startStatusPolling() {
    if (_fallbackPoll != null) return; // already polling
    _fallbackPoll = Timer.periodic(
      const Duration(seconds: 5),
      (_) => refreshStatus(),
    );
  }

  void _stopStatusPolling() {
    _fallbackPoll?.cancel();
    _fallbackPoll = null;
  }

  Future<void> refreshStatus() async {
    final id = orderId;
    if (id == null) return;
    try {
      tracking = await _repository.getStatus(id, orderType);
      final t = tracking;
      if (t?.driverLat != null && t?.driverLng != null) {
        driverPosition = LatLng(t!.driverLat!, t.driverLng!);
        if (t.driverBearing != null) driverBearing = t.driverBearing!;
      }
      notifyListeners();
    } on ApiException catch (e) {
      error = e.message;
    }
  }

  // ---- Actions ----
  Future<void> callDriver() async {
    final phone = tracking?.driver?.phone;
    if (phone == null || phone.isEmpty) return;
    final sanitized = phone.replaceAll(RegExp(r'[^0-9+]'), '');
    final uri = Uri(scheme: 'tel', path: sanitized);
    try {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      // No dialer available — silently ignore.
    }
  }

  Future<String> shareTrip() async {
    final id = orderId;
    if (id == null) return '';
    String link = '';
    try {
      link = await _repository.generateShareLink(id);
    } on ApiException catch (e) {
      error = e.message;
    } catch (e) {
      error = e.toString();
    }
    final text = link.isNotEmpty
        ? 'tracking.share_trip'.tr(namedArgs: {'link': link})
        : 'tracking.share_trip'.tr(namedArgs: {'link': ''});
    try {
      await SharePlus.instance.share(ShareParams(text: text));
    } catch (e) {
      error = e.toString();
    }
    return link;
  }

  Future<void> triggerSos() async {
    final id = orderId;
    if (id == null) return;
    try {
      await _repository.triggerSos(
        id,
        driverPosition?.latitude,
        driverPosition?.longitude,
      );
    } on ApiException catch (e) {
      error = e.message;
    }
  }

  Future<bool> cancelOrder(String reason) async {
    final id = orderId;
    if (id == null) return false;
    try {
      await _repository.cancelOrder(id, orderType, reason);
      return true;
    } on ApiException catch (e) {
      error = e.message;
      notifyListeners();
      return false;
    }
  }

  Future<void> addTip(double amount) async {
    final id = orderId;
    if (id == null) return;
    try {
      await _repository.addTip(id, orderType, amount);
    } on ApiException catch (e) {
      error = e.message;
    }
  }

  Future<bool> rate(int rating, String? comment, List<String> tags) async {
    final id = orderId;
    if (id == null) return false;
    try {
      await _repository.rate(id, orderType, rating, comment, tags);
      return true;
    } on ApiException catch (e) {
      error = e.message;
      return false;
    }
  }

  void stopTracking() {
    _stopStatusPolling();
    final id = orderId;
    if (id != null) _pusher.unsubscribeFromOrder(id);
  }

  static double? _toDouble(dynamic value) {
    if (value == null) return null;
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value);
    return null;
  }

  @override
  void dispose() {
    stopTracking();
    super.dispose();
  }
}
