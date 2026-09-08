import '../../../core/utils/helpers.dart';

class TransactionModel {
  final int id;
  final String type; // credit | debit
  final String amount;
  final String description;
  final DateTime? createdAt;

  TransactionModel({
    required this.id,
    required this.type,
    required this.amount,
    required this.description,
    this.createdAt,
  });

  bool get isCredit => type.toLowerCase() == 'credit';

  factory TransactionModel.fromJson(Map<String, dynamic> json) =>
      TransactionModel(
        id: json['id'] is int
            ? json['id']
            : int.tryParse(json['id']?.toString() ?? '') ?? 0,
        type: (json['type'] ?? 'credit').toString(),
        amount: (json['amount'] ?? '0').toString(),
        description:
            (json['description'] ?? json['note'] ?? json['title'] ?? '')
                .toString(),
        createdAt: Helpers.tryParse(json['created_at'] ?? json['date']),
      );
}
