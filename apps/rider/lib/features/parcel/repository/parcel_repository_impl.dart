import 'dart:io';

import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/dio_client.dart';
import '../../location/model/place_model.dart';
import '../../ride/model/coupon_model.dart';
import '../model/parcel_booking_model.dart';
import '../model/parcel_estimate_model.dart';
import 'parcel_repository.dart';

class ParcelRepositoryImpl implements ParcelRepository {
  final DioClient _client;

  ParcelRepositoryImpl(this._client);

  @override
  Future<ParcelEstimateModel> getEstimate({
    required PlaceModel pickup,
    required PlaceModel drop,
    required String parcelType,
    required double weight,
    required String size,
    bool isCod = false,
    double? codAmount,
  }) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.parcelEstimate,
        data: {
          'pickup_lat': pickup.lat,
          'pickup_lng': pickup.lng,
          'drop_lat': drop.lat,
          'drop_lng': drop.lng,
          'parcel_type': parcelType,
          'weight': weight,
          'size': size,
          'is_cod': isCod,
          'cod_amount': ?codAmount,
        },
      );
      return ParcelEstimateModel.fromJson(
        Map<String, dynamic>.from(res.data['data']),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
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
    bool isCod = false,
    double? codAmount,
    required String paymentTiming,
    required String paymentMethod,
    String? couponCode,
  }) async {
    try {
      // Multipart so the optional parcel photo can be uploaded.
      final formData = FormData.fromMap({
        'sender_name': senderName,
        'sender_phone': senderPhone,
        'pickup_address': pickup.address,
        'pickup_lat': pickup.lat,
        'pickup_lng': pickup.lng,
        'receiver_name': receiverName,
        'receiver_phone': receiverPhone,
        'drop_address': drop.address,
        'drop_lat': drop.lat,
        'drop_lng': drop.lng,
        'parcel_type': parcelType,
        'weight': weight,
        'size': size,
        'parcel_note': ?note,
        'is_cod': isCod ? 1 : 0,
        'cod_amount': ?codAmount,
        'payment_timing': paymentTiming,
        'payment_method': paymentMethod,
        'coupon_code': ?couponCode,
        if (photo != null)
          'parcel_photo': await MultipartFile.fromFile(photo.path),
      });

      final res = await _client.dio.post(ApiEndpoints.parcelBook, data: formData);
      return ParcelBookingModel.fromJson(
        Map<String, dynamic>.from(res.data['data']),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<CouponModel> validateCoupon(String code, double amount) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.couponValidate,
        data: {'code': code, 'service_type': 'parcel', 'order_amount': amount},
      );
      final body = res.data;
      // The API can return 200 with `success: false` (e.g. "You have already
      // used this coupon."). Surface that message instead of crashing on a
      // missing `data`.
      final data = body is Map ? body['data'] : null;
      if ((body is Map && body['success'] == false) || data == null) {
        throw ApiException(
          message: (body is Map ? body['message'] as String? : null) ??
              'Invalid coupon',
        );
      }
      return CouponModel.fromJson(Map<String, dynamic>.from(data));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
