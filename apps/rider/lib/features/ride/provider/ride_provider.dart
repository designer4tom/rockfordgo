import 'dart:async';

import 'package:flutter/material.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/network/pusher_service.dart';
import '../../location/model/place_model.dart';
import '../model/coupon_model.dart';
import '../model/fare_estimate_model.dart';
import '../model/ride_booking_model.dart';
import '../model/ride_status_model.dart';
import '../repository/ride_repository.dart';

class RideProvider extends ChangeNotifier {
  final RideRepository _repository;
  final PusherService _pusher;

  RideProvider(this._repository, this._pusher);

  // Booking state
  PlaceModel? pickup;
  PlaceModel? drop;
  List<PlaceModel> stops = [];
  int? selectedCategoryId;
  List<FareEstimateModel> fareEstimates = [];
  String paymentMethod = 'cash';
  CouponModel? appliedCoupon;
  DateTime? scheduledAt;
  bool rideShare = false;

  // Active ride
  RideBookingModel? activeBooking;
  RideStatusModel? rideStatus;
  Timer? _statusPollTimer; // status polling during the searching phase

  bool isLoading = false;
  bool isFareLoading = false;
  String? error;

  // ---- Trip setup ----
  void setTrip(PlaceModel pickup, PlaceModel drop) {
    this.pickup = pickup;
    this.drop = drop;
    fareEstimates = [];
    selectedCategoryId = null;
    notifyListeners();
  }

  FareEstimateModel? get selectedFare {
    if (selectedCategoryId == null) return null;
    for (final f in fareEstimates) {
      if (f.vehicleCategoryId == selectedCategoryId) return f;
    }
    return null;
  }

  FareEstimateModel? fareForCategory(int categoryId) {
    for (final f in fareEstimates) {
      if (f.vehicleCategoryId == categoryId) return f;
    }
    return null;
  }

  double get couponDiscount {
    final coupon = appliedCoupon;
    final fare = selectedFare;
    if (coupon == null || fare == null) return 0;
    final base = double.tryParse(fare.finalFare) ?? 0;
    return coupon.discountOn(base);
  }

  double get payableAmount {
    final fare = selectedFare;
    if (fare == null) return 0;
    final base = double.tryParse(fare.finalFare) ?? 0;
    final total = base - couponDiscount;
    return total < 0 ? 0 : total;
  }

  // ---- Fare estimates (one per category) ----
  Future<void> loadFareEstimates(List<int> categoryIds) async {
    if (pickup == null || drop == null) return;
    isFareLoading = true;
    error = null;
    notifyListeners();

    try {
      final results = await Future.wait(
        categoryIds.map(
          (id) => _repository.getFareEstimate(
            vehicleCategoryId: id,
            pickup: pickup!,
            drop: drop!,
            stops: stops,
          ),
        ),
      );
      fareEstimates = results;
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      isFareLoading = false;
      notifyListeners();
    }
  }

  void selectCategory(int id) {
    selectedCategoryId = id;
    notifyListeners();
  }

  void setPaymentMethod(String method) {
    paymentMethod = method;
    notifyListeners();
  }

  void setSchedule(DateTime? dt) {
    scheduledAt = dt;
    notifyListeners();
  }

  // ---- Coupon ----
  Future<bool> applyCoupon(String code) async {
    final fare = selectedFare;
    if (fare == null) return false;
    try {
      final amount = double.tryParse(fare.finalFare) ?? 0;
      appliedCoupon = await _repository.validateCoupon(code, 'ride', amount);
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      error = e.message;
      notifyListeners();
      return false;
    }
  }

  void removeCoupon() {
    appliedCoupon = null;
    notifyListeners();
  }

  // ---- Booking ----
  Future<bool> bookRide() async {
    if (pickup == null || drop == null || selectedCategoryId == null) {
      return false;
    }
    _setLoading(true);
    try {
      activeBooking = await _repository.bookRide(
        vehicleCategoryId: selectedCategoryId!,
        pickup: pickup!,
        drop: drop!,
        paymentMethod: paymentMethod,
        stops: stops,
        couponCode: appliedCoupon?.code,
        scheduledAt: scheduledAt,
        rideShare: rideShare,
      );
      _setLoading(false);

      // Scheduled rides are assigned later, so skip live tracking.
      if (scheduledAt == null) {
        startStatusPolling(activeBooking!.orderId);
      }
      return true;
    } on ApiException catch (e) {
      _setError(e.message);
      return false;
    }
  }

