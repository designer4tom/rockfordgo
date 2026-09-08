class ReferralModel {
  final String code;
  final int totalReferred;
  final String totalEarned;
  final List<String> referredUsers;
  final String? howItWorks;

  ReferralModel({
    this.code = '',
    this.totalReferred = 0,
    this.totalEarned = '0.00',
    this.referredUsers = const [],
    this.howItWorks,
  });

  factory ReferralModel.fromJson(Map<String, dynamic> json) {
    return ReferralModel(
      code: json['code'] ?? json['referral_code'] ?? '',
      totalReferred: json['total_referred'] ?? 0,
      totalEarned: (json['total_earned'] ?? '0.00').toString(),
      referredUsers: (json['referred_users'] as List? ?? [])
          .map((e) => (e is Map ? e['name'] : e).toString())
          .toList(),
      howItWorks: json['how_it_works'],
    );
  }
}
