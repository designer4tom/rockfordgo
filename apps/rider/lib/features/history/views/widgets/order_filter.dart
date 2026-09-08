import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';

/// Status filter tabs (All / Upcoming / Completed / Cancelled).
class OrderFilter extends StatelessWidget {
  final String selected;
  final ValueChanged<String> onChanged;

  const OrderFilter({super.key, required this.selected, required this.onChanged});

  static const _filters = [
    ('all', 'history.all', Icons.menu),
    ('upcoming', 'history.upcoming', Icons.access_time),
    ('completed', 'history.completed', Icons.check_circle_outline),
    ('cancelled', 'history.cancelled', Icons.cancel_outlined),
  ];

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return SizedBox(
      height: 44,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: _filters.length,
        separatorBuilder: (context, i) => const SizedBox(width: 10),
        itemBuilder: (context, i) {
          final f = _filters[i];
          final isSelected = selected == f.$1;
          final fg = isSelected ? Colors.white : theme.colorScheme.onSurface;
          return Material(
            color: isSelected ? AppColors.primary : theme.cardColor,
            borderRadius: BorderRadius.circular(12),
            child: InkWell(
              borderRadius: BorderRadius.circular(12),
              onTap: () => onChanged(f.$1),
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: isSelected
                        ? AppColors.primary
                        : theme.dividerColor,
                  ),
                ),
                child: Row(
                  children: [
                    Icon(f.$3, size: 16, color: fg),
                    const SizedBox(width: 6),
                    Text(
                      f.$2.tr(),
                      style: TextStyle(
                        color: fg,
                        fontWeight:
                            isSelected ? FontWeight.w600 : FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
