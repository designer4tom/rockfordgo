import 'dart:async';

import 'package:flutter/foundation.dart';

import '../../../core/constants/app_constants.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/services/auth_session.dart';
import '../model/auth_response_model.dart';
import '../model/driver_model.dart';
import '../repository/auth_repository.dart';

class AuthProvider extends ChangeNotifier {
  final AuthRepository _repo;
  AuthProvider(this._repo);

  bool _loading = false;
  bool get loading => _loading;

  String? _error;
  String? get error => _error;

  String _phone = '';
  String get phone => _phone;

  DriverModel? _driver;
  DriverModel? get driver => _driver;

  AuthResponseModel? _lastAuth;
  AuthResponseModel? get lastAuth => _lastAuth;

  String? get rejectionReason =>
      _lastAuth?.rejectionReason ?? _driver?.rejectionReason;

  // OTP echoed by the API in non-production mode; null in production.
  String? _devOtp;
  String? get devOtp => _devOtp;

  // ---- Resend countdown ----
  int _resendSeconds = 0;
  int get resendSeconds => _resendSeconds;
  bool get canResend => _resendSeconds == 0;
  Timer? _resendTimer;

  void _setLoading(bool v) {
    _loading = v;
    notifyListeners();
  }

  Future<bool> sendOtp(String phone) async {
    _phone = phone;
    _error = null;
    _devOtp = null;
    _setLoading(true);
    try {
      _devOtp = await _repo.sendOtp(phone);
      _startResendTimer();
      _setLoading(false);
      return true;
    } on ApiException catch (e) {
      _error = e.message;
      _setLoading(false);
      return false;
    }
  }

  Future<AuthResult> verifyOtp(String otp) async {
    _error = null;
    _setLoading(true);
    try {
      final auth = await _repo.verifyOtp(phone: _phone, otp: otp);
      _lastAuth = auth;
      _driver = auth.driver;
      _syncSession(auth);
      _setLoading(false);
      return auth.toResult();
    } on ApiException catch (e) {
      _error = e.message;
      _setLoading(false);
      return AuthResult.failed;
    }
  }

  Future<AuthResult> checkStatus() async {
    try {
      final auth = await _repo.checkStatus();
      _lastAuth = auth;
      _driver = auth.driver;
      _syncSession(auth);
      notifyListeners();
      return auth.toResult();
    } on ApiException catch (e) {
      _error = e.message;
      notifyListeners();
      return AuthResult.failed;
    }
  }

  Future<void> loadCachedDriver() async {
    _driver = await _repo.cachedDriver();
    notifyListeners();
  }

  Future<void> logout() async {
    await _repo.logout();
    _driver = null;
    _lastAuth = null;
    _phone = '';
    AuthSession.instance.clear();
    notifyListeners();
  }

  /// Local-only session reset — no logout API call. Use after the account has
  /// been deleted server-side (the token is already revoked, so calling
  /// `/auth/logout` would 401). Mirrors [logout] without the network request.
  Future<void> clearSession() async {
    await _repo.clearLocalSession();
    _driver = null;
    _lastAuth = null;
    _phone = '';
    AuthSession.instance.clear();
    notifyListeners();
  }

  void _syncSession(AuthResponseModel auth) {
    AuthSession.instance.update(
      token: auth.token,
      status: auth.status,
      registrationCompleted: auth.registrationCompleted,
    );
  }

  /// Called after a successful registration submit so the guard knows the
  /// driver has finished the steps and is awaiting approval.
  void markRegistrationCompleted() {
    AuthSession.instance.update(registrationCompleted: true, status: 'pending');
  }

  void _startResendTimer() {
    _resendSeconds = AppConstants.otpResendSeconds;
    _resendTimer?.cancel();
    _resendTimer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (_resendSeconds <= 0) {
        t.cancel();
      } else {
        _resendSeconds--;
      }
      notifyListeners();
    });
  }

  @override
  void dispose() {
    _resendTimer?.cancel();
    super.dispose();
  }
}
