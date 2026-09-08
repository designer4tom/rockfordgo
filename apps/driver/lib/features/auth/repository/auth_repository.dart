import 'dart:io';

import '../../../core/models/config_model.dart';
import '../../../core/models/vehicle_category_model.dart';
import '../model/auth_response_model.dart';
import '../model/driver_model.dart';

/// Contract for driver auth, registration and bootstrap data.
abstract class AuthRepository {
  /// Returns the OTP echoed by the API in non-production mode (for the dev
  /// helper card), or null when the response omits it (production).
  Future<String?> sendOtp(String phone);
  Future<AuthResponseModel> verifyOtp({required String phone, required String otp});
  Future<AuthResponseModel> checkStatus();
  Future<void> logout();

  /// Clears persisted auth/session data locally WITHOUT calling the API.
  /// Used after account deletion, where the server has already revoked the
  /// token so a logout request would only return 401.
  Future<void> clearLocalSession();

  Future<ConfigModel> getConfig();
  Future<List<VehicleCategoryModel>> getVehicleCategories();

  /// Multipart registration with personal info, documents and vehicle data.
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
    required Map<String, File> documents, // field -> file
  });

  Future<DriverModel?> cachedDriver();

  /// Pushes a refreshed FCM device token to the backend so push notifications
  /// (order requests, chat, …) keep reaching this device after the token the
  /// backend has on file rotates — FCM tokens are not stable for the life of
  /// an install, and we only ever sent one at login otherwise.
  Future<void> updateFcmToken(String token);
}
