import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../constants/app_constants.dart';

/// Secure persistence for auth token and driver data.
class SecureStorage {
  SecureStorage._();
  static final SecureStorage instance = SecureStorage._();

  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  // ---- Token ----
  Future<void> saveToken(String token) =>
      _storage.write(key: AppConstants.tokenKey, value: token);

  Future<String?> getToken() => _storage.read(key: AppConstants.tokenKey);

  Future<void> deleteToken() => _storage.delete(key: AppConstants.tokenKey);

  Future<bool> hasToken() async => (await getToken())?.isNotEmpty ?? false;

  // ---- Driver data ----
  Future<void> saveDriver(Map<String, dynamic> driver) =>
      _storage.write(key: AppConstants.driverKey, value: jsonEncode(driver));

  Future<Map<String, dynamic>?> getDriver() async {
    final raw = await _storage.read(key: AppConstants.driverKey);
    if (raw == null || raw.isEmpty) return null;
    return jsonDecode(raw) as Map<String, dynamic>;
  }

  Future<void> deleteDriver() => _storage.delete(key: AppConstants.driverKey);

  // ---- Clear everything (logout) ----
  Future<void> clearAll() async {
    await deleteToken();
    await deleteDriver();
  }
}
