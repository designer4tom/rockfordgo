import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/loading_indicator.dart';
import '../../model/performance_model.dart';
import '../../provider/performance_provider.dart';

class PerformanceScreen extends StatefulWidget {
  const PerformanceScreen({super.key});

  @override
  State<PerformanceScreen> createState() => _PerformanceScreenState();
}

class _PerformanceScreenState extends State<PerformanceScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<PerformanceProvider>().loadPerformance();
    });
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<PerformanceProvider>();
    final m = p.performance;
    return Scaffold(
      appBar: AppBar(title: Text('performance.title'.tr())),
      body: p.loading && m == null
          ? const LoadingIndicator()
          : m == null
              ? Center(child: Text(p.error ?? 'common.no_data'.tr()))
              : RefreshIndicator(
                  onRefresh: () => p.loadPerformance(),
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      _ratingOverview(m),
                      const SizedBox(height: 20),
                      _breakdown(m),
                      const SizedBox(height: 20),
                      _metrics(m),
                      const SizedBox(height: 20),
                      if (m.recentRatings.isNotEmpty) _recent(m),
                    ],
                  ),
                ),
    );
  }

  Widget _ratingOverview(PerformanceModel m) {
    return Column(
      children: [
        Text(m.averageRating,
            style: const TextStyle(fontSize: 48, fontWeight: FontWeight.bold)),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: List.generate(5, (i) {
            final avg = double.tryParse(m.averageRating) ?? 0;
            return Icon(
              i < avg.round() ? Icons.star : Icons.star_border,
              color: Colors.amber,
            );
          }),
        ),
        const SizedBox(height: 4),
        Text('${m.totalRatings} ${'performance.ratings'.tr()}',
            style: const TextStyle(color: AppColors.textSecondary)),
      ],
    );
  }

  Widget _breakdown(PerformanceModel m) {
    final total = m.totalRatings == 0 ? 1 : m.totalRatings;
    return Column(
      children: [5, 4, 3, 2, 1].map((star) {
        final count = m.ratingBreakdown[star] ?? 0;
        return Padding(
          padding: const EdgeInsets.symmetric(vertical: 3),
          child: Row(
            children: [
              SizedBox(width: 16, child: Text('$star')),
              const Icon(Icons.star, size: 14, color: Colors.amber),
              const SizedBox(width: 8),
              Expanded(
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(
                    value: count / total,
                    minHeight: 8,
                    backgroundColor: AppColors.border,
                    color: AppColors.primary,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              SizedBox(width: 32, child: Text('$count', textAlign: TextAlign.end)),
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _metrics(PerformanceModel m) {
    return Column(
      children: [
        _metric('performance.acceptance_rate'.tr(), m.acceptanceRate, AppColors.success),
        _metric('performance.completion_rate'.tr(), m.completionRate, AppColors.secondary),
        _metric('performance.cancellation_rate'.tr(), m.cancellationRate, AppColors.danger),
      ],
    );
  }

  Widget _metric(String label, String value, Color color) {
    final pct = (double.tryParse(value) ?? 0) / 100;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(label),
              Text('$value%',
                  style: const TextStyle(fontWeight: FontWeight.bold)),
            ],
          ),
          const SizedBox(height: 4),
          ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: LinearProgressIndicator(
              value: pct.clamp(0.0, 1.0),
              minHeight: 8,
              backgroundColor: AppColors.border,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  String _initial(String? name) =>
      (name == null || name.trim().isEmpty) ? '?' : name.trim()[0].toUpperCase();

  Widget _recent(PerformanceModel m) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('performance.recent_ratings'.tr(),
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
        const SizedBox(height: 8),
        ...m.recentRatings.map((r) => Container(
              margin: const EdgeInsets.only(bottom: 8),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.border),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      CircleAvatar(
                        radius: 16,
                        backgroundColor:
                            AppColors.primary.withValues(alpha: 0.12),
                        backgroundImage: r.customerImage != null
                            ? NetworkImage(r.customerImage!)
                            : null,
                        child: r.customerImage != null
                            ? null
                            : Text(
                                _initial(r.customerName),
                                style: const TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold,
                                    color: AppColors.primary),
                              ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          (r.customerName?.isNotEmpty ?? false)
                              ? r.customerName!
                              : 'performance.anonymous'.tr(),
                          style: const TextStyle(
                              fontWeight: FontWeight.w600, fontSize: 14),
                        ),
                      ),
                      Text(Helpers.date(r.date),
                          style: const TextStyle(
                              fontSize: 12, color: AppColors.textSecondary)),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Row(
                    children: List.generate(
                      5,
                      (i) => Icon(
                        i < r.rating ? Icons.star : Icons.star_border,
                        size: 16,
                        color: Colors.amber,
                      ),
                    ),
                  ),
                  if (r.comment?.isNotEmpty ?? false) ...[
                    const SizedBox(height: 6),
                    Text(r.comment!),
                  ],
                ],
              ),
            )),
      ],
    );
  }
}
