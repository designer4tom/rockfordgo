import 'dart:io';

import 'package:flutter/material.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/services/config_service.dart';
import '../../location/model/place_model.dart';
import '../../ride/model/coupon_model.dart';
import '../model/parcel_booking_model.dart';
import '../model/parcel_estimate_model.dart';
import '../repository/parcel_repository.dart';

class ParcelProvider extends ChangeNotifier {
  final ParcelRepository _repository;

  ParcelProvider(this._repository);

  // Step 1 — Sender
  String senderName = '';
  String senderPhone = '';
  PlaceModel? pickup;

  // Step 2 — Receiver
  String receiverName = '';
  String receiverPhone = '';
  PlaceModel? drop;

  // Step 3 — Parcel
  String parcelType = 'normal'; // normal/fragile/document
  double weight = 0.5;
  String size = 'small'; // small/medium/large
  String? parcelNote;
  File? parcelPhoto;

  // Step 4 — COD
  bool isCod = false;
  double? codAmount;
  // TODO(config): derive from /config cod_enabled. Defaulting on for now.
  // COD availability is driven by /config (cod_enabled).
  bool get codEnabled => ConfigService.getCached().codEnabled;

  // Step 5 — Payment
  ParcelEstimateModel? estimate;
  String paymentTiming = 'before';
  String paymentMethod = 'wallet';
  CouponModel? appliedCoupon;

  int currentStep = 0;
  bool isLoading = false;
  String? error;

  /// True when /parcel/book was rejected with 403 — the sender's outstanding
  /// delivery-charge due has reached the limit and COD booking is blocked.
  bool isDueLimitBlocked = false;

  // ---- Step setters ----
  void setSenderInfo({
    required String name,
    required String phone,
    PlaceModel? pickup,
  }) {
    senderName = name;
    senderPhone = phone;
    if (pickup != null) this.pickup = pickup;
    notifyListeners();
  }

  void setReceiverInfo({
    required String name,
    required String phone,
    PlaceModel? drop,
  }) {
    receiverName = name;
    receiverPhone = phone;
    if (drop != null) this.drop = drop;
    notifyListeners();
  }

  void setParcelDetails({
    required String type,
    required double weight,
    required String size,
    String? note,
    File? photo,
  }) {
    parcelType = type;
    this.weight = weight;
    this.size = size;
    parcelNote = note;
    parcelPhoto = photo;
    notifyListeners();
  }

  void setCod(bool enabled, double? amount) {
    isCod = enabled;
    codAmount = enabled ? amount : null;
    notifyListeners();
  }

  void setPaymentTiming(String timing) {
    paymentTiming = timing;
    notifyListeners();
  }

  void setPaymentMethod(String method) {
    paymentMethod = method;
    notifyListeners();
  }

  // ---- Step navigation ----
  void nextStep() {
    currentStep++;
    notifyListeners();
  }

  void prevStep() {
    if (currentStep > 0) currentStep--;
    notifyListeners();
  }

  // ---- Estimate ----
  Future<void> loadEstimate() async {
    if (pickup == null || drop == null) return;
    _setLoading(true);
    try {
      estimate = await _repository.getEstimate(
        pickup: pickup!,
        drop: drop!,
        parcelType: parcelType,
        weight: weight,
        size: size,
        isCod: isCod,
        codAmount: codAmount,
      );
      // Default payment timing to the first available option.
      if (estimate!.paymentTimingOptions.isNotEmpty) {
        paymentTiming = estimate!.paymentTimingOptions.first;
      }
      _setLoading(false);
    } on ApiException catch (e) {
      _setError(e.message);
    }
  }

  double get deliveryCharge =>
      double.tryParse(estimate?.deliveryCharge ?? '0') ?? 0;

  double get couponDiscount {
    final coupon = appliedCoupon;
    if (coupon == null) return 0;
    return coupon.discountOn(deliveryCharge);
  }

  double get payableAmount {
    final total = deliveryCharge - couponDiscount;
    return total < 0 ? 0 : total;
  }

  // ---- Coupon ----
  Future<bool> applyCoupon(String code) async {
    try {
      appliedCoupon =
          await _repository.validateCoupon(code, deliveryCharge);
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
  Future<ParcelBookingModel?> bookParcel() async {
    if (pickup == null || drop == null) return null;
    _setLoading(true);
    try {
      final booking = await _repository.bookParcel(
        senderName: senderName,
        senderPhone: senderPhone,
        pickup: pickup!,
        receiverName: receiverName,
        receiverPhone: receiverPhone,
        drop: drop!,
        parcelType: parcelType,
        weight: weight,
        size: size,
        note: parcelNote,
        photo: parcelPhoto,
        isCod: isCod,
        codAmount: codAmount,
        paymentTiming: paymentTiming,
        paymentMethod: paymentMethod,
        couponCode: appliedCoupon?.code,
      );
      _setLoading(false);
      return booking;
    } on ApiException catch (e) {
      isDueLimitBlocked = e.statusCode == 403;
      _setError(e.message);
      return null;
    }
  }

  void reset() {
    senderName = '';
    senderPhone = '';
    pickup = null;
    receiverName = '';
    receiverPhone = '';
    drop = null;
    parcelType = 'normal';
    weight = 0.5;
    size = 'small';
    parcelNote = null;
    parcelPhoto = null;
    isCod = false;
    codAmount = null;
    estimate = null;
    paymentTiming = 'before';
    paymentMethod = 'wallet';
    appliedCoupon = null;
    currentStep = 0;
    error = null;
    isDueLimitBlocked = false;
    notifyListeners();
  }

  // ---- Helpers ----
  void _setLoading(bool value) {
    isLoading = value;
    if (value) {
      error = null;
      isDueLimitBlocked = false;
    }
    notifyListeners();
  }

  void _setError(String message) {
    error = message;
    isLoading = false;
    notifyListeners();
  }
}
