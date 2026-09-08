import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';

class ParcelStepIndicator extends StatelessWidget {
  final int currentStep; // 0-based
  static const _labels = ['parcel.step_sender', 'parcel.step_receiver', 'parcel.step_details', 'parcel.step_cod', 'parcel.step_confirm'];

  const ParcelStepIndicator({super.key, required this.currentStep});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: List.generate(_labels.length * 2 - 1, (i) {
          if (i.isOdd) {
            final stepBefore = i ~/ 2;
            final done = stepBefore < currentStep;
            return Expanded(
              child: Padding(
                padding: const EdgeInsets.only(top: 13),
                child: Container(
                  height: 2,
                  color:
                      done ? AppColors.primary : Theme.of(context).dividerColor,
                ),
              ),
            );
          }
          final step = i ~/ 2;
          final isDone = step < currentStep;
          final isCurrent = step == currentStep;
          return Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              CircleAvatar(
                radius: 14,
                backgroundColor: isDone || isCurrent
                    ? AppColors.primary
                    : Theme.of(context).dividerColor,
                child: isDone
                    ? const Icon(Icons.check, size: 16, color: Colors.white)
                    : Text(
                        '${step + 1}',
                        style: TextStyle(
                          fontSize: 12,
                          color: isCurrent
                              ? Colors.white
                              : AppColors.textPrimary,
                        ),
                      ),
              ),
              const SizedBox(height: 4),
              Text(
                _labels[step].tr(),
                style: TextStyle(
                  fontSize: 10,
                  color:
                      isCurrent ? AppColors.primary : AppColors.textSecondary,
                ),
              ),
            ],
          );
        }),
      ),
    );
  }
}
