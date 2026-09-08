import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class PeriodSelector extends StatelessWidget {
  final String selected;
  final ValueChanged<String> onChanged;

  static const periods = {
    'today': 'Today',
    'week': 'Week',
    'month': 'Month',
    'year': 'Year',
    'all': 'All',
  };

  static String _label(String key) {
    switch (key) {
      case 'today':
        return 'earnings.period_today'.tr();
      case 'week':
        return 'earnings.period_week'.tr();
      case 'month':
        return 'earnings.period_month'.tr();
      case 'year':
        return 'earnings.period_year'.tr();
      case 'all':
        return 'earnings.period_all'.tr();
      default:
        return periods[key] ?? key;
    }
  }

  static const _accent = Color(0xFF5B3DF6);

  const PeriodSelector({
    super.key,
    required this.selected,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return PopupMenuButton<String>(
      initialValue: selected,
      onSelected: onChanged,
      position: PopupMenuPosition.under,
      offset: Offset(0, 4.h),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12.r),
      ),
      color: Theme.of(context).cardColor,
      elevation: 4,
      itemBuilder: (_) => periods.entries
          .map(
            (e) => PopupMenuItem<String>(
              value: e.key,
              child: Row(
                children: [
                  Icon(
                    Icons.event_note_outlined,
                    color: e.key == selected
                        ? _accent
                        : Theme.of(context).hintColor,
                    size: 18.sp,
                  ),
                  SizedBox(width: 8.w),
                  Text(
                    _label(e.key),
                    style: TextStyle(
                      fontSize: 14.sp,
                      fontWeight: e.key == selected
                          ? FontWeight.w700
                          : FontWeight.w500,
                      color: e.key == selected
                          ? _accent
                          : Theme.of(context).colorScheme.onSurface,
                    ),
                  ),
                ],
              ),
            ),
          )
          .toList(),
      child: Container(
        padding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 8.h),
        decoration: BoxDecoration(
          color: Theme.of(context).cardColor,
          borderRadius: BorderRadius.circular(8.r),
          border: Border.all(color: Theme.of(context).dividerColor),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.event_note_outlined, color: _accent, size: 18.sp),
            SizedBox(width: 6.w),
            Text(
              _label(selected),
              style: TextStyle(
                color: Theme.of(context).colorScheme.onSurface,
                fontWeight: FontWeight.w600,
                fontSize: 14.sp,
              ),
            ),
            SizedBox(width: 4.w),
            Icon(Icons.keyboard_arrow_down, color: _accent, size: 20.sp),
          ],
        ),
      ),
    );
  }
}
