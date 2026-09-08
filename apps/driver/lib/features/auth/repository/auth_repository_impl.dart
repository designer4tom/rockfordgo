import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/models/config_model.dart';
import '../../../core/models/vehicle_category_model.dart';
import '../../../core/network/dio_client.dart';
import '../../../core/services/fcm_service.dart';
import '../../../core/storage/secure_storage.dart';
import '../model/auth_response_model.dart';
import '../model/driver_model.dart';
import 'auth_repository.dart';

class AuthRepositoryImpl implements AuthRepository {
  final DioClient _client;
  final SecureStorage _storage;

  AuthRepositoryImpl({DioClient? client, SecureStorage? storage})
      : _client = client ?? DioClient.instance,
        _storage = storage ?? SecureStorage.instance;

  @override
  Future<String?> sendOtp(String phone) async {
    try {
      final res = await _client.post(
        ApiEndpoints.sendOtp,
        data: {'phone': phone},
      );
      // Non-production API echoes the OTP; production omits it.
      final data = res.data is Map ? res.data['data'] : null;
      final otp = (data is Map ? data['otp'] : null) ?? res.data['otp'];
      return otp?.toString();
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  @override
  Future<AuthResponseModel> verifyOtp({
    required String phone,
    required String otp,
  }) async {
    try {
      // FCM token so the backend can push notifications to this device.
      final deviceToken = await _deviceToken();
      final res = await _client.post(
        ApiEndpoints.verifyOtp,
        data: {
          'phone': phone,
          'otp': otp,
          'device_token': ?deviceToken,
          if (deviceToken != null) 'platform': Platform.isIOS ? 'ios' : 'android',
        },
      );
      final auth = AuthResponseModel.fromJson(
          (res.data as Map).cast<String, dynamic>());
      await _persist(auth);
      return auth;
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  @override
  Future<AuthResponseModel> checkStatus() async {
    try {
      final res = await _client.get(ApiEndpoints.authStatus);
      final auth = AuthResponseModel.fromJson(
          (res.data as Map).cast<String, dynamic>());
      await _persist(auth);
      return auth;
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  @override
  Future<void> logout() async {
    try {
      await _client.post(ApiEndpoints.logout);
    } catch (_) {
      // Ignore any failure (incl. 401 if the token is already revoked) —
      // clear locally regardless.
    } finally {
      await _storage.clearAll();
    }
  }

  @override
  Future<void> clearLocalSession() => _storage.clearAll();

  @override
  Future<ConfigModel> getConfig() async {
    try {
      final res = await _client.get(ApiEndpoints.config);
      return ConfigModel.fromJson((res.data['data'] as Map).cast<String, dynamic>());
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  @override
  Future<List<VehicleCategoryModel>> getVehicleCategories() async {
    try {
      final res = await _client.get(ApiEndpoints.vehicleCategories);
      final list = (res.data['data'] as List?) ?? const [];
      return list
          .map((e) => VehicleCategoryModel.fromJson(
              (e as Map).cast<String, dynamic>()))
          .toList();
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  @override
  Future<AuthResponseModel> register({
    required String name,
    String? email,
    required int vehicleCategoryId,
    required String vehicleMake,
    required String vehicleModel,
    required String vehicleYear,
    required String vehicleColor,
    required String vehicleRegNumber,
    DateTime? licenseExpiry,
    DateTime? vehicleRegExpiry,
    DateTime? insuranceExpiry,
    required Map<String, File> documents,
  }) async {
    try {
      final form = <String, dynamic>{
        'name': name,
        if (email != null && email.isNotEmpty) 'email': email,
        'vehicle_category_id': vehicleCategoryId,
        'vehicle_make': vehicleMake,
        'vehicle_model': vehicleModel,
        'vehicle_year': vehicleYear,
        'vehicle_color': vehicleColor,
        'vehicle_registration_number': vehicleRegNumber,
        if (licenseExpiry != null)
          'license_expiry': _fmtDate(licenseExpiry),
        if (vehicleRegExpiry != null)
          'vehicle_registration_expiry': _fmtDate(vehicleRegExpiry),
        if (insuranceExpiry != null)
          'insurance_expiry': _fmtDate(insuranceExpiry),
      };

      for (final entry in documents.entries) {
        form[entry.key] = await MultipartFile.fromFile(
          entry.value.path,
          filename: entry.value.path.split('/').last,
        );
      }

      final res = await _client.post(
        ApiEndpoints.register,
        data: FormData.fromMap(form),
      );
      final auth = AuthResponseModel.fromJson(
          (res.data as Map).cast<String, dynamic>());
      await _persist(auth);
      return auth;
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  @override
  Future<DriverModel?> cachedDriver() async {
    final data = await _storage.getDriver();
    return data == null ? null : DriverModel.fromJson(data);
  }

  @override
  Future<void> updateFcmToken(String token) async {
    try {
      await _client.post(
        ApiEndpoints.updateFcmToken,
        data: {
          'device_token': token,
          'platform': Platform.isIOS ? 'ios' : 'android',
        },
      );
    } catch (_) {
      // best-effort — a failed refresh just means the old token stays on
      // file until the next successful login or retry.
    }
  }

  Future<void> _persist(AuthResponseModel auth) async {
    if (auth.token != null && auth.token!.isNotEmpty) {
      await _storage.saveToken(auth.token!);
    }
    // Persist auth/registration meta even before a full driver object exists,
    // so the router guard resolves the right screen on app restart.
    final map = auth.driver != null ? _driverToMap(auth.driver!) : <String, dynamic>{};
    map['status'] = auth.status;
    map['registration_completed'] = auth.registrationCompleted;
    if (auth.rejectionReason != null) {
      map['rejection_reason'] = auth.rejectionReason;
    }
    await _storage.saveDriver(map);
  }

  Map<String, dynamic> _driverToMap(DriverModel d) => {
        'id': d.id,
        'name': d.name,
        'phone': d.phone,
        'email': d.email,
        'avatar': d.avatar,
        'status': d.status,
        'is_online': d.isOnline,
        'wallet_balance': d.walletBalance,
        'due_amount': d.dueAmount,
        'average_rating': d.averageRating,
        'total_trips': d.totalTrips,
        'rejection_reason': d.rejectionReason,
      };

  String _fmtDate(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  /// FCM device token; null when Firebase isn't configured (request omits it).
  Future<String?> _deviceToken() async {
    try {
      final token = await FcmService.instance.getToken();
      if (kDebugMode) {
        debugPrint(token == null
            ? 'device_token: NULL (FCM not ready)'
            : 'device_token FULL: $token');
      }
      return token;
    } catch (e) {
      if (kDebugMode) debugPrint('device_token error: $e');
      return null;
    }
  }
}
