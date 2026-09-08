import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/withdrawal_model.dart';

class WithdrawalTile extends StatelessWidget {
  final WithdrawalModel withdrawal;
  const WithdrawalTile({super.key, required this.withdrawal});

  Color get _statusColor {
    switch (withdrawal.status) {
      case 'approved':
        return AppColors.success;
      case 'rejected':
        return AppColors.danger;
      default:
        return AppColors.warning;
    }
  }

  @override
  Widget build(BuildContext context) {
    final amount = double.tryParse(withdrawal.amount) ?? 0;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(Helpers.money(amount),
                    style: const TextStyle(
                        fontSize: 16, fontWeight: FontWeight.bold)),
                const SizedBox(height: 4),
                Text('${withdrawal.method.toUpperCase()} • ${withdrawal.account}',
                    style: TextStyle(color: Theme.of(context).hintColor)),
                Text(Helpers.dateTime(withdrawal.createdAt),
                    style: TextStyle(
                        fontSize: 12, color: Theme.of(context).hintColor)),
                if (withdrawal.status == 'rejected' &&
                    (withdrawal.rejectionReason?.isNotEmpty ?? false)) ...[
                  const SizedBox(height: 6),
                  Text(
                      '${'wallet.rejection_reason'.tr()}: ${withdrawal.rejectionReason}',
                      style: const TextStyle(
                          color: AppColors.danger, fontSize: 13)),
                ],
              ],
            ),
          ),
          const SizedBox(width: 10),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: _statusColor.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              withdrawal.status.toUpperCase(),
              style: TextStyle(
                  color: _statusColor,
                  fontSize: 11,
                  fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
  }
}
