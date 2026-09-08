import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/fare_estimate_model.dart';

class FareBreakdownCard extends StatelessWidget {
  final FareBreakdown breakdown;
  final double couponDiscount;
  final double total;

  const FareBreakdownCard({
    super.key,
    required this.breakdown,
    required this.couponDiscount,
    required this.total,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        children: [
          _row('base_fare'.tr(), breakdown.baseFare),
          _row('distance_charge'.tr(), breakdown.distanceCharge),
          _row('time_charge'.tr(), breakdown.timeCharge),
          if ((double.tryParse(breakdown.surgeAmount) ?? 0) > 0)
            _row('surge'.tr(), breakdown.surgeAmount),
          if (couponDiscount > 0)
            _row('coupon_discount'.tr(), '-${couponDiscount.toStringAsFixed(2)}',
                color: AppColors.success),
          const Divider(height: 20),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'total'.tr(),
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              Text(
                Helpers.currency(total),
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                  color: AppColors.primary,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _row(String label, String value, {Color? color}) {
    final amount = double.tryParse(value.replaceAll('-', '')) ?? 0;
    final display =
        value.startsWith('-') ? '-${Helpers.currency(amount)}' : Helpers.currency(amount);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: AppColors.textSecondary)),
          Text(display, style: TextStyle(color: color)),
        ],
      ),
    );
  }
}
