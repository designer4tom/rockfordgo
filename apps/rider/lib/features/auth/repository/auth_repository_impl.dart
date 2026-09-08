import 'dart:io';

import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_response.dart';
import '../../../core/network/dio_client.dart';
import '../model/auth_response_model.dart';
import 'auth_repository.dart';

class AuthRepositoryImpl implements AuthRepository {
  final DioClient _client;

  AuthRepositoryImpl(this._client);

  @override
  Future<ApiResponse> sendOtp(String phone) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.sendOtp,
        data: {'phone': phone},
      );
      return ApiResponse.fromJson(res.data, null);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<AuthResponseModel> verifyOtp({
    required String phone,
    required String otp,
    String? name,
    String? fcmToken,
  }) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.verifyOtp,
        data: {
          'phone': phone,
          'otp': otp,
          'name': ?name,
          // Backend expects the FCM device token under `device_token`; it is
          // stored and used to trigger push notifications.
          'device_token': ?fcmToken,
        },
      );
      return AuthResponseModel.fromJson(
        Map<String, dynamic>.from(res.data['data']),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<AuthResponseModel> completeProfile({
    required String name,
    String? email,
    String? referralCode,
    File? avatar,
  }) async {
    try {
      // Avatar is optional → use multipart only when a photo was picked,
      // otherwise send a plain JSON body as before.
      final Object data;
      if (avatar != null) {
        data = FormData.fromMap({
          'name': name,
          if (email != null && email.isNotEmpty) 'email': email,
          if (referralCode != null && referralCode.isNotEmpty)
            'referral_code': referralCode,
          'avatar': await MultipartFile.fromFile(avatar.path),
        });
      } else {
        data = {
          'name': name,
          if (email != null && email.isNotEmpty) 'email': email,
          if (referralCode != null && referralCode.isNotEmpty)
            'referral_code': referralCode,
        };
      }
      final res = await _client.dio.post(
        ApiEndpoints.completeProfile,
        data: data,
      );
      return AuthResponseModel.fromJson(
        Map<String, dynamic>.from(res.data['data']),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<void> logout() async {
    try {
      await _client.dio.post(ApiEndpoints.logout);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
