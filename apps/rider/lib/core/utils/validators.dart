import '../services/config_service.dart';

class Validators {
  /// Phone validation driven by `/config` (phone_regex or min/max length),
  /// so it adapts per country instead of hard-coding a single format.
  static String? phone(String? value) {
    if (value == null || value.isEmpty) return 'Phone number is required';
    final digits = value.replaceAll(RegExp(r'\D'), '');
    if (!ConfigService.getCached().isValidPhone(digits)) {
      return 'Enter a valid phone number';
    }
    return null;
  }

  static String? required(String? value, [String field = 'This field']) {
    if (value == null || value.trim().isEmpty) return '$field is required';
    return null;
  }

  static String? email(String? value) {
    if (value == null || value.isEmpty) return null; // optional
    final regex = RegExp(r'^[\w.\-]+@([\w\-]+\.)+[\w\-]{2,4}$');
    if (!regex.hasMatch(value)) return 'Enter a valid email';
    return null;
  }

  static String? name(String? value) {
    if (value == null || value.trim().isEmpty) return 'Name is required';
    if (value.trim().length < 2) return 'Name is too short';
    return null;
  }

  static String? otp(String? value, [int length = 6]) {
    if (value == null || value.isEmpty) return 'OTP is required';
    if (value.length != length) return 'Enter the $length-digit code';
    return null;
  }
}
