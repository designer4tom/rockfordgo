/// A withdrawal method from the backend (/withdrawal-methods). The [code] is
/// what the withdrawal-request API validates against — NOT a display label.
class WithdrawalMethodModel {
  final String code;
  final String name;
  final String? instructions;

  WithdrawalMethodModel({
    required this.code,
    required this.name,
    this.instructions,
  });

  factory WithdrawalMethodModel.fromJson(Map<String, dynamic> json) =>
      WithdrawalMethodModel(
        code: (json['code'] ?? json['id'] ?? '').toString(),
        name: (json['name'] ?? json['title'] ?? '').toString(),
        instructions: json['instructions']?.toString(),
      );
}
