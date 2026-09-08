import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/services/config_service.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../model/withdrawal_method_model.dart';
import '../../model/withdrawal_model.dart';
import '../../provider/wallet_provider.dart';

class WithdrawalScreen extends StatefulWidget {
  const WithdrawalScreen({super.key});

  @override
  State<WithdrawalScreen> createState() => _WithdrawalScreenState();
}

class _WithdrawalScreenState extends State<WithdrawalScreen> {
  final _formKey = GlobalKey<FormState>();
  final _amount = TextEditingController();
  final _account = TextEditingController();
  String _method = 'bkash';

  /// Used until `/withdrawal-methods` responds (or if it returns nothing).
  static final List<WithdrawalMethodModel> _fallbackMethods = [
    WithdrawalMethodModel(code: 'bkash', name: 'bKash'),
    WithdrawalMethodModel(code: 'nagad', name: 'Nagad'),
    WithdrawalMethodModel(code: 'bank', name: 'Bank Transfer'),
  ];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final p = context.read<WalletProvider>();
      p.loadBalance();
      p.loadWithdrawalHistory();
      p.loadWithdrawalMethods();
    });
  }

  @override
  void dispose() {
    _amount.dispose();
    _account.dispose();
    super.dispose();
  }

  double get _min => ConfigService.getCached().userWithdrawalMinimum;

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    Helpers.dismissKeyboard(context);
    final provider = context.read<WalletProvider>();
    // Use the effective selected code (guard against a default that's not in
    // the API-provided list).
    final available = provider.withdrawalMethods.isNotEmpty
        ? provider.withdrawalMethods
        : _fallbackMethods;
    final method = available.any((m) => m.code == _method)
        ? _method
        : available.first.code;
    final ok = await provider.requestWithdrawal(
      amount: double.parse(_amount.text.trim()),
      method: method,
      account: _account.text.trim(),
    );
    if (!mounted) return;
    if (ok) {
      SnackbarHelper.showSuccess(context, 'wallet.withdraw_submitted'.tr());
      await provider.loadWithdrawalHistory();
      _amount.clear();
      _account.clear();
    } else {
      SnackbarHelper.showError(
          context, provider.error ?? 'wallet.withdraw_failed'.tr());
    }
  }

  @override
  Widget build(BuildContext context) {
    final wallet = context.watch<WalletProvider>();
    final theme = Theme.of(context);
    final balance = wallet.wallet?.balanceValue ?? 0;

    // Methods from `/withdrawal-methods` (fallback to built-in until loaded).
    final methods = wallet.withdrawalMethods.isNotEmpty
        ? wallet.withdrawalMethods
        : _fallbackMethods;
    final selectedCode =
        methods.any((m) => m.code == _method) ? _method : methods.first.code;
    final selectedMethod =
        methods.firstWhere((m) => m.code == selectedCode);

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      appBar: AppBar(title: Text('wallet.withdraw'.tr())),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Available balance
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [AppColors.primaryDark, AppColors.primary],
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('wallet.available_balance'.tr(),
                    style: const TextStyle(color: Colors.white70)),
                const SizedBox(height: 4),
                Text(Helpers.currency(balance),
                    style: const TextStyle(
                        color: Colors.white,
                        fontSize: 26,
                        fontWeight: FontWeight.bold)),
              ],
            ),
          ),
          const SizedBox(height: 24),
          Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('wallet.amount'.tr(),
                    style: const TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 8),
                TextFormField(
                  controller: _amount,
                  keyboardType:
                      const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: [
                    FilteringTextInputFormatter.allow(RegExp(r'[0-9.]')),
                  ],
                  decoration: InputDecoration(
                    prefixText: '${Helpers.currencySymbol} ',
                    hintText: 'wallet.enter_amount'.tr(),
                  ),
                  validator: (v) {
                    final amt = double.tryParse((v ?? '').trim());
                    if (amt == null || amt <= 0) {
                      return 'wallet.enter_valid_amount'.tr();
                    }
                    if (amt < _min) {
                      return 'wallet.min_withdraw'.tr(namedArgs: {
                        'amount': Helpers.currency(_min),
                      });
                    }
                    if (amt > balance) {
                      return 'wallet.insufficient_balance'.tr();
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 20),
                Text('wallet.method'.tr(),
                    style: const TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 8),
                ...methods.map((m) {
                  final selected = m.code == selectedCode;
                  return InkWell(
                    onTap: () => setState(() => _method = m.code),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 6),
                      child: Row(
                        children: [
                          Icon(
                            selected
                                ? Icons.radio_button_checked
                                : Icons.radio_button_unchecked,
                            color: selected ? AppColors.primary : null,
                          ),
                          const SizedBox(width: 12),
                          Text(m.name.isNotEmpty ? m.name : m.code),
                        ],
                      ),
                    ),
                  );
                }),
                const SizedBox(height: 12),
                Text('wallet.account_number'.tr(),
                    style: const TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 8),
                TextFormField(
                  controller: _account,
                  keyboardType: TextInputType.text,
                  decoration: InputDecoration(
                    // Per-method guidance from the API (e.g. "Enter your
                    // bKash account number").
                    hintText: selectedMethod.instructions.isNotEmpty
                        ? selectedMethod.instructions
                        : 'wallet.enter_account'.tr(),
                  ),
                  validator: (v) => (v == null || v.trim().isEmpty)
                      ? 'wallet.account_required'.tr()
                      : null,
                ),
                const SizedBox(height: 24),
                CustomButton(
                  text: 'wallet.request_withdrawal'.tr(),
                  isLoading: wallet.isSubmitting,
                  onPressed: _submit,
                ),
              ],
            ),
          ),
          const SizedBox(height: 28),
          Text('wallet.withdrawal_history'.tr(),
              style: theme.textTheme.titleMedium),
          const SizedBox(height: 8),
          if (wallet.isLoadingWithdrawals)
            const Padding(
                padding: EdgeInsets.all(16),
                child: Center(child: CircularProgressIndicator()))
          else if (wallet.withdrawals.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 16),
              child: Text('wallet.no_withdrawals'.tr(),
                  style: const TextStyle(color: AppColors.textSecondary)),
            )
          else
            ...wallet.withdrawals.map((w) => _historyTile(theme, w)),
        ],
      ),
    );
  }

  /// Resolve a method code to its display name via the loaded methods,
  /// falling back to the built-in list and finally the raw code.
  String _methodName(String code) {
    final all = [
      ...context.read<WalletProvider>().withdrawalMethods,
      ..._fallbackMethods,
    ];
    final match = all.where((m) => m.code == code);
    if (match.isNotEmpty && match.first.name.isNotEmpty) {
      return match.first.name;
    }
    return code;
  }

  Widget _historyTile(ThemeData theme, WithdrawalModel w) {
    final amount = double.tryParse(w.amount) ?? 0;
    final (color, label) = switch (w.status) {
      'approved' => (AppColors.success, 'wallet.status_approved'.tr()),
      'rejected' => (AppColors.danger, 'wallet.status_rejected'.tr()),
      _ => (AppColors.warning, 'wallet.status_pending'.tr()),
    };
    final dt = DateTime.tryParse(w.createdAt);
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: theme.dividerColor),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(Helpers.currency(amount),
                    style: const TextStyle(fontWeight: FontWeight.bold)),
                const SizedBox(height: 2),
                Text(
                  '${_methodName(w.method)} • ${w.accountNumber}',
                  style: const TextStyle(
                      fontSize: 12, color: AppColors.textSecondary),
                ),
                if (dt != null) ...[
                  const SizedBox(height: 2),
                  Text(Helpers.formatDateTime(dt.toLocal()),
                      style: const TextStyle(
                          fontSize: 11, color: AppColors.textSecondary)),
                ],
              ],
            ),
          ),
          Container(
            padding:
                const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(label,
                style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: color)),
          ),
        ],
      ),
    );
  }
}
