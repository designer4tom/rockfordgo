import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/due_entry_model.dart';

class DueTile extends StatelessWidget {
  final DueEntryModel entry;
  final bool showDivider;

  const DueTile({
    super.key,
    required this.entry,
    this.showDivider = false,
  });

  @override
  Widget build(BuildContext context) {
    final paid = entry.isPaid;
    final amount = double.tryParse(entry.amount) ?? 0;
    final dueAfter = double.tryParse(entry.dueAfter) ?? 0;
    final color = paid ? AppColors.success : AppColors.danger;
    final icon = paid ? Icons.check_circle_outline : Icons.arrow_upward;

    final onSurface = Theme.of(context).colorScheme.onSurface;
    final subtitle = entry.note ?? entry.orderNumber ?? '';

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
                  color: color.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: color, size: 20),
              ),
              const SizedBox(width: 12),
              // Title + note/order + date
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      paid ? 'wallet.due_paid'.tr() : 'wallet.due_added'.tr(),
                      style: TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 15,
                          color: onSurface),
                    ),
                    if (subtitle.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(
                        subtitle,
                        style: const TextStyle(
                            fontSize: 12, color: AppColors.textSecondary),
                      ),
                    ],
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
              // Amount + due after
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    '${paid ? '- ' : '+ '}${Helpers.currency(amount)}',
                    style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                        color: color),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${'wallet.due_after_label'.tr()}: ${Helpers.currency(dueAfter)}',
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
    final dt = DateTime.tryParse(entry.createdAt);
    if (dt == null) return entry.createdAt;
    return Helpers.formatDateTime(dt.toLocal());
  }
}
