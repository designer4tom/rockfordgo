import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/dio_client.dart';
import '../model/active_order_model.dart';
import '../model/tracking_model.dart';
import 'tracking_repository.dart';

class TrackingRepositoryImpl implements TrackingRepository {
  final DioClient _client;

  TrackingRepositoryImpl(this._client);

  String _statusPath(int orderId, String type) =>
      type == 'parcel'
          ? ApiEndpoints.parcelStatus(orderId)
          : ApiEndpoints.rideStatus(orderId);

  String _cancelPath(int orderId, String type) =>
      type == 'parcel'
          ? ApiEndpoints.parcelCancel(orderId)
          : ApiEndpoints.rideCancel(orderId);

  String _ratePath(int orderId, String type) =>
      type == 'parcel'
          ? ApiEndpoints.parcelRate(orderId)
          : ApiEndpoints.rideRate(orderId);

  @override
  Future<TrackingModel> getStatus(int orderId, String type) async {
    try {
      final res = await _client.dio.get(_statusPath(orderId, type));
      return TrackingModel.fromJson(
        Map<String, dynamic>.from(res.data['data']),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<ActiveOrderModel> getActiveOrder() async {
    try {
      final res = await _client.dio.get(ApiEndpoints.activeOrder);
      return ActiveOrderModel.fromJson(
        Map<String, dynamic>.from(res.data['data']),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<Map<String, dynamic>> cancelOrder(
    int orderId,
    String type,
    String reason,
  ) async {
    try {
      final res = await _client.dio.post(
        _cancelPath(orderId, type),
        data: {'reason': reason},
      );
      return Map<String, dynamic>.from(res.data);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<void> rate(
    int orderId,
    String type,
    int rating,
    String? comment,
    List<String> tags,
  ) async {
    try {
      await _client.dio.post(
        _ratePath(orderId, type),
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
  Future<void> addTip(int orderId, String type, double amount) async {
    try {
      // Tipping is a ride-only flow in the API.
      await _client.dio.post(
        ApiEndpoints.rideTip(orderId),
        data: {'amount': amount},
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
  Future<void> triggerSos(int orderId, double? lat, double? lng) async {
    try {
      await _client.dio.post(
        ApiEndpoints.sos,
        data: {
          'order_id': orderId,
          'lat': ?lat,
          'lng': ?lng,
        },
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
