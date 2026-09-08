import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/shift_model.dart';

class ShiftTile extends StatelessWidget {
  final ShiftModel shift;
  const ShiftTile({super.key, required this.shift});

  @override
  Widget build(BuildContext context) {
    final earning = double.tryParse(shift.earning) ?? 0;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
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
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(Helpers.date(shift.date),
                  style: const TextStyle(fontWeight: FontWeight.bold)),
              Text(Helpers.money(earning),
                  style: const TextStyle(
                      fontWeight: FontWeight.bold, color: AppColors.success)),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              _stat(context, Icons.timer_outlined, '${shift.hours}h'),
              const SizedBox(width: 20),
              _stat(context, Icons.local_taxi_outlined,
                  '${shift.trips} ${'history.trips'.tr()}'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _stat(BuildContext context, IconData icon, String text) {
    return Row(
      children: [
        Icon(icon, size: 16, color: Theme.of(context).hintColor),
        const SizedBox(width: 4),
        Text(text, style: TextStyle(color: Theme.of(context).hintColor)),
      ],
    );
  }
}
