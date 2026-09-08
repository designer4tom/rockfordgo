import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';

class PaymentMethodSelector extends StatelessWidget {
  final String selected; // 'cash' | 'online' | 'wallet'
  final double walletBalance;
  final double payableAmount;
  final ValueChanged<String> onChanged;
  final VoidCallback? onTopUp;

  const PaymentMethodSelector({
    super.key,
    required this.selected,
    required this.walletBalance,
    required this.payableAmount,
    required this.onChanged,
    this.onTopUp,
  });

  @override
  Widget build(BuildContext context) {
    final walletInsufficient = walletBalance < payableAmount;
    return Column(
      children: [
        _tile(
          context,
          value: 'cash',
          icon: Icons.payments_outlined,
          title: 'cash'.tr(),
        ),
        _tile(
          context,
          value: 'online',
          icon: Icons.credit_card,
          title: 'online'.tr(),
        ),
        _tile(
          context,
          value: 'wallet',
          icon: Icons.account_balance_wallet_outlined,
          title: 'ride.wallet'.tr(),
          subtitle: 'ride.wallet_balance'.tr(namedArgs: {'amount': Helpers.currency(walletBalance)}),
          disabled: walletInsufficient,
          trailing: walletInsufficient
              ? TextButton(onPressed: onTopUp, child: Text('top_up'.tr()))
              : null,
        ),
      ],
    );
  }

  Widget _tile(
    BuildContext context, {
    required String value,
    required IconData icon,
    required String title,
    String? subtitle,
    bool disabled = false,
    Widget? trailing,
  }) {
    final isSelected = selected == value;
    return Opacity(
      opacity: disabled ? 0.5 : 1,
      child: GestureDetector(
        onTap: disabled ? null : () => onChanged(value),
        child: Container(
          margin: const EdgeInsets.symmetric(vertical: 4),
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Theme.of(context).cardColor,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: isSelected ? AppColors.primary : Theme.of(context).dividerColor,
              width: isSelected ? 2 : 1,
            ),
          ),
          child: Row(
            children: [
              Icon(icon,
                  color: isSelected ? AppColors.primary : AppColors.textSecondary),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title,
                        style: const TextStyle(fontWeight: FontWeight.w500)),
                    if (subtitle != null)
                      Text(subtitle,
                          style: const TextStyle(
                              fontSize: 12, color: AppColors.textSecondary)),
                  ],
                ),
              ),
              if (trailing != null)
                trailing
              else
                Icon(
                  isSelected
                      ? Icons.radio_button_checked
                      : Icons.radio_button_unchecked,
                  color: isSelected ? AppColors.primary : Theme.of(context).dividerColor,
                ),
            ],
          ),
        ),
      ),
    );
  }
}
