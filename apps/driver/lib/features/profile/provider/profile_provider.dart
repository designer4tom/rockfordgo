import 'dart:io';

import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../../auth/model/driver_model.dart';
import '../../auth/repository/auth_repository.dart';
import '../models/emergency_contact_model.dart';
import '../repository/profile_repository.dart';

class ProfileProvider extends ChangeNotifier {
  final ProfileRepository _repository;
  final AuthRepository _authRepository;

  ProfileProvider(this._repository, this._authRepository);

  DriverModel? driver;
  EmergencyContactModel? emergencyContact;
  bool loading = false;
  bool loadingContact = false;
  bool submitting = false;
  String? error;

  /// Clears cached profile state (e.g. after the account is deleted).
  void reset() {
    driver = null;
    error = null;
    loading = false;
    submitting = false;
    notifyListeners();
  }

  Future<void> loadProfile() async {
    loading = true;
    notifyListeners();
    try {
      driver = await _repository.getProfile();
      error = null;
    } on ApiException catch (e) {
      error = e.message;
    }
    loading = false;
    notifyListeners();
  }

  /// Refresh the driver after a recharge. The gateway credits the wallet via
  /// an async server callback, so a single fetch can still read the old
  /// balance. Poll until [driver.walletBalance] changes, calling [onUpdated]
  /// with each fresh driver so listeners (e.g. the drawer via HomeProvider)
  /// can stay in sync too.
  Future<void> refreshAfterRecharge({
    String? previousBalance,
    String? previousDue,
    void Function(DriverModel? driver)? onUpdated,
  }) async {
    final beforeBalance = previousBalance ?? driver?.walletBalance;
    final beforeDue = previousDue ?? driver?.dueAmount;
    const retryDelaysMs = [300, 500, 800, 1200, 1800, 2500];
    for (var i = 0; i <= retryDelaysMs.length; i++) {
      try {
        driver = await _repository.getProfile();
        error = null;
        notifyListeners();
        onUpdated?.call(driver);
        // A recharge either credits the wallet OR clears the outstanding due
        // (sometimes without changing the balance) — stop as soon as either
        // moves, so the UI reflects it promptly.
        if (driver?.walletBalance != beforeBalance ||
            driver?.dueAmount != beforeDue) {
          break;
        }
      } on ApiException catch (e) {
        error = e.message;
        notifyListeners();
      }
      if (i < retryDelaysMs.length) {
        await Future.delayed(Duration(milliseconds: retryDelaysMs[i]));
      }
    }
  }

  Future<bool> updateProfile({String? name, String? email, File? avatar}) =>
      _run(
        () =>
            _repository.updateProfile(name: name, email: email, avatar: avatar),
      );

  Future<void> loadEmergencyContact() async {
    loadingContact = true;
    notifyListeners();
    try {
      emergencyContact = await _repository.getEmergencyContact();
      error = null;
    } on ApiException catch (e) {
      error = e.message;
    }
    loadingContact = false;
    notifyListeners();
  }

  Future<bool> updateEmergencyContact({
    required String name,
    required String phone,
    String? relation,
  }) => _run(
    () => _repository.updateEmergencyContact(
      name: name,
      phone: phone,
      relation: relation,
    ),
  );

  Future<bool> updateWithdrawalInfo({
    required String method,
    required String account,
  }) => _run(
    () => _repository.updateWithdrawalInfo(method: method, account: account),
  );

  Future<bool> deleteAccount(String? reason) async {
    // Block deletion if there is outstanding due.
    final due = double.tryParse(driver?.dueAmount ?? '0') ?? 0;
    if (due > 0) {
      error = 'Outstanding due of $due must be cleared before deleting.';
      notifyListeners();
      return false;
    }
    // Unlike other mutations we must NOT reload the profile afterwards: the
    // account is gone, so /driver/profile would 401 and trigger a spurious
    // unauthorized redirect. The caller handles logout + navigation.
    submitting = true;
    error = null;
    notifyListeners();
    try {
      final ok = await _repository.deleteAccount(reason);
      submitting = false;
      notifyListeners();
      return ok;
    } on ApiException catch (e) {
      error = e.message;
      submitting = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> logout() => _authRepository.logout();

  Future<bool> _run(Future<bool> Function() action) async {
    submitting = true;
    error = null;
    notifyListeners();
    try {
      final ok = await action();
      submitting = false;
      if (ok) await loadProfile();
      notifyListeners();
      return ok;
    } on ApiException catch (e) {
      error = e.message;
      submitting = false;
      notifyListeners();
      return false;
    }
  }
}
