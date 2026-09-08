import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';

class ParcelSizeSelector extends StatelessWidget {
  final String selected; // small/medium/large
  final ValueChanged<String> onChanged;

  const ParcelSizeSelector({
    super.key,
    required this.selected,
    required this.onChanged,
  });

  static const _sizes = [
    ('small', 'parcel.size_small', Icons.crop_square),
    ('medium', 'parcel.size_medium', Icons.crop_din),
    ('large', 'parcel.size_large', Icons.crop_16_9),
  ];

  @override
  Widget build(BuildContext context) {
    return Row(
      children: _sizes.map((s) {
        final isSelected = selected == s.$1;
        return Expanded(
          child: GestureDetector(
            onTap: () => onChanged(s.$1),
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
                  Icon(s.$3,
                      color: isSelected
                          ? AppColors.primary
                          : AppColors.textSecondary),
                  const SizedBox(height: 6),
                  Text(s.$2.tr(), style: const TextStyle(fontSize: 12)),
                ],
              ),
            ),
          ),
        );
      }).toList(),
    );
  }
}
