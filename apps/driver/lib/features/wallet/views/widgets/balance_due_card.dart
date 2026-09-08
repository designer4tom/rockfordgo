import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/wallet_model.dart';

class BalanceDueCard extends StatelessWidget {
  final WalletModel wallet;
  final VoidCallback onWithdraw;
  final VoidCallback onRecharge;

  const BalanceDueCard({
    super.key,
    required this.wallet,
    required this.onWithdraw,
    required this.onRecharge,
  });

  @override
  Widget build(BuildContext context) {
    final dueRatio = wallet.dueLimitValue <= 0
        ? 0.0
        : (wallet.dueValue / wallet.dueLimitValue).clamp(0.0, 1.0);

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('wallet.balance'.tr(),
              style: TextStyle(color: Theme.of(context).hintColor)),
          const SizedBox(height: 4),
          Text(
            Helpers.money(wallet.balanceValue),
            style: const TextStyle(
                fontSize: 32, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),
          if (wallet.dueValue > 0) ...[
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('wallet.due'.tr(),
                    style: const TextStyle(color: AppColors.danger)),
                Text(
                  '${Helpers.money(wallet.dueValue)} / ${Helpers.money(wallet.dueLimitValue)}',
                  style: const TextStyle(
                      color: AppColors.danger, fontWeight: FontWeight.w600),
                ),
              ],
            ),
            const SizedBox(height: 6),
            ClipRRect(
              borderRadius: BorderRadius.circular(6),
              child: LinearProgressIndicator(
                value: dueRatio,
                minHeight: 8,
                backgroundColor: Theme.of(context).dividerColor,
                color: wallet.dueExceeded ? AppColors.danger : AppColors.warning,
              ),
            ),
            const SizedBox(height: 12),
          ],
          if (wallet.dueExceeded)
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppColors.danger.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Row(
                children: [
                  const Icon(Icons.warning_amber_rounded,
                      color: AppColors.danger),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      '${'wallet.due'.tr()}: ${Helpers.money(wallet.dueValue)} — ${'wallet.recharge_to_clear'.tr()}',
                      style: const TextStyle(color: AppColors.danger),
                    ),
                  ),
                ],
              ),
            ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: onRecharge,
                  icon: const Icon(Icons.add),
                  label: Text('wallet.recharge'.tr()),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: onWithdraw,
                  icon: const Icon(Icons.account_balance_wallet_outlined),
                  label: Text('wallet.withdraw'.tr()),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
