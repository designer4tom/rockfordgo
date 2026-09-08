class WithdrawalModel {
  final int id;
  final String amount;
  final String method; // bkash / nagad / bank
  final String accountNumber;
  final String status; // pending / approved / rejected
  final String? note;
  final String createdAt;

  WithdrawalModel({
    required this.id,
    this.amount = '0.00',
    this.method = '',
    this.accountNumber = '',
    this.status = 'pending',
    this.note,
    this.createdAt = '',
  });

  factory WithdrawalModel.fromJson(Map<String, dynamic> json) {
    return WithdrawalModel(
      id: json['id'] ?? 0,
      amount: (json['amount'] ?? '0.00').toString(),
      method: json['method'] ?? '',
      accountNumber:
          (json['account_number'] ?? json['account'] ?? '').toString(),
      status: json['status'] ?? 'pending',
      note: json['note'] ?? json['admin_note'],
      createdAt: json['created_at'] ?? '',
    );
  }
}
