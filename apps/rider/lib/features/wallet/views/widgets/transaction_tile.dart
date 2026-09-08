import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/transaction_model.dart';

class TransactionTile extends StatelessWidget {
  final TransactionModel transaction;
  final bool showDivider;

  const TransactionTile({
    super.key,
    required this.transaction,
    this.showDivider = false,
  });

  static const Color _purple = Color(0xFF7C3AED);

  bool get _isParcel {
    final c = '${transaction.category} ${transaction.categoryLabel}'
        .toLowerCase();
    return c.contains('parcel') || c.contains('courier');
  }

  @override
  Widget build(BuildContext context) {
    final credit = transaction.isCredit;
    final amount = double.tryParse(transaction.amount) ?? 0;
    final balance = double.tryParse(transaction.balanceAfter) ?? 0;
    final amountColor = credit ? AppColors.success : AppColors.danger;

    // Category-based leading icon/colour (parcel/courier = purple box).
    final Color iconColor =
        _isParcel ? _purple : (credit ? AppColors.success : AppColors.danger);
    final IconData icon = _isParcel
        ? Icons.inventory_2_outlined
        : (credit ? Icons.arrow_downward : Icons.arrow_upward);

    final onSurface = Theme.of(context).colorScheme.onSurface;

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 44,
                height: 44,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  color: iconColor.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: iconColor, size: 20),
              ),
              const SizedBox(width: 12),
              // Title + date
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      transaction.categoryLabel,
                      style: TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 15,
                          color: onSurface),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      _formattedDate(),
                      style: const TextStyle(
                          fontSize: 12, color: AppColors.textSecondary),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              // Amount + balance after
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    '${credit ? '+ ' : '- '}${Helpers.currency(amount)}',
                    style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                        color: amountColor),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${'home.wallet_balance'.tr()}: ${Helpers.currency(balance)}',
                    style: const TextStyle(
                        fontSize: 11, color: AppColors.textSecondary),
                  ),
                ],
              ),
            ],
          ),
        ),
        if (showDivider)
          Divider(height: 1, color: Theme.of(context).dividerColor),
      ],
    );
  }

  String _formattedDate() {
    final dt = DateTime.tryParse(transaction.createdAt);
    if (dt == null) return transaction.createdAt;
    return Helpers.formatDateTime(dt.toLocal());
  }
}
