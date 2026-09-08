import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/loading_indicator.dart';
import '../../model/earnings_model.dart';
import '../../provider/earnings_provider.dart';
import '../widgets/earnings_chart.dart';
import '../widgets/earnings_summary_card.dart';
import '../widgets/period_selector.dart';

class EarningsScreen extends StatefulWidget {
  const EarningsScreen({super.key});

  @override
  State<EarningsScreen> createState() => _EarningsScreenState();
}

class _EarningsScreenState extends State<EarningsScreen> {
  static const _accent = Color(0xFF5B3DF6);
  static const _accentSoft = Color(0xFFEDE7FE);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<EarningsProvider>().init();
    });
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<EarningsProvider>();
    final rating = p.averageRating;
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: EdgeInsets.fromLTRB(16.w, 10.h, 16.w, 8.h),
              child: Align(
                alignment: Alignment.centerRight,
                child: PeriodSelector(
                  selected: p.period,
                  onChanged: p.setPeriod,
                ),
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: () => p.init(),
                child: ListView(
                  padding: EdgeInsets.fromLTRB(16.w, 2.h, 16.w, 18.h),
                  children: [
                    if (p.loading && p.earnings == null)
                      Padding(
                        padding: EdgeInsets.symmetric(vertical: 28.h),
                        child: const LoadingIndicator(),
                      )
                    else if (p.earnings != null) ...[
                      EarningsSummaryCard(earnings: p.earnings!),
                      SizedBox(height: 8.h),
                      _statsRow(p.earnings!, rating),
                      SizedBox(height: 8.h),
                      _chartCard(p),
                      SizedBox(height: 8.h),
                      _breakdownCard(p.earnings!),
                    ] else
                      Padding(
                        padding: EdgeInsets.symmetric(vertical: 40.h),
                        child: Column(
                          children: [
                            Icon(Icons.bar_chart_rounded,
                                size: 48.sp, color: Colors.black26),
                            SizedBox(height: 12.h),
                            Text(
                              p.error ?? 'earnings.no_data'.tr(),
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                  color: Theme.of(context).hintColor,
                                  fontSize: 13.sp),
                            ),
                            SizedBox(height: 12.h),
                            OutlinedButton(
                              onPressed: () => p.init(),
                              child: Text('common.retry'.tr()),
                            ),
                          ],
                        ),
                      ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ─────────── Stats row ───────────
  Widget _statsRow(EarningsModel e, double rating) {
    return _Card(
      child: Padding(
        padding: EdgeInsets.symmetric(vertical: 14.h, horizontal: 10.w),
        child: Row(
          children: [
            Expanded(
              child: _Stat(
                icon: Icons.directions_car_filled_outlined,
                color: const Color(0xFF22B07D),
                label: 'earnings.total_rides'.tr(),
                value: '${e.totalTrips}',
              ),
            ),
            const _VDivider(),
            Expanded(
              child: _Stat(
                icon: Icons.access_time_rounded,
                color: _accent,
                label: 'earnings.online_time'.tr(),
                value: e.formattedOnlineTime,
              ),
            ),
            const _VDivider(),
            Expanded(
              child: _Stat(
                icon: Icons.star_outline_rounded,
                color: const Color(0xFFF59E0B),
                label: 'earnings.average_rating'.tr(),
                value: rating > 0 ? rating.toStringAsFixed(1) : '—',
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ─────────── Chart card ───────────
  Widget _chartCard(EarningsProvider p) {
    return _Card(
      child: Padding(
        padding: EdgeInsets.fromLTRB(14.w, 12.h, 14.w, 10.h),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'earnings.overview'.tr(),
                  style: TextStyle(
                    fontSize: 14.sp,
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                ),
                InkWell(
                  onTap: () => context.push(RouteNames.history),
                  child: Row(
                    children: [
                      Text(
                        'earnings.view_history'.tr(),
                        style: TextStyle(
                          color: _accent,
                          fontWeight: FontWeight.w600,
                          fontSize: 11.sp,
                        ),
                      ),
                      Icon(Icons.chevron_right, color: _accent, size: 18.sp),
                    ],
                  ),
                ),
              ],
            ),
            SizedBox(height: 8.h),
            EarningsChart(
              data: p.chartData,
              labels: _labelsFor(p.period, p.chartData.length),
            ),
          ],
        ),
      ),
    );
  }

  /// Return axis labels based on the selected period.
  /// - week  → Mon..Sun
  /// - year / all → Jan..Dec
  /// - month → numeric day-of-month (1..N)
  /// - today → null (let chart use its own labels)
  List<String>? _labelsFor(String period, int dataLen) {
    if (dataLen == 0) return null;
    switch (period) {
      case 'week':
        const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        return List.generate(dataLen, (i) => i < days.length ? days[i] : '');
      case 'year':
      case 'all':
        const months = [
          'Jan',
          'Feb',
          'Mar',
          'Apr',
          'May',
          'Jun',
          'Jul',
          'Aug',
          'Sep',
          'Oct',
          'Nov',
          'Dec',
        ];
        return List.generate(
          dataLen,
          (i) => i < months.length ? months[i] : '',
        );
      case 'month':
        return List.generate(dataLen, (i) => '${i + 1}');
      default:
        return null;
    }
  }

  // ─────────── Breakdown card ───────────
  Widget _breakdownCard(EarningsModel e) {
    final ride = double.tryParse(e.rideEarning) ?? 0;
    final parcel = double.tryParse(e.parcelEarning) ?? 0;
    final tips = double.tryParse(e.tipsReceived) ?? 0;
    final total = double.tryParse(e.totalEarning) ?? 0;
    // Bonus is not in the API yet → keep it static for now.
    // TODO: replace with e.bonusEarning once backend adds the field.
    const double bonus = 0.0;

    return _Card(
      child: Padding(
        padding: EdgeInsets.fromLTRB(14.w, 14.h, 14.w, 12.h),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'earnings.breakdown'.tr(),
              style: TextStyle(
                fontSize: 14.sp,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
            ),
            SizedBox(height: 10.h),
            _BreakdownRow(
              icon: Icons.person_outline,
              iconColor: const Color(0xFF22B07D),
              title: 'earnings.ride_earnings'.tr(),
              subtitle:
                  'earnings.rides_count'.tr(namedArgs: {'count': '${e.totalTrips}'}),
              amount: ride,
            ),
            if (parcel > 0) ...[
              Divider(height: 18.h, color: const Color(0xFFF1EEFB)),
              _BreakdownRow(
                icon: Icons.local_shipping_outlined,
                iconColor: const Color(0xFF1A56DB),
                title: 'earnings.parcel_earnings'.tr(),
                subtitle: 'earnings.delivery'.tr(),
                amount: parcel,
              ),
            ],
            Divider(height: 18.h, color: const Color(0xFFF1EEFB)),
            _BreakdownRow(
              icon: Icons.sentiment_satisfied_alt_rounded,
              iconColor: const Color(0xFFF59E0B),
              title: 'earnings.tips'.tr(),
              subtitle: tips > 0
                  ? 'earnings.tips_count'.tr(namedArgs: {'count': '${tips.toInt()}'})
                  : 'earnings.no_tips'.tr(),
              amount: tips,
            ),
            Divider(height: 18.h, color: const Color(0xFFF1EEFB)),
            _BreakdownRow(
              icon: Icons.card_giftcard_rounded,
              iconColor: _accent,
              title: 'earnings.bonus'.tr(),
              subtitle: 'earnings.no_bonus_period'.tr(),
              amount: bonus,
            ),
            SizedBox(height: 10.h),
            Container(
              padding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 12.h),
              decoration: BoxDecoration(
                color: _accentSoft,
                borderRadius: BorderRadius.circular(12.r),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'earnings.total_earnings'.tr(),
                    style: TextStyle(
                      color: _accent,
                      fontWeight: FontWeight.w700,
                      fontSize: 14.sp,
                    ),
                  ),
                  Text(
                    Helpers.money(total),
                    style: TextStyle(
                      color: _accent,
                      fontWeight: FontWeight.bold,
                      fontSize: 14.sp,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─────────── small UI primitives ───────────
class _Card extends StatelessWidget {
  final Widget child;
  const _Card({required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(16.r),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: child,
    );
  }
}

class _Stat extends StatelessWidget {
  final IconData icon;
  final Color color;
  final String label;
  final String value;
  const _Stat({
    required this.icon,
    required this.color,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Container(
          width: 38.r,
          height: 38.r,
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.12),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, color: color, size: 20.sp),
        ),
        SizedBox(height: 6.h),
        Text(
          label,
          style: TextStyle(
            fontSize: 11.sp,
            color: Theme.of(context).hintColor,
            fontWeight: FontWeight.w500,
          ),
        ),
        SizedBox(height: 3.h),
        Text(
          value,
          style: TextStyle(
            fontSize: 14.sp,
            fontWeight: FontWeight.bold,
            color: Theme.of(context).colorScheme.onSurface,
          ),
        ),
      ],
    );
  }
}

class _VDivider extends StatelessWidget {
  const _VDivider();

  @override
  Widget build(BuildContext context) =>
      Container(width: 1, height: 48.h, color: const Color(0xFFEDE9FB));
}

class _BreakdownRow extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String title;
  final String subtitle;
  final double amount;

  const _BreakdownRow({
    required this.icon,
    required this.iconColor,
    required this.title,
    required this.subtitle,
    required this.amount,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 34.r,
          height: 34.r,
          decoration: BoxDecoration(
            color: iconColor.withValues(alpha: 0.12),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, color: iconColor, size: 18.sp),
        ),
        SizedBox(width: 10.w),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  fontSize: 13.sp,
                  color: Theme.of(context).colorScheme.onSurface,
                ),
              ),
              SizedBox(height: 2.h),
              Text(
                subtitle,
                style: TextStyle(
                    color: Theme.of(context).hintColor, fontSize: 11.sp),
              ),
            ],
          ),
        ),
        Text(
          Helpers.money(amount),
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 13.sp,
            color: Theme.of(context).colorScheme.onSurface,
          ),
        ),
        SizedBox(width: 4.w),
        Icon(Icons.chevron_right, color: Colors.black38, size: 18.sp),
      ],
    );
  }
}
