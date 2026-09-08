import 'dart:io';

import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/dio_client.dart';
import '../../auth/model/driver_model.dart';
import '../models/emergency_contact_model.dart';

class ProfileRepository {
  final DioClient _client;
  ProfileRepository({DioClient? client})
    : _client = client ?? DioClient.instance;

  Future<DriverModel> getProfile() async {
    try {
      final res = await _client.get(ApiEndpoints.profile);
      final data = (res.data['data'] ?? res.data) as Map;
      final driverJson = (data['driver'] ?? data) as Map;
      return DriverModel.fromJson(driverJson.cast<String, dynamic>());
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<bool> updateProfile({
    String? name,
    String? email,
    File? avatar,
  }) async {
    try {
      final form = <String, dynamic>{'name': ?name, 'email': ?email};
      if (avatar != null) {
        form['avatar'] = await MultipartFile.fromFile(
          avatar.path,
          filename: avatar.path.split('/').last,
        );
      }
      final res = await _client.post(
        ApiEndpoints.profile,
        data: FormData.fromMap(form),
      );
      return _ok(res.data);
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<bool> updateWithdrawalInfo({
    required String method,
    required String account,
  }) async {
    try {
      final res = await _client.put(
        ApiEndpoints.profile,
        data: {'withdrawal_method': method, 'withdrawal_account': account},
      );
      return _ok(res.data);
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<EmergencyContactModel> getEmergencyContact() async {
    try {
      final res = await _client.get(ApiEndpoints.emergencyContact);
      final data = (res.data['data'] ?? res.data) as Map;
      return EmergencyContactModel.fromJson(data.cast<String, dynamic>());
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<bool> updateEmergencyContact({
    required String name,
    required String phone,
    String? relation,
  }) async {
    try {
      final res = await _client.post(
        ApiEndpoints.emergencyContact,
        data: {'name': name, 'phone': phone, 'relationship': ?relation},
      );
      return _ok(res.data);
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<bool> deleteAccount(String? reason) async {
    try {
      final res = await _client.delete(
        ApiEndpoints.deleteAccount,
        data: {'reason': ?reason},
      );
      return _ok(res.data);
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  bool _ok(dynamic body) =>
      body is Map && (body['success'] == true || body['status'] == true);
}
