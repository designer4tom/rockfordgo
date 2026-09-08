import '../../../core/utils/helpers.dart';

class WithdrawalModel {
  final int id;
  final String amount;
  final String method;
  final String account;
  final String status; // pending | approved | rejected
  final String? rejectionReason;
  final DateTime? createdAt;

  WithdrawalModel({
    required this.id,
    required this.amount,
    required this.method,
    required this.account,
    required this.status,
    this.rejectionReason,
    this.createdAt,
  });

  factory WithdrawalModel.fromJson(Map<String, dynamic> json) =>
      WithdrawalModel(
        id: json['id'] is int
            ? json['id']
            : int.tryParse(json['id']?.toString() ?? '') ?? 0,
        amount: (json['amount'] ?? '0').toString(),
        method: (json['method'] ?? '').toString(),
        account: (json['account'] ?? json['account_number'] ?? '').toString(),
        status: (json['status'] ?? 'pending').toString(),
        rejectionReason: json['rejection_reason']?.toString(),
        createdAt: Helpers.tryParse(json['created_at'] ?? json['date']),
      );
}
