import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/transaction_model.dart';

class TransactionTile extends StatelessWidget {
  final TransactionModel transaction;
  const TransactionTile({super.key, required this.transaction});

  @override
  Widget build(BuildContext context) {
    final credit = transaction.isCredit;
    final amount = double.tryParse(transaction.amount) ?? 0;
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: CircleAvatar(
        backgroundColor:
            (credit ? AppColors.success : AppColors.danger).withValues(alpha: 0.12),
        child: Icon(
          credit ? Icons.arrow_downward : Icons.arrow_upward,
          color: credit ? AppColors.success : AppColors.danger,
        ),
      ),
      title: Text(
        transaction.description.isEmpty
            ? (credit ? 'wallet.credit'.tr() : 'wallet.debit'.tr())
            : transaction.description,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
      ),
      subtitle: Text(Helpers.dateTime(transaction.createdAt)),
      trailing: Text(
        '${credit ? '+' : '-'}${Helpers.money(amount)}',
        style: TextStyle(
          fontWeight: FontWeight.bold,
          color: credit ? AppColors.success : AppColors.danger,
        ),
      ),
    );
  }
}
