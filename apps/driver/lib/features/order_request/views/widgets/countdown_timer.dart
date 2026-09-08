import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';

/// Circular countdown ring that transitions green → yellow → red.
class CountdownTimer extends StatelessWidget {
  final int remaining;
  final int total;
  final double size;

  const CountdownTimer({
    super.key,
    required this.remaining,
    required this.total,
    this.size = 96,
  });

  Color get _color {
    final frac = total == 0 ? 0.0 : remaining / total;
    if (frac > 0.5) return AppColors.success;
    if (frac > 0.25) return AppColors.warning;
    return AppColors.danger;
  }

  @override
  Widget build(BuildContext context) {
    final value = total == 0 ? 0.0 : remaining / total;
    return SizedBox(
      width: size,
      height: size,
      child: Stack(
        alignment: Alignment.center,
        children: [
          SizedBox(
            width: size,
            height: size,
            child: TweenAnimationBuilder<double>(
              tween: Tween(begin: value, end: value),
              duration: const Duration(milliseconds: 400),
              builder: (_, v, _) => CircularProgressIndicator(
                value: v,
                strokeWidth: 7,
                backgroundColor: Theme.of(context).dividerColor,
                valueColor: AlwaysStoppedAnimation(_color),
              ),
            ),
          ),
          Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                '$remaining',
                style: TextStyle(
                  fontSize: size * 0.32,
                  fontWeight: FontWeight.bold,
                  color: _color,
                ),
              ),
              Text('order.seconds_short'.tr(),
                  style: TextStyle(
                      fontSize: 12, color: Theme.of(context).hintColor)),
            ],
          ),
        ],
      ),
    );
  }
}
