import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/dio_client.dart';
import '../model/complaint_model.dart';
import 'complaint_repository.dart';

class ComplaintRepositoryImpl implements ComplaintRepository {
  final DioClient _client;

  ComplaintRepositoryImpl(this._client);

  @override
  Future<List<ComplaintModel>> getComplaints() async {
    try {
      final res = await _client.dio.get(ApiEndpoints.complaints);
      return (res.data['data'] as List? ?? [])
          .map((e) => ComplaintModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<ComplaintModel> createComplaint({
    required String category,
    required String description,
    int? orderId,
  }) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.complaints,
        data: {
          'category': category,
          'description': description,
          'order_id': ?orderId,
        },
      );
      return ComplaintModel.fromJson(
        Map<String, dynamic>.from(res.data['data']),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
