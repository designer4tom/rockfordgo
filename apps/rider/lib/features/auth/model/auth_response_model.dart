import 'user_model.dart';

class AuthResponseModel {
  final String? token;
  final bool isNewUser;
  final String? tempToken;
  final UserModel? user;

  AuthResponseModel({
    this.token,
    this.isNewUser = false,
    this.tempToken,
    this.user,
  });

  factory AuthResponseModel.fromJson(Map<String, dynamic> json) {
    return AuthResponseModel(
      token: json['token'],
      isNewUser: json['is_new_user'] ?? false,
      tempToken: json['temp_token'],
      user: json['user'] != null
          ? UserModel.fromJson(Map<String, dynamic>.from(json['user']))
          : null,
    );
  }
}
