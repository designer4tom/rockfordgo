class WalletModel {
  final String balance;
  final String currency;
  final String currencySymbol;
  final String dueAmount;
  final String dueLimit;
  final bool canPlaceCod;

  WalletModel({
    this.balance = '0.00',
    this.currency = 'BDT',
    this.currencySymbol = '৳',
    this.dueAmount = '0.00',
    this.dueLimit = '0.00',
    this.canPlaceCod = true,
  });

  factory WalletModel.fromJson(Map<String, dynamic> json) {
    return WalletModel(
      balance: (json['balance'] ?? '0.00').toString(),
      currency: json['currency'] ?? 'BDT',
      currencySymbol: json['currency_symbol'] ?? '৳',
      dueAmount: (json['due_amount'] ?? '0.00').toString(),
      dueLimit: (json['due_limit'] ?? '0.00').toString(),
      canPlaceCod: json['can_place_cod'] ?? true,
    );
  }

  double get balanceValue => double.tryParse(balance) ?? 0;

  double get dueValue => double.tryParse(dueAmount) ?? 0;

  double get dueLimitValue => double.tryParse(dueLimit) ?? 0;

  bool get hasDue => dueValue > 0;
}
