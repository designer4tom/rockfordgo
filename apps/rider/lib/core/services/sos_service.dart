import 'package:dio/dio.dart';

import '../constants/api_endpoints.dart';
import '../network/api_exception.dart';
import '../network/dio_client.dart';

/// Centralised SOS trigger, used from tracking screens.
class SosService {
  final DioClient _client;

  SosService(this._client);

  Future<void> triggerSos({int? orderId, double? lat, double? lng}) async {
    try {
      await _client.dio.post(
        ApiEndpoints.sos,
        data: {
          'order_id': ?orderId,
          'lat': ?lat,
          'lng': ?lng,
        },
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
