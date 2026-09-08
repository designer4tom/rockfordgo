import 'driver_model.dart';

/// Outcome of an OTP verification / status check, used to drive navigation.
enum AuthResult {
  approved,
  needsRegistration,
  pendingApproval,
  rejected,
  blocked,
  failed,
}

/// Parsed payload from verify-otp / status endpoints.
///
/// Backend contract:
///   { token, status, is_new_driver, registration_completed, rejection_reason,
///     driver: {...} }
class AuthResponseModel {
  final String? token;
  final DriverModel? driver;
  final String status; // pending | approved | rejected | blocked | suspended
  final bool isNewDriver;
  final bool registrationCompleted;
  final String? rejectionReason;
  final String message;

  AuthResponseModel({
    this.token,
    this.driver,
    required this.status,
    required this.isNewDriver,
    required this.registrationCompleted,
    this.rejectionReason,
    required this.message,
  });

  static bool _truthy(dynamic v) => v == true || v == 1 || v == '1';

  factory AuthResponseModel.fromJson(Map<String, dynamic> json) {
    final data = (json['data'] is Map ? json['data'] : json) as Map;
    final driverJson = data['driver'] ?? data['user'];
    final statusStr = (data['status'] ??
            (driverJson is Map ? driverJson['status'] : null) ??
            'pending')
        .toString();
    return AuthResponseModel(
      token: (data['token'] ?? data['access_token'])?.toString(),
      driver: driverJson is Map
          ? DriverModel.fromJson(driverJson.cast<String, dynamic>())
          : null,
      status: statusStr,
      isNewDriver: _truthy(data['is_new_driver']) || _truthy(data['is_new']),
      registrationCompleted: _truthy(data['registration_completed']),
      rejectionReason: (data['rejection_reason'] ??
              (driverJson is Map ? driverJson['rejection_reason'] : null))
          ?.toString(),
      message: (json['message'] ?? '').toString(),
    );
  }

  /// Map the server response to a navigation [AuthResult].
  ///
  /// Priority: blocked → rejected → approved → (incomplete registration) →
  /// pending approval.
  AuthResult toResult() {
    if (status == 'blocked' || status == 'suspended') return AuthResult.blocked;
    if (status == 'rejected') return AuthResult.rejected;
    if (status == 'approved') return AuthResult.approved;
    if (!registrationCompleted) return AuthResult.needsRegistration;
    return AuthResult.pendingApproval;
  }
}
