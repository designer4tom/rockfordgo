import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/dio_client.dart';
import '../model/driver_marker_model.dart';
import 'home_repository.dart';

class HomeRepositoryImpl implements HomeRepository {
  final DioClient _client;

  HomeRepositoryImpl(this._client);

  @override
  Future<List<DriverMarker>> getNearbyDrivers(
    double lat,
    double lng, {
    String? serviceType,
  }) async {
    try {
      final res = await _client.dio.get(
        ApiEndpoints.nearbyDrivers,
        queryParameters: {
          'lat': lat,
          'lng': lng,
          'type': ?serviceType,
        },
      );
      // The backend occasionally returns a malformed `data` payload (e.g. a
      // serialized PHP Collection object instead of a JSON array) — treat
      // anything that isn't a list of driver objects as "no drivers".
      final data = res.data is Map ? res.data['data'] : null;
      if (data is! List) return [];
      return data
          .whereType<Map>()
          .map((e) => DriverMarker.fromJson(Map<String, dynamic>.from(e)))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
