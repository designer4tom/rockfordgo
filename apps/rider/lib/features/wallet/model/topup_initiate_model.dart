class TopupInitiateModel {
  final int topupId;
  final String amount;
  final String paymentMethod;
  final String paymentUrl;
  final String status;

  TopupInitiateModel({
    this.topupId = 0,
    this.amount = '0.00',
    this.paymentMethod = '',
    this.paymentUrl = '',
    this.status = 'pending',
  });

  factory TopupInitiateModel.fromJson(Map<String, dynamic> json) {
    return TopupInitiateModel(
      topupId: json['topup_id'] ?? 0,
      amount: (json['amount'] ?? '0.00').toString(),
      paymentMethod: json['payment_method'] ?? '',
      paymentUrl: json['payment_url'] ?? '',
      status: json['status'] ?? 'pending',
    );
  }
}
