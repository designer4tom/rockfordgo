import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/document_alert.dart';

/// Stack of dismissible alert cards (document expiry, due amount).
class StatusAlerts extends StatelessWidget {
  final List<DocumentAlert> expiringDocs;
  final String dueAmount;
  final bool dueExceeded;

  const StatusAlerts({
    super.key,
    required this.expiringDocs,
    required this.dueAmount,
    required this.dueExceeded,
  });

  @override
  Widget build(BuildContext context) {
    final due = double.tryParse(dueAmount) ?? 0;
    final cards = <Widget>[];

    if (dueExceeded || due > 0) {
      cards.add(_AlertCard(
        icon: Icons.warning_amber_rounded,
        color: dueExceeded ? AppColors.danger : AppColors.warning,
        title: dueExceeded
            ? 'wallet.due_limit_exceeded'.tr()
            : 'wallet.outstanding_due'.tr(),
        message:
            '${Helpers.money(due)} ${'wallet.due_suffix'.tr()}${dueExceeded ? ' ${'wallet.clear_to_go_online'.tr()}' : ''}',
      ));
    }

    for (final d in expiringDocs) {
      cards.add(_AlertCard(
        icon: d.expired ? Icons.error_outline : Icons.schedule,
        color: d.expired ? AppColors.danger : AppColors.warning,
        title: d.expired
            ? '${d.name} ${'documents.expired'.tr()}'
            : '${d.name} ${'documents.expiring_soon'.tr()}',
        message: d.expiry == null
            ? 'documents.please_update'.tr()
            : '${'documents.expires'.tr()} ${Helpers.date(d.expiry)}.',
      ));
    }

    if (cards.isEmpty) return const SizedBox.shrink();

    return Column(
      children: cards
          .map((c) => Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: c,
              ))
          .toList(),
    );
  }
}

class _AlertCard extends StatelessWidget {
  final IconData icon;
  final Color color;
  final String title;
  final String message;

  const _AlertCard({
    required this.icon,
    required this.color,
    required this.title,
    required this.message,
  });

  @override
  Widget build(BuildContext context) {
    return Dismissible(
      key: ValueKey('$title$message'),
      direction: DismissDirection.horizontal,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withValues(alpha: 0.3)),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: color, size: 22),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title,
                      style: TextStyle(
                          fontWeight: FontWeight.bold, color: color)),
                  const SizedBox(height: 2),
                  Text(message,
                      style: TextStyle(
                          fontSize: 13,
                          color: Theme.of(context).colorScheme.onSurface)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
