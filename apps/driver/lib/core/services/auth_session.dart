import '../storage/secure_storage.dart';

/// In-memory snapshot of auth state for synchronous router redirect decisions.
/// Persisted via [SecureStorage] (driver data) so it survives app restart.
class AuthSession {
  AuthSession._();
  static final AuthSession instance = AuthSession._();

  String? token;
  String status = 'pending';
  bool registrationCompleted = false;

  bool get isLoggedIn => token != null && token!.isNotEmpty;
  bool get isApproved => status == 'approved';
  bool get isRejected => status == 'rejected';
  bool get isBlocked => status == 'blocked' || status == 'suspended';

  /// Load persisted state at app start (call before runApp / in splash).
  Future<void> load() async {
    token = await SecureStorage.instance.getToken();
    final data = await SecureStorage.instance.getDriver();
    if (data != null) {
      status = (data['status'] ?? 'pending').toString();
      registrationCompleted = data['registration_completed'] == true;
    }
  }

  void update({
    String? token,
    String? status,
    bool? registrationCompleted,
  }) {
    if (token != null) this.token = token;
    if (status != null) this.status = status;
    if (registrationCompleted != null) {
      this.registrationCompleted = registrationCompleted;
    }
  }

  void clear() {
    token = null;
    status = 'pending';
    registrationCompleted = false;
  }
}
