import 'package:flutter/material.dart';

import '../constants/app_colors.dart';
import '../theme/app_dimensions.dart';

/// One tappable "Wallet / Schedule" style option: icon + label + value +
/// dropdown chevron. Two of these sit side-by-side via [OptionPillRow].
class OptionPill extends StatelessWidget {
  final IconData icon;
  final String label;
  final String? value;
  final VoidCallback onTap;

  const OptionPill({
    super.key,
    required this.icon,
    required this.label,
    this.value,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.all(AppDimensions.cardPadding - 2),
        child: Row(
          children: [
            Icon(icon, size: 20, color: AppColors.primary),
            const SizedBox(width: AppDimensions.sm + 2),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(label,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          fontSize: 12, color: AppColors.textSecondary)),
                  if (value != null) ...[
                    const SizedBox(height: 2),
                    Text(value!,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                            fontWeight: FontWeight.w600,
                            color: theme.colorScheme.onSurface)),
                  ],
                ],
              ),
            ),
            const Icon(Icons.keyboard_arrow_down,
                size: 18, color: AppColors.textSecondary),
          ],
        ),
      ),
    );
  }
}

/// A card holding two [OptionPill]s split by a vertical divider.
class OptionPillRow extends StatelessWidget {
  final OptionPill left;
  final OptionPill right;

  const OptionPillRow({super.key, required this.left, required this.right});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(AppDimensions.inputRadius),
        border: Border.all(color: theme.dividerColor),
      ),
      child: IntrinsicHeight(
        child: Row(
          children: [
            Expanded(child: left),
            VerticalDivider(width: 1, color: theme.dividerColor),
            Expanded(child: right),
          ],
        ),
      ),
    );
  }
}
