import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/services/config_service.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/widgets/custom_textfield.dart';
import '../../provider/wallet_provider.dart';

class WithdrawalScreen extends StatefulWidget {
  const WithdrawalScreen({super.key});

  @override
  State<WithdrawalScreen> createState() => _WithdrawalScreenState();
}

class _WithdrawalScreenState extends State<WithdrawalScreen> {
  final _formKey = GlobalKey<FormState>();
  final _amountController = TextEditingController();
  final _accountController = TextEditingController();
  String? _method; // selected method code (from backend)

  @override
  void initState() {
    super.initState();
    final w = context.read<WalletProvider>().wallet;
    _accountController.text = w?.withdrawalAccount ?? '';
    // Methods (and their valid codes) come from the backend — never hardcode.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<WalletProvider>().loadWithdrawalMethods();
    });
  }

  @override
  void dispose() {
    _amountController.dispose();
    _accountController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final p = context.read<WalletProvider>();
    if (_method == null || _method!.isEmpty) {
      AppSnackbar.error(context, 'wallet.select_method'.tr());
      return;
    }
    if ((p.wallet?.withdrawalAccount ?? '').isEmpty &&
        _accountController.text.trim().isEmpty) {
      AppSnackbar.error(context, 'wallet.add_withdrawal_account_first'.tr());
      return;
    }
    final amount = double.tryParse(_amountController.text) ?? 0;
    // Min withdrawal comes from /config (admin), fallback to wallet value.
    final min = double.tryParse(
            ConfigService.cachedOrNull?.minWithdrawalAmount ??
                p.wallet?.minWithdrawal ??
                '0') ??
        0;
    if (amount < min) {
      AppSnackbar.error(context,
          '${'wallet.minimum_withdrawal_is'.tr()} ${Helpers.money(min)}');
      return;
    }
    if (amount > (p.wallet?.balanceValue ?? 0)) {
      AppSnackbar.error(context, 'wallet.amount_exceeds_balance'.tr());
      return;
    }
    final ok = await p.requestWithdrawal(
        amount, _method!, _accountController.text.trim());
    if (!mounted) return;
    if (ok) {
      AppSnackbar.success(context, 'wallet.withdrawal_requested'.tr());
      context.pop();
    } else {
      AppSnackbar.error(context, p.error ?? 'wallet.withdrawal_failed'.tr());
    }
  }

  Widget _methodSelector(WalletProvider p) {
    if (p.loadingMethods && p.withdrawalMethods.isEmpty) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 16),
        child: Center(child: CircularProgressIndicator()),
      );
    }
    if (p.withdrawalMethods.isEmpty) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Text('wallet.no_methods'.tr(),
            style: TextStyle(color: Theme.of(context).hintColor)),
      );
    }
    // Default to the first valid code once methods are available.
    final codes = p.withdrawalMethods.map((m) => m.code).toList();
    if (_method == null || !codes.contains(_method)) {
      _method = codes.first;
    }
    return Column(
      children: p.withdrawalMethods
          .map(
            (m) => RadioListTile<String>(
              value: m.code,
              // ignore: deprecated_member_use
              groupValue: _method,
              // ignore: deprecated_member_use
              onChanged: (v) => setState(() => _method = v),
              title: Text(m.name),
              subtitle: (m.instructions != null && m.instructions!.isNotEmpty)
                  ? Text(m.instructions!)
                  : null,
              activeColor: AppColors.primary,
              contentPadding: EdgeInsets.zero,
            ),
          )
          .toList(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<WalletProvider>();
    final balance = p.wallet?.balanceValue ?? 0;

    return Scaffold(
      appBar: AppBar(title: Text('wallet.withdraw'.tr())),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Column(
                  children: [
                    Text('wallet.available_balance'.tr(),
                        style: TextStyle(color: Theme.of(context).hintColor)),
                    const SizedBox(height: 4),
                    Text(Helpers.money(balance),
                        style: const TextStyle(
                            fontSize: 24, fontWeight: FontWeight.bold)),
                  ],
                ),
              ),
              const SizedBox(height: 20),
              if ((p.wallet?.withdrawalAccount ?? '').isEmpty)
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: AppColors.warning.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.info_outline, color: AppColors.warning),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text('wallet.add_withdrawal_account_first'.tr()),
                      ),
                      TextButton(
                        onPressed: () => context.push('/withdrawal-account'),
                        child: Text('common.add'.tr()),
                      ),
                    ],
                  ),
                ),
              CustomTextField(
                controller: _amountController,
                label: 'wallet.amount'.tr(),
                hint: 'wallet.enter_amount'.tr(),
                prefixIcon: Icons.payments_outlined,
                keyboardType: TextInputType.number,
                inputFormatters: [
                  FilteringTextInputFormatter.allow(RegExp(r'[0-9.]')),
                ],
                validator: (v) =>
                    (v == null || v.isEmpty) ? 'wallet.enter_an_amount'.tr() : null,
              ),
              const SizedBox(height: 16),
              Text('wallet.method'.tr(),
                  style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: Theme.of(context).hintColor)),
              const SizedBox(height: 6),
              _methodSelector(p),
              const SizedBox(height: 16),
              CustomTextField(
                controller: _accountController,
                label: 'wallet.account'.tr(),
                hint: 'wallet.account_number'.tr(),
                prefixIcon: Icons.account_balance_outlined,
                validator: (v) =>
                    (v == null || v.isEmpty) ? 'wallet.enter_account'.tr() : null,
              ),
              const SizedBox(height: 12),
              Text(
                'wallet.withdrawal_approval_note'.tr(),
                style: TextStyle(color: Theme.of(context).hintColor),
              ),
              const SizedBox(height: 20),
              CustomButton(
                label: 'wallet.request_withdrawal'.tr(),
                loading: p.submitting,
                onPressed: _submit,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
