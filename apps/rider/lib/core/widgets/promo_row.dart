import 'package:flutter/material.dart';

import '../constants/app_colors.dart';
import '../theme/app_dimensions.dart';

/// "Have a promo code?" row. When [appliedLabel] is non-null it renders the
/// applied state (green) with a Remove action; otherwise the call-to-action.
/// Pass already-localized strings.
class PromoRow extends StatelessWidget {
  final String title;
  final String subtitle;
  final String actionLabel;
  final VoidCallback onTap;
  final String? appliedLabel;
  final String? appliedDetail;
  final String? removeLabel;
  final VoidCallback? onRemove;

  const PromoRow({
    super.key,
    required this.title,
    required this.subtitle,
    required this.actionLabel,
    required this.onTap,
    this.appliedLabel,
    this.appliedDetail,
    this.removeLabel,
    this.onRemove,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final applied = appliedLabel != null;
    final accent = applied ? AppColors.success : AppColors.primary;

    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(AppDimensions.cardPadding - 2),
        decoration: BoxDecoration(
          color: applied ? accent.withValues(alpha: 0.06) : theme.cardColor,
          borderRadius: BorderRadius.circular(AppDimensions.inputRadius),
          border: Border.all(color: applied ? accent : theme.dividerColor),
        ),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: accent.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(AppDimensions.inputRadius),
              ),
              child: Icon(Icons.local_offer_outlined, color: accent),
            ),
            const SizedBox(width: AppDimensions.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(applied ? appliedLabel! : title,
                      style: TextStyle(
                          fontWeight: FontWeight.w600,
                          color: theme.colorScheme.onSurface)),
                  const SizedBox(height: 2),
                  Text(applied ? (appliedDetail ?? '') : subtitle,
                      style: TextStyle(
                          fontSize: 12,
                          color: applied
                              ? AppColors.success
                              : AppColors.textSecondary)),
                ],
              ),
            ),
            const SizedBox(width: AppDimensions.sm),
            if (applied)
              TextButton(
                onPressed: onRemove,
                child: Text(removeLabel ?? 'Remove',
                    style: const TextStyle(color: AppColors.danger)),
              )
            else
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(actionLabel,
                      style: const TextStyle(
                          color: AppColors.primary,
                          fontWeight: FontWeight.w600)),
                  const Icon(Icons.chevron_right,
                      size: 18, color: AppColors.primary),
                ],
              ),
          ],
        ),
      ),
    );
  }
}
