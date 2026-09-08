class TransactionModel {
  final int id;
  final String type; // credit/debit
  final String category;
  final String categoryLabel;
  final String amount;
  final String balanceAfter;
  final String? note;
  final String? orderNumber;
  final String createdAt;

  TransactionModel({
    required this.id,
    required this.type,
    this.category = '',
    this.categoryLabel = '',
    this.amount = '0.00',
    this.balanceAfter = '0.00',
    this.note,
    this.orderNumber,
    this.createdAt = '',
  });

  factory TransactionModel.fromJson(Map<String, dynamic> json) {
    return TransactionModel(
      id: json['id'] ?? 0,
      type: json['type'] ?? 'credit',
      category: json['category'] ?? '',
      categoryLabel: json['category_label'] ?? json['category'] ?? '',
      amount: (json['amount'] ?? '0.00').toString(),
      balanceAfter: (json['balance_after'] ?? '0.00').toString(),
      note: json['note'],
      orderNumber: json['order_number'],
      createdAt: json['created_at'] ?? '',
    );
  }

  bool get isCredit => type == 'credit';
}
