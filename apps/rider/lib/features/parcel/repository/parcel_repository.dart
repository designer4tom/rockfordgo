import 'dart:io';

import '../../location/model/place_model.dart';
import '../../ride/model/coupon_model.dart';
import '../model/parcel_booking_model.dart';
import '../model/parcel_estimate_model.dart';

abstract class ParcelRepository {
  Future<ParcelEstimateModel> getEstimate({
    required PlaceModel pickup,
    required PlaceModel drop,
    required String parcelType,
    required double weight,
    required String size,
    bool isCod,
    double? codAmount,
  });

  Future<ParcelBookingModel> bookParcel({
    required String senderName,
    required String senderPhone,
    required PlaceModel pickup,
    required String receiverName,
    required String receiverPhone,
    required PlaceModel drop,
    required String parcelType,
    required double weight,
    required String size,
    String? note,
    File? photo,
    bool isCod,
    double? codAmount,
    required String paymentTiming,
    required String paymentMethod,
    String? couponCode,
  });

  Future<CouponModel> validateCoupon(String code, double amount);
}
