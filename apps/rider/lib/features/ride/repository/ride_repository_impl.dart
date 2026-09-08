import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/dio_client.dart';
import '../../location/model/place_model.dart';
import '../model/coupon_model.dart';
import '../model/fare_estimate_model.dart';
import '../model/ride_booking_model.dart';
import '../model/ride_status_model.dart';
import 'ride_repository.dart';

class RideRepositoryImpl implements RideRepository {
  final DioClient _client;

  RideRepositoryImpl(this._client);

  @override
  Future<FareEstimateModel> getFareEstimate({
    required int vehicleCategoryId,
    required PlaceModel pickup,
    required PlaceModel drop,
    List<PlaceModel>? stops,
  }) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.rideFareEstimate,
        data: {
          'vehicle_category_id': vehicleCategoryId,
          'pickup_lat': pickup.lat,
          'pickup_lng': pickup.lng,
          'drop_lat': drop.lat,
          'drop_lng': drop.lng,
          if (stops != null && stops.isNotEmpty)
            'stops': stops
                .map((s) => {'lat': s.lat, 'lng': s.lng})
                .toList(),
        },
      );
      return FareEstimateModel.fromJson(
        Map<String, dynamic>.from(res.data['data']),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<RideBookingModel> bookRide({
    required int vehicleCategoryId,
    required PlaceModel pickup,
    required PlaceModel drop,
    required String paymentMethod,
    List<PlaceModel>? stops,
    String? couponCode,
    DateTime? scheduledAt,
    bool rideShare = false,
  }) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.rideBook,
        data: {
          'vehicle_category_id': vehicleCategoryId,
          'pickup_address': pickup.address,
          'pickup_lat': pickup.lat,
          'pickup_lng': pickup.lng,
          'drop_address': drop.address,
          'drop_lat': drop.lat,
          'drop_lng': drop.lng,
          'payment_method': paymentMethod,
          if (stops != null && stops.isNotEmpty)
            'stops': stops
                .map((s) => {'lat': s.lat, 'lng': s.lng})
                .toList(),
          'coupon_code': ?couponCode,
          'scheduled_at': ?scheduledAt?.toIso8601String(),
          'ride_share': rideShare,
        },
      );
      return RideBookingModel.fromJson(
        Map<String, dynamic>.from(res.data['data']),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<RideStatusModel> getStatus(int orderId) async {
    try {
      final res = await _client.dio.get(ApiEndpoints.rideStatus(orderId));
      return RideStatusModel.fromJson(
        Map<String, dynamic>.from(res.data['data']),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<Map<String, dynamic>> cancelRide(int orderId, String reason) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.rideCancel(orderId),
        data: {'reason': reason},
      );
      return Map<String, dynamic>.from(res.data);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<void> addTip(int orderId, double amount) async {
    try {
      await _client.dio.post(
        ApiEndpoints.rideTip(orderId),
        data: {'amount': amount},
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<void> rateRide(
    int orderId,
    int rating,
    String? comment,
    List<String> tags,
  ) async {
    try {
      await _client.dio.post(
        ApiEndpoints.rideRate(orderId),
        data: {
          'rating': rating,
          'comment': ?comment,
          'tags': tags,
        },
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<String> generateShareLink(int orderId) async {
    try {
      final res = await _client.dio.post(ApiEndpoints.rideShareLink(orderId));
      return res.data['data']?['url'] ?? res.data['data']?['link'] ?? '';
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<CouponModel> validateCoupon(
    String code,
    String serviceType,
    double amount,
  ) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.couponValidate,
        data: {
          'code': code,
          'service_type': serviceType,
          'order_amount': amount,
        },
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
