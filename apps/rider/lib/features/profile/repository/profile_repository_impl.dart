import 'dart:io';

import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/dio_client.dart';
import '../../auth/model/user_model.dart';
import 'profile_repository.dart';

class ProfileRepositoryImpl implements ProfileRepository {
  final DioClient _client;

  ProfileRepositoryImpl(this._client);

  @override
  Future<UserModel> getProfile() async {
    try {
      final res = await _client.dio.get(ApiEndpoints.profile);
      return UserModel.fromJson(Map<String, dynamic>.from(res.data['data']));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<UserModel> updateProfile({
    String? name,
    String? email,
    File? avatar,
  }) async {
    try {
      final formData = FormData.fromMap({
        'name': ?name,
        'email': ?email,
        if (avatar != null)
          'avatar': await MultipartFile.fromFile(avatar.path),
      });
      final res = await _client.dio.post(ApiEndpoints.profile, data: formData);
      return UserModel.fromJson(Map<String, dynamic>.from(res.data['data']));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<void> deleteAccount(String? reason) async {
    try {
      await _client.dio.delete(
        ApiEndpoints.deleteAccount,
        data: {'reason': ?reason},
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<void> updateEmergencyContact({
    required String name,
    required String phone,
  }) async {
    try {
      await _client.dio.post(
        ApiEndpoints.emergencyContact,
        data: {'name': name, 'phone': phone},
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
