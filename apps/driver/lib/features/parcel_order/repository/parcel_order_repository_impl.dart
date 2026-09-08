import 'dart:io';

import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/dio_client.dart';
import '../model/active_parcel_model.dart';
import 'parcel_order_repository.dart';

class ParcelOrderRepositoryImpl implements ParcelOrderRepository {
  final DioClient _client;
  ParcelOrderRepositoryImpl({DioClient? client})
      : _client = client ?? DioClient.instance;

  @override
  Future<ActiveParcelModel?> loadActiveOrder(int orderId) async {
    try {
      final res = await _client.get(ApiEndpoints.orderDetail(orderId));
      final data = res.data['data'] ?? res.data;
      if (data == null) return null;
      return ActiveParcelModel.fromJson((data as Map).cast<String, dynamic>());
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  @override
  Future<bool> updateStatus(int orderId, String status) async {
    try {
      final res = await _client.post(
        ApiEndpoints.parcelUpdateStatus,
        data: {'order_id': orderId, 'status': status},
      );
      return _ok(res.data);
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  @override
  Future<bool> collectCod(int orderId, double amount) async {
    try {
      final res = await _client.post(
        ApiEndpoints.parcelCollectCod,
        data: {'order_id': orderId, 'collected_amount': amount},
      );
      return _ok(res.data);
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  @override
  Future<bool> complete({
    required int orderId,
    String? proofType,
    String? otp,
    File? photo,
    String? signature,
  }) async {
    try {
      // Backend field names: proof_type + proof_otp / proof_photo / proof_signature.
      final form = <String, dynamic>{
        'order_id': orderId,
        'proof_type': ?proofType,
        'proof_otp': ?otp,
        'proof_signature': ?signature,
      };
      if (photo != null) {
        form['proof_photo'] = await MultipartFile.fromFile(
          photo.path,
          filename: photo.path.split('/').last,
        );
      }
      final res = await _client.post(
        ApiEndpoints.parcelComplete,
        data: FormData.fromMap(form),
      );
      return _ok(res.data);
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  bool _ok(dynamic body) =>
      body is Map && (body['success'] == true || body['status'] == true);
}
