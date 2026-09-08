import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

import '../../../../core/utils/helpers.dart';
import '../../model/earnings_model.dart';

class EarningsSummaryCard extends StatelessWidget {
  final EarningsModel earnings;
  const EarningsSummaryCard({super.key, required this.earnings});

  static const _accent = Color(0xFF5B3DF6);

  @override
  Widget build(BuildContext context) {
    final total = double.tryParse(earnings.totalEarning) ?? 0;
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(14.r),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Padding(
        padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 14.h),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'earnings.total_earnings'.tr(),
                    style: TextStyle(
                      color: Theme.of(context).hintColor,
                      fontSize: 12.sp,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  SizedBox(height: 6.h),
                  Text(
                    Helpers.money(total),
                    style: TextStyle(
                      fontSize: 28.sp,
                      fontWeight: FontWeight.bold,
                      color: Theme.of(context).colorScheme.onSurface,
                    ),
                  ),
                ],
              ),
            ),
            Container(width: 1, height: 48.h, color: const Color(0xFFEDE9FB)),
            SizedBox(width: 14.w),
            Container(
              width: 56.r,
              height: 56.r,
              decoration: BoxDecoration(
                color: _accent.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              alignment: Alignment.center,
              child: Icon(
                Icons.account_balance_wallet_rounded,
                color: _accent,
                size: 28.sp,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
