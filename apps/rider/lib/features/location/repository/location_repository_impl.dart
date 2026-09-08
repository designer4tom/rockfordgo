import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/dio_client.dart';
import '../model/place_model.dart';
import 'location_repository.dart';

class LocationRepositoryImpl implements LocationRepository {
  final DioClient _client;

  LocationRepositoryImpl(this._client);

  @override
  Future<List<PlaceModel>> searchPlaces(
    String query, {
    double? lat,
    double? lng,
  }) async {
    try {
      final res = await _client.dio.get(
        ApiEndpoints.geocodeSearch,
        queryParameters: {
          'q': query,
          'lat': ?lat,
          'lng': ?lng,
        },
      );
      final list = (res.data['data'] as List? ?? []);
      return list
          .map((e) => PlaceModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<PlaceModel> placeDetails(String placeId) async {
    try {
      final res = await _client.dio.get(
        ApiEndpoints.geocodePlace,
        queryParameters: {'place_id': placeId},
      );
      return PlaceModel.fromJson(Map<String, dynamic>.from(res.data['data']));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<PlaceModel> reverseGeocode(double lat, double lng) async {
    try {
      final res = await _client.dio.get(
        ApiEndpoints.geocodeReverse,
        queryParameters: {'lat': lat, 'lng': lng},
      );
      return PlaceModel.fromJson(Map<String, dynamic>.from(res.data['data']));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
