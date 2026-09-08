import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/services/fcm_service.dart';
import '../../../core/storage/secure_storage.dart';
import '../model/user_model.dart';
import '../repository/auth_repository.dart';

enum AuthResult { success, needsProfile, failed }

class AuthProvider extends ChangeNotifier {
  final AuthRepository _repository;
  final SecureStorage _storage;

  AuthProvider(this._repository, this._storage);

  // State
  bool _isLoading = false;
  String? _error;
  UserModel? _user;
  String _phone = '';
  int _resendSeconds = 0;
  Timer? _resendTimer;
  String? _devOtp;
  bool? _userExists;

  // Getters
  bool get isLoading => _isLoading;
  String? get error => _error;
  UserModel? get user => _user;
  String get phone => _phone;
  int get resendSeconds => _resendSeconds;
  bool get canResend => _resendSeconds == 0;

  /// OTP echoed back by the API in non-production builds (for testing).
  /// Null in production, where the OTP is never returned in the response.
  String? get devOtp => _devOtp;

  /// Whether the phone belongs to an existing user (from send-otp response).
  /// Null when the backend doesn't return the flag — treated as a new user.
  bool? get userExists => _userExists;

  // ---- Restore persisted user ----
  /// Rehydrate the in-memory [user] from secure storage after an app restart,
  /// so screens that pre-fill from the profile (e.g. parcel sender info) work
  /// even when the session was restored from a saved token.
  Future<void> loadStoredUser() async {
    if (_user != null) return;
    final json = await _storage.getUser();
    if (json == null || json.isEmpty) return;
    try {
      _user = UserModel.fromJson(jsonDecode(json) as Map<String, dynamic>);
      notifyListeners();
    } catch (_) {
      // Corrupted cache — ignore and leave the user unset.
    }
  }

  // ---- Sync wallet balance ----
  /// Mirror a freshly-loaded wallet balance onto the cached user so every
  /// screen that reads `user.walletBalance` (profile, drawer, ride payment …)
  /// updates automatically after an add-money. No-op until a user is loaded.
  void updateWalletBalance(String balance) {
    final u = _user;
    if (u == null || u.walletBalance == balance) return;
    _user = UserModel(
      id: u.id,
      name: u.name,
      phone: u.phone,
      email: u.email,
      avatar: u.avatar,
      walletBalance: balance,
      referralCode: u.referralCode,
      isActive: u.isActive,
    );
    _storage.saveUser(jsonEncode(_user!.toJson()));
    notifyListeners();
  }

  // ---- Send OTP ----
  Future<bool> sendOtp(String phone) async {
    _setLoading(true);
    try {
      final response = await _repository.sendOtp(phone);
      _phone = phone;
      // The backend includes `otp` in the response only in dev/testing.
      final data = response.data;
      _devOtp = data is Map ? data['otp']?.toString() : null;
      _userExists = data is Map ? _parseBool(data['user_exists']) : null;
      _startResendTimer();
      _setLoading(false);
      return true;
    } on ApiException catch (e) {
      _setError(e.message);
      return false;
    }
  }

  // ---- Resend OTP ----
  Future<bool> resendOtp() async {
    if (!canResend) return false;
    return sendOtp(_phone);
  }

  // ---- Verify OTP ----
  Future<AuthResult> verifyOtp(String otp, {String? name}) async {
    _setLoading(true);
    try {
      final fcmToken = await _getFcmToken();
      final result = await _repository.verifyOtp(
        phone: _phone,
        otp: otp,
        name: name,
        fcmToken: fcmToken,
      );

      // New user without a full token → needs profile completion.
      if (result.isNewUser && result.token == null) {
        if (result.tempToken != null) {
          await _storage.saveToken(result.tempToken!);
        }
        _setLoading(false);
        return AuthResult.needsProfile;
      }

      // Logged in.
      if (result.token != null) {
        await _storage.saveToken(result.token!);
      }
      if (result.user != null) {
        _user = result.user;
        await _storage.saveUser(jsonEncode(result.user!.toJson()));
      }
      _setLoading(false);
      return AuthResult.success;
    } on ApiException catch (e) {
      _setError(e.message);
      return AuthResult.failed;
    }
  }

  // ---- Complete Profile ----
  Future<bool> completeProfile({
    required String name,
    String? email,
    String? referralCode,
    File? avatar,
  }) async {
    _setLoading(true);
    try {
      final result = await _repository.completeProfile(
        name: name,
        email: email,
        referralCode: referralCode,
        avatar: avatar,
      );
      if (result.token != null) {
        await _storage.saveToken(result.token!);
      }
      if (result.user != null) {
        _user = result.user;
        await _storage.saveUser(jsonEncode(result.user!.toJson()));
      }
      _setLoading(false);
      return true;
    } on ApiException catch (e) {
      _setError(e.message);
      return false;
    }
  }

  // ---- Logout ----
  Future<void> logout() async {
    try {
      await _repository.logout();
    } on ApiException {
      // Ignore network errors on logout — clear the session regardless.
    }
    await _storage.clearAll();
    _user = null;
    _phone = '';
    notifyListeners();
  }

  // ---- Resend timer (30s countdown) ----
  void _startResendTimer() {
    _resendSeconds = AppConstants.otpResendSeconds;
    _resendTimer?.cancel();
    _resendTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_resendSeconds > 0) {
        _resendSeconds--;
        notifyListeners();
      } else {
        timer.cancel();
      }
    });
  }

  /// Reset transient state when leaving the OTP screen.
  void resetTimer() {
    _resendTimer?.cancel();
    _resendSeconds = 0;
  }

  // ---- FCM token ----
  // Firebase is initialised in a later phase. Until then this safely
  // returns null so verify-otp still works.
  /// Robustly parse a bool that the API may send as bool, int (1/0) or
  /// string ("true"/"1"). Null when absent.
  static bool? _parseBool(dynamic value) {
    if (value == null) return null;
    if (value is bool) return value;
    if (value is num) return value != 0;
    if (value is String) {
      final v = value.toLowerCase();
      return v == 'true' || v == '1';
    }
    return null;
  }

  Future<String?> _getFcmToken() async {
    // Real device token (null if Firebase isn't configured — safely ignored).
    return FcmService().getToken();
  }

  // ---- Helpers ----
  void _setLoading(bool value) {
    _isLoading = value;
    if (value) _error = null;
    notifyListeners();
  }

  void _setError(String message) {
    _error = message;
    _isLoading = false;
    notifyListeners();
  }

  void clearError() {
    _error = null;
  }

  @override
  void dispose() {
    _resendTimer?.cancel();
    super.dispose();
  }
}
