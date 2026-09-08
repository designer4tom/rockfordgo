class UserModel {
  final int id;
  final String name;
  final String phone;
  final String? email;
  final String? avatar;
  final String walletBalance;
  final String referralCode;
  final bool isActive;

  UserModel({
    required this.id,
    required this.name,
    required this.phone,
    this.email,
    this.avatar,
    this.walletBalance = '0.00',
    this.referralCode = '',
    this.isActive = true,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'],
      name: json['name'] ?? '',
      phone: json['phone'] ?? '',
      email: json['email'],
      avatar: json['avatar'],
      walletBalance: (json['wallet_balance'] ?? '0.00').toString(),
      referralCode: json['referral_code'] ?? '',
      isActive: json['is_active'] ?? true,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'phone': phone,
        'email': email,
        'avatar': avatar,
        'wallet_balance': walletBalance,
        'referral_code': referralCode,
        'is_active': isActive,
      };
}
