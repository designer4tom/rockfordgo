import 'package:cached_network_image/cached_network_image.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/theme/app_text_styles.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/widgets/primary_action_button.dart';
import '../../model/payment_method_model.dart';
import '../../provider/wallet_provider.dart';
import '../widgets/topup_amount_selector.dart';

class TopupScreen extends StatefulWidget {
  const TopupScreen({super.key});

  @override
  State<TopupScreen> createState() => _TopupScreenState();
}

class _TopupScreenState extends State<TopupScreen> {
  double? _amount;
  String? _method;
  final _customController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final p = context.read<WalletProvider>();
      p.loadBalance();
      p.loadPaymentMethods();
    });
  }

  @override
  void dispose() {
    _customController.dispose();
    super.dispose();
  }

  Future<void> _proceed() async {
    final amount = _amount;
    if (amount == null || amount <= 0) {
      SnackbarHelper.showError(context, 'wallet.invalid_amount'.tr());
      return;
    }
    if (_method == null) {
      SnackbarHelper.showError(context, 'wallet.select_payment_method'.tr());
      return;
    }
    final wallet = context.read<WalletProvider>();
    final url = await wallet.initiateAddMoney(amount, _method!);
    if (!mounted) return;
    if (url == null) {
      SnackbarHelper.showError(
          context, wallet.error ?? 'wallet.payment_init_failed'.tr());
      return;
    }
    final result = await context.push<String>('/wallet-webview', extra: url);
    if (!mounted) return;
    if (result == 'success') {
      final dueBefore = wallet.dueAmount;
      await wallet.refreshBalanceAfterTopup();
      await wallet.loadTransactions(refresh: true);
      if (!mounted) return;
      // A top-up pays any outstanding delivery-charge due first — tell the
      // user how much of it went there.
      final dueCleared = dueBefore - wallet.dueAmount;
      if (dueCleared > 0) {
        SnackbarHelper.showSuccess(
            context,
            'wallet.due_cleared_msg'
                .tr(namedArgs: {'amount': Helpers.currency(dueCleared)}));
      } else {
        SnackbarHelper.showSuccess(context, 'wallet.topup_success'.tr());
      }
      context.pop();
    }
    // cancelled → stay on this screen.
  }

  @override
  Widget build(BuildContext context) {
    final wallet = context.watch<WalletProvider>();
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      appBar: AppBar(title: Text('wallet.add_money'.tr())),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Current balance
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [AppColors.primaryDark, AppColors.primary],
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('home.wallet_balance'.tr(),
                    style: const TextStyle(color: Colors.white70)),
                const SizedBox(height: 4),
                Text(
                  Helpers.currency(wallet.wallet?.balanceValue ?? 0),
                  style: const TextStyle(
                      color: Colors.white,
                      fontSize: 26,
                      fontWeight: FontWeight.bold),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          Text('wallet.select_amount'.tr(), style: AppTextStyles.title),
          const SizedBox(height: 12),
          TopupAmountSelector(
            selected: _amount,
            onSelected: (a) => setState(() {
              _amount = a;
              // Reflect the chosen quick amount in the input field so it can
              // be edited further.
              final text = a == a.roundToDouble()
                  ? a.toInt().toString()
                  : a.toString();
              _customController.value = TextEditingValue(
                text: text,
                selection: TextSelection.collapsed(offset: text.length),
              );
            }),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _customController,
            keyboardType: TextInputType.number,
            decoration: InputDecoration(
              labelText: 'wallet.custom_amount'.tr(),
              prefixText: '${Helpers.currencySymbol} ',
            ),
            onChanged: (v) => setState(() => _amount = double.tryParse(v)),
          ),
          const SizedBox(height: 24),
          Text('wallet.method'.tr(), style: AppTextStyles.title),
          const SizedBox(height: 8),
          _paymentMethods(theme, wallet),
          const SizedBox(height: 24),
          PrimaryActionButton(
            label: 'wallet.proceed_pay'.tr(),
            isLoading: wallet.isInitiating,
            onPressed: _proceed,
          ),
        ],
      ),
    );
  }

  Widget _paymentMethods(ThemeData theme, WalletProvider wallet) {
    if (wallet.isLoadingPaymentMethods) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 12),
        child: Center(child: CircularProgressIndicator()),
      );
    }
    // An empty list is a real answer (backend has no active gateway), not a
    // loading state — rendering a spinner for it left the screen hanging.
    if (wallet.paymentMethods.isEmpty) {
      final failed = wallet.paymentMethodsError != null;
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 12),
        child: Column(
          children: [
            Text(
              failed
                  ? 'wallet.payment_methods_failed'.tr()
                  : 'wallet.no_payment_methods'.tr(),
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyMedium
                  ?.copyWith(color: theme.hintColor),
            ),
            if (failed)
              TextButton(
                onPressed: () =>
                    context.read<WalletProvider>().loadPaymentMethods(),
                child: Text('common.retry'.tr()),
              ),
          ],
        ),
      );
    }
    return Column(
      children: wallet.paymentMethods.map((m) {
        final selected = _method == m.id;
        return GestureDetector(
          onTap: () => setState(() => _method = m.id),
          child: Container(
            margin: const EdgeInsets.only(bottom: 10),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: selected
                  ? AppColors.primary.withValues(alpha: 0.06)
                  : theme.cardColor,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: selected ? AppColors.primary : theme.dividerColor,
                width: selected ? 1.5 : 1,
              ),
            ),
            child: Row(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(6),
                  // The backend's payment-methods list currently ships
                  // `icon: null` for every gateway, so `m.icon` is usually
                  // empty — CachedNetworkImage can't recover from an empty
                  // URL (it never reaches errorWidget), leaving a blank
                  // box. Skip straight to a name-based fallback icon
                  // whenever there's no real URL to try.
                  child: m.icon.isNotEmpty
                      ? CachedNetworkImage(
                          imageUrl: m.icon,
                          width: 36,
                          height: 36,
                          fit: BoxFit.contain,
                          errorWidget: (context, url, error) =>
                              _methodIcon(m),
                        )
                      : _methodIcon(m),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Text(m.name,
                      style: TextStyle(
                          fontWeight: FontWeight.w600,
                          color: theme.colorScheme.onSurface)),
                ),
                Icon(
                  selected
                      ? Icons.radio_button_checked
                      : Icons.radio_button_unchecked,
                  color: selected ? AppColors.primary : theme.dividerColor,
                ),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }

  /// The backend never sends a usable icon URL for gateways today (see the
  /// comment above), so pick a recognisable icon from the gateway id/name
  /// instead of leaving the row blank.
  Widget _methodIcon(PaymentMethodModel m) {
    final key = '${m.id} ${m.name}'.toLowerCase();
    IconData icon;
    if (key.contains('bkash') ||
        key.contains('nagad') ||
        key.contains('rocket') ||
        key.contains('mobile')) {
      icon = Icons.phone_android;
    } else if (key.contains('wallet')) {
      icon = Icons.account_balance_wallet_outlined;
    } else if (key.contains('bank') || key.contains('transfer')) {
      icon = Icons.account_balance_outlined;
    } else if (key.contains('cash')) {
      icon = Icons.payments_outlined;
    } else {
      // Card gateways (stripe, razorpay, paystack, paytabs, adyen, square,
      // braintree, flutterwave, hesabe, mollie, payu, …) all render as a
      // generic card — there's no bundled logo per gateway.
      icon = Icons.credit_card;
    }
    return Container(
      width: 36,
      height: 36,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: AppColors.primary.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Icon(icon, color: AppColors.primary, size: 20),
    );
  }
}
