import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';

class SchedulePicker extends StatelessWidget {
  final DateTime? scheduledAt;
  final ValueChanged<DateTime?> onScheduled;

  const SchedulePicker({
    super.key,
    required this.scheduledAt,
    required this.onScheduled,
  });

  Future<void> _pickLater(BuildContext context) async {
    final now = DateTime.now();
    final minDate = now.add(const Duration(hours: 1));
    final date = await showDatePicker(
      context: context,
      initialDate: minDate,
      firstDate: minDate,
      lastDate: now.add(const Duration(days: 7)),
    );
    if (date == null || !context.mounted) return;

    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(minDate),
    );
    if (time == null) return;

    onScheduled(
      DateTime(date.year, date.month, date.day, time.hour, time.minute),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isLater = scheduledAt != null;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            _segment(
              context,
              label: 'now'.tr(),
              selected: !isLater,
              onTap: () => onScheduled(null),
            ),
            const SizedBox(width: 8),
            _segment(
              context,
              label: 'later'.tr(),
              selected: isLater,
              onTap: () => _pickLater(context),
            ),
          ],
        ),
        if (isLater) ...[
          const SizedBox(height: 8),
          Text(
            'ride.scheduled_at'.tr(namedArgs: {'datetime': Helpers.formatDateTime(scheduledAt!)}),
            style: const TextStyle(
              color: AppColors.textSecondary,
              fontSize: 13,
            ),
          ),
        ],
      ],
    );
  }

  Widget _segment(
    BuildContext context, {
    required String label,
    required bool selected,
    required VoidCallback onTap,
  }) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 12),
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: selected ? AppColors.primary : Theme.of(context).cardColor,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(
              color: selected ? AppColors.primary : Theme.of(context).dividerColor,
            ),
          ),
          child: Text(
            label,
            style: TextStyle(
              color: selected ? Colors.white : Theme.of(context).colorScheme.onSurface,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
      ),
    );
  }
}
