class WalletModel {
  final String balance;
  final String due;
  final String dueLimit;
  final bool canAccept;
  final String minWithdrawal;
  final String withdrawalMethod;
  final String withdrawalAccount;

  WalletModel({
    required this.balance,
    required this.due,
    required this.dueLimit,
    required this.canAccept,
    required this.minWithdrawal,
    required this.withdrawalMethod,
    required this.withdrawalAccount,
  });

  double get balanceValue => double.tryParse(balance) ?? 0;
  double get dueValue => double.tryParse(due) ?? 0;
  double get dueLimitValue => double.tryParse(dueLimit) ?? 0;
  bool get dueExceeded => dueLimitValue > 0 && dueValue >= dueLimitValue;

  factory WalletModel.fromJson(Map<String, dynamic> json) => WalletModel(
        balance: (json['balance'] ?? json['wallet_balance'] ?? '0').toString(),
        due: (json['due'] ?? json['due_amount'] ?? '0').toString(),
        dueLimit: (json['due_limit'] ?? json['max_due'] ?? '0').toString(),
        canAccept: json['can_accept'] == null
            ? true
            : (json['can_accept'] == true || json['can_accept'] == 1),
        minWithdrawal:
            (json['min_withdrawal'] ?? json['minimum_withdrawal'] ?? '0')
                .toString(),
        withdrawalMethod:
            (json['withdrawal_method'] ?? 'bkash').toString(),
        withdrawalAccount: (json['withdrawal_account'] ?? '').toString(),
      );
}
