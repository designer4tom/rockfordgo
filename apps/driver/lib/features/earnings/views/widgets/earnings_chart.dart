import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:syncfusion_flutter_charts/charts.dart';

import '../../../../core/utils/currency_helper.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/earnings_model.dart';

class EarningsChart extends StatelessWidget {
  final List<ChartData> data;
  final int highlightIndex;
  final List<String>? labels;
  const EarningsChart({
    super.key,
    required this.data,
    this.highlightIndex = -1,
    this.labels,
  });

  static const _accent = Color(0xFF5B3DF6);
  static const _faded = Color(0xFFDDD5FB);

  @override
  Widget build(BuildContext context) {
    if (data.isEmpty) {
      return SizedBox(
        height: 200.h,
        child: Center(
          child: Text(
            'earnings.no_chart_data'.tr(),
            style: TextStyle(
              color: Theme.of(context).hintColor,
              fontSize: 13.sp,
            ),
          ),
        ),
      );
    }

    final highlight = highlightIndex >= 0 && highlightIndex < data.length
        ? highlightIndex
        : _indexOfMax();
    final axisMax = _axisMaximum();
    const axisInterval = 1000.0;

    return SizedBox(
      height: 170.h,
      child: SfCartesianChart(
        plotAreaBorderWidth: 0,
        margin: EdgeInsets.zero,
        primaryXAxis: CategoryAxis(
          majorGridLines: const MajorGridLines(width: 0),
          majorTickLines: const MajorTickLines(width: 0),
          axisLine: const AxisLine(width: 0),
          interval: _labelInterval().toDouble(),
          labelPlacement: LabelPlacement.betweenTicks,
          edgeLabelPlacement: EdgeLabelPlacement.shift,
          labelIntersectAction: AxisLabelIntersectAction.hide,
          labelStyle: TextStyle(
            fontSize: 10.sp,
            color: Theme.of(context).hintColor,
            fontWeight: FontWeight.w500,
          ),
        ),
        primaryYAxis: NumericAxis(
          minimum: 0,
          maximum: axisMax,
          interval: axisInterval,
          axisLine: const AxisLine(width: 0),
          majorTickLines: const MajorTickLines(width: 0),
          majorGridLines: MajorGridLines(
            color: Theme.of(context).dividerColor,
            width: 1,
          ),
          labelStyle: TextStyle(
            fontSize: 10.sp,
            color: Theme.of(context).hintColor,
            fontWeight: FontWeight.w500,
          ),
          axisLabelFormatter: (AxisLabelRenderDetails details) {
            final value = details.value.toDouble();
            final sym = CurrencyHelper.symbol;
            final label = value == 0
                ? '${sym}0'
                : value >= 1000
                ? '$sym${(value / 1000).round()}K'
                : '$sym${value.toInt()}';
            return ChartAxisLabel(
              label,
              TextStyle(
                fontSize: 10.sp,
                color: Theme.of(context).hintColor,
                fontWeight: FontWeight.w500,
              ),
            );
          },
        ),
        series: <CartesianSeries<ChartData, String>>[
          ColumnSeries<ChartData, String>(
            dataSource: data,
            xValueMapper: (chartData, index) =>
                labels != null && index < labels!.length
                ? labels![index]
                : chartData.label,
            yValueMapper: (chartData, _) => chartData.value,
            pointColorMapper: (chartData, index) =>
                index == highlight ? _accent : _faded,
            width: 0.38,
            spacing: 0.15,
            borderRadius: BorderRadius.circular(6.r),
            dataLabelSettings: DataLabelSettings(
              isVisible: true,
              labelAlignment: ChartDataLabelAlignment.top,
              offset: Offset(0, -0.1.h),
              builder:
                  (
                    dynamic dataPoint,
                    dynamic point,
                    dynamic series,
                    int pointIndex,
                    int seriesIndex,
                  ) {
                    if (pointIndex != highlight) return const SizedBox.shrink();
                    final chartData = dataPoint as ChartData;
                    return Container(
                      padding: EdgeInsets.symmetric(
                        horizontal: 7.w,
                        vertical: 4.h,
                      ),
                      decoration: BoxDecoration(
                        color: _accent,
                        borderRadius: BorderRadius.circular(8.r),
                        boxShadow: [
                          BoxShadow(
                            color: _accent.withValues(alpha: 0.22),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Text(
                        Helpers.money(chartData.value),
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 10.sp,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    );
                  },
            ),
          ),
        ],
      ),
    );
  }

  /// Show roughly a dozen x-axis labels at most so day/date/month labels
  /// don't pile up on top of each other when there are many data points.
  int _labelInterval() {
    const maxLabels = 12;
    if (data.length <= maxLabels) return 1;
    return (data.length / maxLabels).ceil();
  }

  double _axisMaximum() {
    final maxValue = data.isEmpty
        ? 0.0
        : data.map((e) => e.value).fold<double>(0, (a, b) => a > b ? a : b);
    if (maxValue <= 0) return 3000;
    final rounded = (maxValue / 1000).ceil() * 1000;
    return rounded < 3000 ? 3000 : rounded.toDouble();
  }

  int _indexOfMax() {
    if (data.isEmpty) return -1;
    var best = 0;
    for (var i = 1; i < data.length; i++) {
      if (data[i].value > data[best].value) best = i;
    }
    return best;
  }
}
