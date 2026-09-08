import 'dart:async';

import 'package:audioplayers/audioplayers.dart';
import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../../ride_order/model/active_ride_model.dart';
import '../model/order_request_model.dart';
import '../repository/order_request_repository.dart';

class OrderRequestProvider extends ChangeNotifier {
  final OrderRequestRepository _repository;
  OrderRequestProvider(this._repository);

  OrderRequestModel? currentRequest;

  /// The ride parsed from the most recent accept response — carries the
  /// authoritative fare breakdown to seed the ride screen.
  ActiveRideModel? acceptedRide;
  int remainingSeconds = 0;
  bool processing = false; // accept/reject in flight
  bool lastExpired = false; // FCM-opened request already timed out
  String? error;

  Timer? _countdownTimer;
  AudioPlayer? _player;

  // Order ids already surfaced this session — prevents the same order being
  // shown twice when Pusher and FCM both deliver it.
  final Set<int> _handledOrderIds = {};

  bool get hasRequest => currentRequest != null;

  bool _alreadyHandled(int orderId) => _handledOrderIds.contains(orderId);
  void _markHandled(int orderId) => _handledOrderIds.add(orderId);

  /// Show a freshly received request (Pusher foreground path).
  void showRequest(OrderRequestModel request) {
    // Skip if already shown (current popup or earlier via FCM/Pusher).
    if (currentRequest?.orderId == request.orderId) return;
    if (_alreadyHandled(request.orderId)) return;
    _markHandled(request.orderId);
    currentRequest = request;
    remainingSeconds = request.timeoutSeconds;
    lastExpired = false;
    error = null;
    _startCountdown();
    _playSound();
    notifyListeners();
  }

  /// Dismiss the popup if it matches a cancelled order (another driver took it
  /// or the customer cancelled).
  void dismissIfMatches(dynamic orderId) {
    final id = orderId is int
        ? orderId
        : int.tryParse(orderId?.toString() ?? '');
    if (id != null && currentRequest?.orderId == id) {
      dismiss();
    }
  }

  /// FCM background/terminated tap path — build the popup straight from the
  /// notification's data payload. The order is only *offered* (no driver_id yet)
  /// so we must NOT hit the order-detail endpoint, which would 404. Everything
  /// needed is already in [data]. [elapsedSeconds] = seconds since it was sent.
  OrderRequestModel? openFromData(
    Map<String, dynamic> data, {
    int elapsedSeconds = 0,
  }) {
    final req = OrderRequestModel.fromFcmData(data);
    if (req == null) return null;
    // Pusher may have already surfaced this order in the foreground.
    if (currentRequest?.orderId == req.orderId ||
        _alreadyHandled(req.orderId)) {
      return currentRequest;
    }
    final remaining = req.timeoutSeconds - elapsedSeconds;
    if (remaining <= 0) {
      lastExpired = true;
      currentRequest = null;
      notifyListeners();
      return null;
    }
    _markHandled(req.orderId);
    currentRequest = req;
    remainingSeconds = remaining;
    lastExpired = false;
    error = null;
    _startCountdown();
    _playSound();
    notifyListeners();
    return req;
  }

  /// FCM fallback path: resolve order id → details, respecting elapsed time.
  /// Only usable once the order is assigned to this driver. [elapsedSeconds] =
  /// seconds since the request was originally sent.
  Future<OrderRequestModel?> openFromNotification(
    int orderId, {
    int elapsedSeconds = 0,
  }) async {
    // Pusher may have already surfaced this order in the foreground.
    if (currentRequest?.orderId == orderId || _alreadyHandled(orderId)) {
      return currentRequest;
    }
    final req = await _repository.fetchRequest(orderId);
    if (req == null) {
      lastExpired = true;
      notifyListeners();
      return null;
    }
    final remaining = req.timeoutSeconds - elapsedSeconds;
    if (remaining <= 0) {
      lastExpired = true;
      currentRequest = null;
      notifyListeners();
      return null;
    }
    _markHandled(orderId);
    currentRequest = req;
    remainingSeconds = remaining;
    lastExpired = false;
    _startCountdown();
    _playSound();
    notifyListeners();
    return req;
  }

  void _startCountdown() {
    _countdownTimer?.cancel();
    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (remainingSeconds <= 1) {
        _timeout();
      } else {
        remainingSeconds--;
        notifyListeners();
      }
    });
  }

  Future<void> _playSound() async {
    try {
      _player ??= AudioPlayer();
      await _player!.setReleaseMode(ReleaseMode.loop);
      await _player!.play(AssetSource('sounds/order_request.wav'));
    } catch (_) {
      // Sound is best-effort; never block the popup on audio errors.
    }
  }

  Future<void> _stopSound() async {
    try {
      await _player?.stop();
    } catch (_) {}
  }

  Future<bool> acceptOrder() async {
    final req = currentRequest;
    if (req == null) return false;
    processing = true;
    error = null;
    notifyListeners();
    try {
      final ride = await _repository.accept(req.orderId);
      processing = false;
      final ok = ride != null;
      if (ok) {
        acceptedRide = ride;
        _stopTimers();
        await _stopSound();
      }
      notifyListeners();
      return ok;
    } on ApiException catch (e) {
      error = e.message;
      processing = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> rejectOrder() async {
    final req = currentRequest;
    _stopTimers();
    await _stopSound();
    if (req != null) {
      try {
        await _repository.reject(req.orderId);
      } catch (_) {
        // Reject is fire-and-forget from the UI's perspective.
      }
    }
    dismiss();
  }

  void _timeout() {
    lastExpired = true;
    _stopTimers();
    _stopSound();
    currentRequest = null;
    remainingSeconds = 0;
    notifyListeners();
  }

  void dismiss() {
    _stopTimers();
    _stopSound();
    currentRequest = null;
    remainingSeconds = 0;
    notifyListeners();
  }

  void _stopTimers() {
    _countdownTimer?.cancel();
    _countdownTimer = null;
  }

  @override
  void dispose() {
    _stopTimers();
    _player?.dispose();
    super.dispose();
  }
}
