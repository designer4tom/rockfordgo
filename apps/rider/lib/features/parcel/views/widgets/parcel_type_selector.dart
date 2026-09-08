import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';

class ParcelTypeSelector extends StatelessWidget {
  final String selected; // normal/fragile/document
  final ValueChanged<String> onChanged;

  const ParcelTypeSelector({
    super.key,
    required this.selected,
    required this.onChanged,
  });

  static const _types = [
    ('normal', 'parcel.type_normal', Icons.inventory_2_outlined),
    ('fragile', 'parcel.type_fragile', Icons.warning_amber_rounded),
    ('document', 'parcel.type_document', Icons.description_outlined),
  ];

  @override
  Widget build(BuildContext context) {
    return Row(
      children: _types.map((t) {
        final isSelected = selected == t.$1;
        return Expanded(
          child: GestureDetector(
            onTap: () => onChanged(t.$1),
            child: Container(
              margin: const EdgeInsets.symmetric(horizontal: 4),
              padding: const EdgeInsets.symmetric(vertical: 16),
              decoration: BoxDecoration(
                color: isSelected
                    ? AppColors.primary.withValues(alpha: 0.06)
                    : Theme.of(context).cardColor,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: isSelected
                      ? AppColors.primary
                      : Theme.of(context).dividerColor,
                  width: isSelected ? 2 : 1,
                ),
              ),
              child: Column(
                children: [
                  Icon(t.$3,
                      color: isSelected
                          ? AppColors.primary
                          : AppColors.textSecondary),
                  const SizedBox(height: 6),
                  Text(t.$2.tr(), style: const TextStyle(fontSize: 12)),
                ],
              ),
            ),
          ),
        );
      }).toList(),
    );
  }
}
