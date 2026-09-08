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
import '../../../../core/routing/route_names.dart';
import '../../../home/provider/home_provider.dart';
import '../../../profile/provider/profile_provider.dart';
import '../../../wallet/provider/wallet_provider.dart';
import '../../provider/recharge_provider.dart';
import '../widgets/payment_method_icon.dart';

class RechargeScreen extends StatefulWidget {
  const RechargeScreen({super.key});

  @override
  State<RechargeScreen> createState() => _RechargeScreenState();
}

class _RechargeScreenState extends State<RechargeScreen> {
  final _amountController = TextEditingController();
  static const _quick = [100, 200, 500, 1000];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final p = context.read<RechargeProvider>();
      p.loadPaymentMethods();
      p.loadHistory();
    });
  }

  @override
  void dispose() {
    _amountController.dispose();
    super.dispose();
  }

  void _setAmount(int value) {
    _amountController.text = value.toString();
    context.read<RechargeProvider>().setAmount(value.toDouble());
  }

  Future<void> _proceed() async {
    final p = context.read<RechargeProvider>();
    final amount = double.tryParse(_amountController.text) ?? 0;
    p.setAmount(amount);
    if (!p.canProceed) {
      AppSnackbar.error(context, 'recharge.enter_amount_select_method'.tr());
      return;
    }
    // Minimum recharge amount comes from /config (admin).
    final min =
        double.tryParse(ConfigService.cachedOrNull?.minRechargeAmount ?? '0') ??
            0;
    if (amount < min) {
      AppSnackbar.error(context,
          '${'recharge.minimum_is'.tr()} ${Helpers.money(min)}');
      return;
    }
    final url = await p.initiateRecharge();
    if (!mounted) return;
    if (url == null || url.isEmpty) {
      AppSnackbar.error(context, p.error ?? 'recharge.could_not_start_payment'.tr());
      return;
    }
    // Open the in-app webview; it returns 'success' or 'cancelled'.
    final result =
        await context.push<String>(RouteNames.rechargeWebview, extra: url);
    if (!mounted) return;
    if (result == 'success') {
      // The gateway credits the wallet via an async server callback, so the new
      // balance may not be live yet. Poll until it changes (captures the
      // current balance as the baseline before refreshing).
      final wallet = context.read<WalletProvider>();
      wallet.refreshAfterRecharge(previousBalance: wallet.wallet?.balance);
      // Refresh wallet so the new balance / cleared due shows.
      context.read<WalletProvider>().loadWallet();
      p.loadHistory();
      // The balance is also shown by the profile summary (ProfileProvider) and
      // the drawer header card (HomeProvider). Poll the profile the same way and
      // push each fresh driver into Home so both update live with the recharge.
      final profile = context.read<ProfileProvider>();
      final home = context.read<HomeProvider>();
      profile.refreshAfterRecharge(
        previousBalance: profile.driver?.walletBalance,
        previousDue: profile.driver?.dueAmount,
        onUpdated: home.setDriver,
      );
      AppSnackbar.success(context, 'recharge.recharge_successful'.tr());
      context.pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    final wallet = context.watch<WalletProvider>().wallet;
    final p = context.watch<RechargeProvider>();
    final due = wallet?.dueValue ?? 0;

    return Scaffold(
      appBar: AppBar(title: Text('recharge.recharge'.tr())),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Balance + due
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('wallet.balance'.tr(),
                    style: TextStyle(color: Theme.of(context).hintColor)),
                const SizedBox(height: 2),
                Text(Helpers.money(wallet?.balanceValue ?? 0),
                    style: const TextStyle(
                        fontSize: 24, fontWeight: FontWeight.bold)),
                if (due > 0) ...[
                  const SizedBox(height: 10),
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppColors.danger.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.warning_amber_rounded,
                            color: AppColors.danger, size: 18),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            'recharge.due_will_clear_first'
                                .tr(namedArgs: {'amount': Helpers.money(due)}),
                            style: const TextStyle(color: AppColors.danger),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Amount
          Text('recharge.amount'.tr(),
              style: const TextStyle(fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _quick
                .map((v) => ChoiceChip(
                      label: Text(Helpers.money(v.toDouble())),
                      selected: (double.tryParse(_amountController.text) ?? 0) ==
                          v.toDouble(),
                      onSelected: (_) => _setAmount(v),
                      selectedColor: AppColors.primary.withValues(alpha: 0.2),
                    ))
                .toList(),
          ),
          const SizedBox(height: 12),
          CustomTextField(
            controller: _amountController,
            label: 'recharge.custom_amount'.tr(),
            hint: 'recharge.enter_amount'.tr(),
            prefixIcon: Icons.payments_outlined,
            keyboardType: TextInputType.number,
            inputFormatters: [
              FilteringTextInputFormatter.allow(RegExp(r'[0-9.]')),
            ],
            onChanged: (v) =>
                context.read<RechargeProvider>().setAmount(double.tryParse(v) ?? 0),
          ),
          const SizedBox(height: 20),

          // Payment methods
          Text('recharge.payment_method'.tr(),
              style: const TextStyle(fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          if (p.loading && p.paymentMethods.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 16),
              child: Center(child: CircularProgressIndicator()),
            )
          else
            ...p.paymentMethods.map(
              (m) => RadioListTile<String>(
                value: m.code,
                // ignore: deprecated_member_use
                groupValue: p.selectedMethod,
                // ignore: deprecated_member_use
                onChanged: (v) => p.selectMethod(v!),
                title: Text(m.name),
                secondary: PaymentMethodIcon(code: m.code, iconUrl: m.icon),
                activeColor: AppColors.primary,
                contentPadding: EdgeInsets.zero,
              ),
            ),
          const SizedBox(height: 24),
          SafeArea(
            child: CustomButton(
              label: 'recharge.proceed_to_pay'.tr(),
              loading: p.submitting,
              onPressed: _proceed,
            ),
          ),
        ],
      ),
    );
  }
}
