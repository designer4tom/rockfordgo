import 'dart:async';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/models/place_info.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/pusher_service.dart';
import '../../../core/services/location_service.dart';
import '../model/active_parcel_model.dart';
import '../repository/parcel_order_repository.dart';

/// Sequential parcel status flow.
class ParcelStatus {
  static const accepted = 'accepted';
  static const goToPickup = 'go_to_pickup';
  static const confirmArrival = 'confirm_arrival';
  static const pickedUp = 'picked_up';
  static const startRide = 'start_ride';
  static const droppedOff = 'dropped_off';
  static const completed = 'completed';
  static const cancelled = 'cancelled';

  /// Any backend status that means the parcel is no longer active for the driver.
  static bool isCancelled(String? status) {
    final s = (status ?? '').toLowerCase();
    return s.contains('cancel') || s == 'rejected';
  }
}

class ParcelOrderProvider extends ChangeNotifier {
  final ParcelOrderRepository _repository;
  final LocationService _location;
  final PusherService _pusher;

  ParcelOrderProvider(this._repository, this._location, this._pusher);

  ActiveParcelModel? activeParcel;
  bool loading = false;
  bool updating = false;
  bool codCollected = false;
  bool proofCollected = false;
  bool cancelledByCustomer = false;
  String? error;
  int? _subscribedOrderId;
  Timer? _statusPoll;

  /// How often we re-check the order with the backend as a safety net in case
  /// the realtime cancel event is missed.
  static const _pollInterval = Duration(seconds: 12);


  // Proof is held locally until completion — the backend collects it as part of
  // the /driver/parcel/complete call (no separate proof endpoint exists).
  String? _proofType;
  String? _proofOtp;
  String? _proofSignature;
  File? _proofPhoto;

  String get currentStatus => activeParcel?.status ?? ParcelStatus.accepted;

  Future<void> loadActiveOrder(int orderId) async {
    loading = true;
    notifyListeners();
    try {
      activeParcel = await _repository.loadActiveOrder(orderId);
      if (ParcelStatus.isCancelled(activeParcel?.status)) {
        cancelledByCustomer = true;
      }
      _location.setUpdateInterval(AppConstants.locationIntervalActive);
      _subscribeToOrder(orderId);
      _startStatusPolling(orderId);
      error = null;
    } on ApiException catch (e) {
      error = e.message;
    }
    loading = false;
    notifyListeners();
  }

  void _subscribeToOrder(int orderId) {
    _subscribedOrderId = orderId;
    _pusher.subscribeToOrder(
      orderId,
      onStatusUpdated: (data) {
        if (ParcelStatus.isCancelled(data['status']?.toString())) {
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
  /// out of the delivery screen.
  void _startStatusPolling(int orderId) {
    _statusPoll?.cancel();
    _statusPoll = Timer.periodic(_pollInterval, (_) async {
      if (cancelledByCustomer) return;
      try {
        final latest = await _repository.loadActiveOrder(orderId);
        if (ParcelStatus.isCancelled(latest?.status)) _markCancelled();
      } catch (_) {
        // Best-effort; ignore transient errors and try again next tick.
      }
    });
  }

  void _stopStatusPolling() {
    _statusPoll?.cancel();
    _statusPoll = null;
  }

  Future<bool> updateStatus(String status) async {
    final parcel = activeParcel;
    if (parcel == null) return false;
    updating = true;
    error = null;
    notifyListeners();
    try {
      final ok = await _repository.updateStatus(parcel.orderId, status);
      if (ok) activeParcel = parcel.copyWith(status: status);
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

  /// Stores the collected proof locally; it's submitted with completeDelivery().
  /// Returns false if the chosen proof type has no value yet.
  Future<bool> collectProof({
    required String type,
    String? otp,
    File? photo,
    String? signature,
  }) async {
    if (activeParcel == null) return false;
    final hasValue = switch (type) {
      'photo' => photo != null,
      'signature' => signature != null && signature.isNotEmpty,
      _ => otp != null && otp.isNotEmpty,
    };
    if (!hasValue) {
      error = 'Please provide the delivery proof.';
      notifyListeners();
      return false;
    }
    _proofType = type;
    _proofOtp = otp;
    _proofPhoto = photo;
    _proofSignature = signature;
    proofCollected = true;
    error = null;
    notifyListeners();
    return true;
  }

  Future<bool> collectCod(double amount) async {
    final parcel = activeParcel;
    if (parcel == null) return false;
    try {
      final ok = await _repository.collectCod(parcel.orderId, amount);
      if (ok) codCollected = true;
      notifyListeners();
      return ok;
    } on ApiException catch (e) {
      error = e.message;
      notifyListeners();
      return false;
    }
  }

  Future<bool> completeDelivery() async {
    final parcel = activeParcel;
    if (parcel == null) return false;
    updating = true;
    notifyListeners();
    try {
      final ok = await _repository.complete(
        orderId: parcel.orderId,
        proofType: _proofType,
        otp: _proofOtp,
        photo: _proofPhoto,
        signature: _proofSignature,
      );
      if (ok) {
        activeParcel = parcel.copyWith(status: ParcelStatus.completed);
        await _location.setUpdateInterval(AppConstants.locationIntervalIdle);
      }
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

  bool get _beforePickup =>
      currentStatus == ParcelStatus.accepted ||
      currentStatus == ParcelStatus.goToPickup ||
      currentStatus == ParcelStatus.confirmArrival;

  Future<void> openNavigation() async {
    final parcel = activeParcel;
    if (parcel == null) return;
    await _launchMaps(_beforePickup ? parcel.pickup : parcel.drop);
  }

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

  Future<void> callSender() => _call(activeParcel?.sender.phone);
  Future<void> callReceiver() => _call(activeParcel?.receiver.phone);

  Future<void> _call(String? phone) async {
    if (phone == null || phone.isEmpty) return;
    final uri = Uri.parse('tel:$phone');
    if (await canLaunchUrl(uri)) await launchUrl(uri);
  }

  void clear() {
    _unsubscribe();
    _stopStatusPolling();
    activeParcel = null;
    codCollected = false;
    proofCollected = false;
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
