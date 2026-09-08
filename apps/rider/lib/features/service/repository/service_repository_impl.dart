import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/dio_client.dart';
import '../model/service_model.dart';
import '../model/vehicle_category_model.dart';
import 'service_repository.dart';

class ServiceRepositoryImpl implements ServiceRepository {
  final DioClient _client;

  ServiceRepositoryImpl(this._client);

  @override
  Future<List<ServiceModel>> getServices() async {
    try {
      final res = await _client.dio.get(ApiEndpoints.services);
      final list = (res.data['data'] as List? ?? []);
      return list
          .map((e) => ServiceModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<List<VehicleCategoryModel>> getVehicleCategories(
    double lat,
    double lng,
  ) async {
    try {
      final res = await _client.dio.get(
        ApiEndpoints.vehicleCategories,
        // Backend requires pickup_lat/pickup_lng (not lat/lng).
        queryParameters: {'pickup_lat': lat, 'pickup_lng': lng},
      );
      final list = (res.data['data'] as List? ?? []);
      return list
          .map((e) =>
              VehicleCategoryModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
