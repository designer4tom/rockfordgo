import 'dart:io';

import 'package:flutter/material.dart';

import '../../../core/network/api_exception.dart';
import '../../auth/model/user_model.dart';
import '../repository/profile_repository.dart';

class ProfileProvider extends ChangeNotifier {
  final ProfileRepository _repository;

  ProfileProvider(this._repository);

  UserModel? user;
  bool isLoading = false;
  String? error;

  /// Loyalty / reward points. Static placeholder until the rewards feature
  /// (backend + API) is built; UI reads this so wiring it later is trivial.
  int rewardPoints = 0;

  Future<void> loadProfile() async {
    try {
      user = await _repository.getProfile();
      notifyListeners();
    } on ApiException catch (e) {
      error = e.message;
      notifyListeners();
    }
  }

  Future<bool> updateProfile({String? name, String? email, File? avatar}) async {
    isLoading = true;
    error = null;
    notifyListeners();
    try {
      user = await _repository.updateProfile(
        name: name,
        email: email,
        avatar: avatar,
      );
      isLoading = false;
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      error = e.message;
      isLoading = false;
      notifyListeners();
      return false;
    }
  }

  /// HTTP status of the last failed deleteAccount call. 422 means deletion is
  /// blocked by a server precondition (outstanding due / ongoing order).
  int? deleteErrorStatus;

  Future<bool> deleteAccount(String? reason) async {
    deleteErrorStatus = null;
    try {
      await _repository.deleteAccount(reason);
      return true;
    } on ApiException catch (e) {
      error = e.message;
      deleteErrorStatus = e.statusCode;
      notifyListeners();
      return false;
    }
  }

  Future<bool> updateEmergencyContact({
    required String name,
    required String phone,
  }) async {
    try {
      await _repository.updateEmergencyContact(name: name, phone: phone);
      return true;
    } on ApiException catch (e) {
      error = e.message;
      notifyListeners();
      return false;
    }
  }
}
