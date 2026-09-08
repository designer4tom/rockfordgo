import '../../../core/utils/helpers.dart';

/// Result of initiating a recharge — carries the payment_url to load in webview.
class RechargeInitiateModel {
  final int? rechargeId;
  final String amount;
  final String paymentMethod;
  final String? paymentUrl;
  final String status;

  RechargeInitiateModel({
    this.rechargeId,
    this.amount = '',
    this.paymentMethod = '',
    this.paymentUrl,
    this.status = '',
  });

  factory RechargeInitiateModel.fromJson(Map<String, dynamic> json) {
    final data = (json['data'] is Map ? json['data'] : json) as Map;
    return RechargeInitiateModel(
      rechargeId: data['recharge_id'] is int
          ? data['recharge_id']
          : int.tryParse((data['recharge_id'] ?? data['id'] ?? '').toString()),
      amount: (data['amount'] ?? '').toString(),
      paymentMethod: (data['payment_method'] ?? data['method'] ?? '').toString(),
      paymentUrl: (data['payment_url'] ?? data['url'])?.toString(),
      status: (data['status'] ?? '').toString(),
    );
  }
}

/// A past recharge entry for history.
class RechargeModel {
  final int id;
  final String amount;
  final String method;
  final String status; // pending | success | failed
  final DateTime? createdAt;

  RechargeModel({
    required this.id,
    required this.amount,
    required this.method,
    required this.status,
    this.createdAt,
  });

  factory RechargeModel.fromJson(Map<String, dynamic> json) => RechargeModel(
        id: json['id'] is int
            ? json['id']
            : int.tryParse(json['id']?.toString() ?? '') ?? 0,
        amount: (json['amount'] ?? '0').toString(),
        method: (json['method'] ?? '').toString(),
        status: (json['status'] ?? 'pending').toString(),
        createdAt: Helpers.tryParse(json['created_at'] ?? json['date']),
      );
}
