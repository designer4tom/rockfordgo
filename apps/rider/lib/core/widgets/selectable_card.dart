import 'package:flutter/material.dart';

import '../constants/app_colors.dart';
import '../theme/app_dimensions.dart';

/// Generic selectable list item: leading + (title/subtitle/meta) + trailing,
/// with a radio/check indicator. Selected → light accent bg + accent border.
/// Reusable for vehicle select, payment select, etc. UI-only; selection is
/// reported via [onTap].
class SelectableCard extends StatelessWidget {
  final bool selected;
  final VoidCallback onTap;
  final Widget? leading;
  final Widget title;
  final Widget? subtitle;
  final Widget? meta;
  final Widget? trailing;

  const SelectableCard({
    super.key,
    required this.selected,
    required this.onTap,
    this.leading,
    required this.title,
    this.subtitle,
    this.meta,
    this.trailing,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: AppDimensions.xs + 2),
        padding: const EdgeInsets.all(AppDimensions.cardPadding - 2),
        decoration: BoxDecoration(
          color: selected
              ? AppColors.primary.withValues(alpha: 0.04)
              : theme.cardColor,
          borderRadius: BorderRadius.circular(AppDimensions.cardRadius),
          border: Border.all(
            color: selected ? AppColors.primary : theme.dividerColor,
            width: selected ? 1.5 : 1,
          ),
        ),
        child: Row(
          children: [
            if (leading != null) ...[
              leading!,
              const SizedBox(width: AppDimensions.md),
            ],
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  title,
                  if (subtitle != null) ...[
                    const SizedBox(height: 3),
                    subtitle!,
                  ],
                  if (meta != null) ...[
                    const SizedBox(height: 4),
                    meta!,
                  ],
                ],
              ),
            ),
            const SizedBox(width: AppDimensions.sm),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                ?trailing,
                const SizedBox(height: AppDimensions.sm),
                SelectionIndicator(selected: selected),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

/// Filled accent check when selected, empty outline circle otherwise.
class SelectionIndicator extends StatelessWidget {
  final bool selected;
  const SelectionIndicator({super.key, required this.selected});

  @override
  Widget build(BuildContext context) {
    if (selected) {
      return Container(
        width: 22,
        height: 22,
        decoration: const BoxDecoration(
            color: AppColors.primary, shape: BoxShape.circle),
        child: const Icon(Icons.check, size: 18, color: Colors.white),
      );
    }
    return Container(
      width: 22,
      height: 22,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(color: Theme.of(context).dividerColor, width: 1.5),
      ),
    );
  }
}
