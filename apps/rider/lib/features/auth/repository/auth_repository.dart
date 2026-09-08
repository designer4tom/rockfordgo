import 'dart:io';

import '../../../core/network/api_response.dart';
import '../model/auth_response_model.dart';

abstract class AuthRepository {
  Future<ApiResponse> sendOtp(String phone);

  Future<AuthResponseModel> verifyOtp({
    required String phone,
    required String otp,
    String? name,
    String? fcmToken,
  });

  Future<AuthResponseModel> completeProfile({
    required String name,
    String? email,
    String? referralCode,
    File? avatar,
  });

  Future<void> logout();
}
