class DueEntryModel {
  final int id;
  final String type; // added/paid
  final String amount;
  final String dueBefore;
  final String dueAfter;
  final String? note;
  final String? orderNumber;
  final String createdAt;

  DueEntryModel({
    required this.id,
    required this.type,
    this.amount = '0.00',
    this.dueBefore = '0.00',
    this.dueAfter = '0.00',
    this.note,
    this.orderNumber,
    this.createdAt = '',
  });

  factory DueEntryModel.fromJson(Map<String, dynamic> json) {
    return DueEntryModel(
      id: json['id'] ?? 0,
      type: json['type'] ?? 'added',
      amount: (json['amount'] ?? '0.00').toString(),
      dueBefore: (json['due_before'] ?? '0.00').toString(),
      dueAfter: (json['due_after'] ?? '0.00').toString(),
      note: json['note'],
      orderNumber: json['order_number'],
      createdAt: json['created_at'] ?? '',
    );
  }

  bool get isPaid => type == 'paid';
}
