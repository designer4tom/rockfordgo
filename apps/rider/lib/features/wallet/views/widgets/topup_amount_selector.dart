import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/services/config_service.dart';
import '../../../../core/utils/helpers.dart';

class TopupAmountSelector extends StatelessWidget {
  final double? selected;
  final ValueChanged<double> onSelected;

  const TopupAmountSelector({
    super.key,
    required this.selected,
    required this.onSelected,
  });

  @override
  Widget build(BuildContext context) {
    // Quick amounts come from /config (topup_quick_amounts).
    final amounts = ConfigService.getCached().topupQuickAmounts;
    return Wrap(
      spacing: 12,
      runSpacing: 12,
      children: amounts.map((a) {
        final isSelected = selected == a;
        return GestureDetector(
          onTap: () => onSelected(a),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
            decoration: BoxDecoration(
              color: isSelected
                  ? AppColors.primary.withValues(alpha: 0.08)
                  : Theme.of(context).cardColor,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: isSelected
                    ? AppColors.primary
                    : Theme.of(context).dividerColor,
                width: isSelected ? 2 : 1,
              ),
            ),
            child: Text(
              Helpers.currency(a),
              style: TextStyle(
                fontWeight: FontWeight.w600,
                color: isSelected
                    ? AppColors.primary
                    : Theme.of(context).colorScheme.onSurface,
              ),
            ),
          ),
        );
      }).toList(),
    );
  }
}
