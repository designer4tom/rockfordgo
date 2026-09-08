import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';

/// Horizontal step progress indicator for the 4-step registration flow.
class RegistrationStepIndicator extends StatelessWidget {
  final int currentStep;
  final int totalSteps;
  final List<String> labels;

  const RegistrationStepIndicator({
    super.key,
    required this.currentStep,
    this.totalSteps = 4,
    this.labels = const ['Personal', 'Documents', 'Vehicle', 'Payout'],
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: List.generate(totalSteps, (i) {
        final done = i < currentStep;
        final active = i == currentStep;
        final color =
            done || active ? AppColors.primary : Theme.of(context).dividerColor;
        return Expanded(
          child: Column(
            children: [
              Row(
                children: [
                  Expanded(
                    child: Container(
                      height: 4,
                      color: i == 0 ? Colors.transparent : color,
                    ),
                  ),
                  CircleAvatar(
                    radius: 14,
                    backgroundColor: color,
                    child: done
                        ? const Icon(Icons.check, size: 16, color: Colors.white)
                        : Text(
                            '${i + 1}',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              color: active || done
                                  ? Colors.white
                                  : Theme.of(context).hintColor,
                            ),
                          ),
                  ),
                  Expanded(
                    child: Container(
                      height: 4,
                      color: i == totalSteps - 1
                          ? Colors.transparent
                          : (i < currentStep ? AppColors.primary : Theme.of(context).dividerColor),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              if (i < labels.length)
                Text(
                  labels[i],
                  style: TextStyle(
                    fontSize: 11,
                    color: active
                        ? AppColors.primary
                        : Theme.of(context).hintColor,
                    fontWeight: active ? FontWeight.w600 : FontWeight.normal,
                  ),
                ),
            ],
          ),
        );
      }),
    );
  }
}
