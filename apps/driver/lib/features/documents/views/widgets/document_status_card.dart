import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../model/document_model.dart';

class DocumentStatusCard extends StatelessWidget {
  final DocumentModel document;
  final VoidCallback onUpdate;

  const DocumentStatusCard({
    super.key,
    required this.document,
    required this.onUpdate,
  });

  Color get _statusColor {
    switch (document.status) {
      case 'approved':
        return AppColors.success;
      case 'rejected':
      case 'expired':
        return AppColors.danger;
      case 'expiring_soon':
        return AppColors.warning;
      default:
        return AppColors.textSecondary;
    }
  }

  String get _statusLabel => document.status.replaceAll('_', ' ').toUpperCase();

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(document.typeLabel,
                    style: const TextStyle(
                        fontSize: 15, fontWeight: FontWeight.bold)),
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: _statusColor.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(_statusLabel,
                    style: TextStyle(
                        color: _statusColor,
                        fontSize: 11,
                        fontWeight: FontWeight.bold)),
              ),
            ],
          ),
          if (document.expiryDate != null) ...[
            const SizedBox(height: 6),
            Row(
              children: [
                Icon(Icons.event,
                    size: 16,
                    color: document.isExpired || document.isExpiringSoon
                        ? AppColors.danger
                        : Theme.of(context).hintColor),
                const SizedBox(width: 6),
                Text(
                  '${'documents.expires'.tr()}: ${document.expiryDate}'
                  '${document.daysUntilExpiry != null ? ' (${document.daysUntilExpiry}d)' : ''}',
                  style: TextStyle(
                    fontSize: 13,
                    color: document.isExpired || document.isExpiringSoon
                        ? AppColors.danger
                        : Theme.of(context).hintColor,
                  ),
                ),
              ],
            ),
          ],
          if (document.isRejected &&
              (document.rejectionReason?.isNotEmpty ?? false)) ...[
            const SizedBox(height: 6),
            Text('${'documents.reason'.tr()}: ${document.rejectionReason}',
                style: const TextStyle(color: AppColors.danger, fontSize: 13)),
          ],
          if (document.needsAction) ...[
            const SizedBox(height: 10),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: onUpdate,
                icon: const Icon(Icons.upload_file, size: 18),
                label: Text(document.isExpired
                    ? 'documents.update_now'.tr()
                    : 'documents.re_upload'.tr()),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