  /// Resume the searching phase after an app restart (we only know the order
  /// id). Sets a minimal active booking and re-attaches status updates so the
  /// Searching screen behaves as if we never left.
  void resumeSearching(int orderId) {
    activeBooking = RideBookingModel(orderId: orderId, fare: FareBreakdown());
    rideStatus = RideStatusModel(status: 'pending');
    startStatusPolling(orderId);
    notifyListeners();
  }

  // ---- Searching phase: Pusher for instant updates, polling always on as a
  // safety net so terminal states (no_driver_found / accepted / cancelled) are
  // never missed even if a Pusher event doesn't arrive. ----
  void startStatusPolling(int orderId) {
    _subscribeForSearching(orderId);
    _pollStatus(orderId); // immediate check
    _statusPollTimer?.cancel();
    _statusPollTimer = Timer.periodic(
      const Duration(seconds: 4),
      (_) => _pollStatus(orderId),
    );
  }

  Future<void> _subscribeForSearching(int orderId) async {
    await _pusher.subscribeToOrder(
      orderId,
      onDriverAccepted: (data) {
        rideStatus = RideStatusModel(
          status: 'accepted',
          driver: data['driver'] != null
              ? DriverInfo.fromJson(Map<String, dynamic>.from(data['driver']))
              : null,
        );
        notifyListeners();
        stopStatusPolling();
      },
      onStatusUpdated: (data) {
        final status = data['status'] as String? ?? 'pending';
        rideStatus = RideStatusModel(status: status);
        notifyListeners();
        if (status == 'no_driver_found' || status == 'cancelled') {
          stopStatusPolling();
        }
      },
      onLocationUpdated: (_) {},
      onCompleted: (_) {},
      onCancelled: (_) {
        rideStatus = RideStatusModel(status: 'cancelled');
        notifyListeners();
        stopStatusPolling();
      },
    );
  }

  Future<void> _pollStatus(int orderId) async {
    try {
      rideStatus = await _repository.getStatus(orderId);
      notifyListeners();
      const terminal = {'accepted', 'cancelled', 'no_driver_found'};
      if (terminal.contains(rideStatus!.status)) {
        stopStatusPolling();
      }
    } on ApiException {
      // Ignore transient polling errors; keep the timer running.
    }
  }

  void stopStatusPolling() {
    _statusPollTimer?.cancel();
    _statusPollTimer = null;
    final orderId = activeBooking?.orderId;
    if (orderId != null) _pusher.unsubscribeFromOrder(orderId);
  }

  Future<bool> cancelRide(String reason) async {
    final orderId = activeBooking?.orderId;
    if (orderId == null) return false;
    try {
      await _repository.cancelRide(orderId, reason);
      stopStatusPolling();
      return true;
    } on ApiException catch (e) {
      _setError(e.message);
      return false;
    }
  }

  Future<void> addTip(double amount) async {
    final orderId = activeBooking?.orderId;
    if (orderId == null) return;
    try {
      await _repository.addTip(orderId, amount);
    } on ApiException catch (e) {
      _setError(e.message);
    }
  }

  Future<void> rateRide(int rating, String? comment, List<String> tags) async {
    final orderId = activeBooking?.orderId;
    if (orderId == null) return;
    try {
      await _repository.rateRide(orderId, rating, comment, tags);
    } on ApiException catch (e) {
      _setError(e.message);
    }
  }

  void clearBooking() {
    stopStatusPolling();
    pickup = null;
    drop = null;
    stops = [];
    selectedCategoryId = null;
    fareEstimates = [];
    paymentMethod = 'cash';
    appliedCoupon = null;
    scheduledAt = null;
    rideShare = false;
    activeBooking = null;
    rideStatus = null;
    error = null;
    notifyListeners();
  }

  // ---- Helpers ----
  void _setLoading(bool value) {
    isLoading = value;
    if (value) error = null;
    notifyListeners();
  }

  void _setError(String message) {
    error = message;
    isLoading = false;
    notifyListeners();
  }

  @override
  void dispose() {
    _statusPollTimer?.cancel();
    super.dispose();
  }
}
