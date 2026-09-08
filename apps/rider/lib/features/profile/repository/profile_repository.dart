import 'dart:io';

import '../../auth/model/user_model.dart';

abstract class ProfileRepository {
  Future<UserModel> getProfile();

  Future<UserModel> updateProfile({String? name, String? email, File? avatar});

  Future<void> deleteAccount(String? reason);

  Future<void> updateEmergencyContact({
    required String name,
    required String phone,
  });
}
