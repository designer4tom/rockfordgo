class WithdrawalMethodModel {
  final String code;
  final String name;
  final String instructions;

  WithdrawalMethodModel({
    required this.code,
    this.name = '',
    this.instructions = '',
  });

  factory WithdrawalMethodModel.fromJson(Map<String, dynamic> json) {
    return WithdrawalMethodModel(
      code: json['code'] ?? '',
      name: json['name'] ?? '',
      instructions: json['instructions'] ?? '',
    );
  }
}
