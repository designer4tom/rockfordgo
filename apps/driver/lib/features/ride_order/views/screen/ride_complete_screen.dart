import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../provider/ride_order_provider.dart';

class RideCompleteScreen extends StatefulWidget {
  final int orderId;
  const RideCompleteScreen({super.key, required this.orderId});

  @override
  State<RideCompleteScreen> createState() => _RideCompleteScreenState();
}

class _RideCompleteScreenState extends State<RideCompleteScreen> {
  int _rating = 0;
  final Set<String> _tags = {};
  bool _submitting = false;

  static const _quickTags = [
    'Polite',
    'On time',
    'Clean',
    'Good location',
    'Friendly',
  ];

  Future<void> _submit({required bool skip}) async {
    final p = context.read<RideOrderProvider>();
    setState(() => _submitting = true);
    if (!skip && _rating > 0) {
      final comment = _tags.isEmpty ? null : _tags.join(', ');
      await p.rateCustomer(_rating, comment);
    }
    p.clear();
    if (mounted) context.go(RouteNames.home);
  }

  @override
  Widget build(BuildContext context) {
    final fare = context.read<RideOrderProvider>().activeRide?.fare;
    final net = double.tryParse(fare?.driverEarning ?? '0') ?? 0;

    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Column(
            children: [
              const SizedBox(height: 24),
              const CircleAvatar(
                radius: 48,
                backgroundColor: AppColors.success,
                child: Icon(Icons.check, size: 56, color: Colors.white),
              ),
              const SizedBox(height: 20),
              Text('ride.trip_complete'.tr(),
                  style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              Text('${'ride.you_earned'.tr()} ${Helpers.money(net)}',
                  style: const TextStyle(
                      fontSize: 18,
                      color: AppColors.success,
                      fontWeight: FontWeight.w600)),
              const SizedBox(height: 24),
              if (fare != null)
                _fareBreakdown(context, fare.totalFare, fare.adminCommission,
                    fare.driverEarning),
              const SizedBox(height: 28),
              Text('ride.rate_your_customer'.tr(),
                  style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
              const SizedBox(height: 12),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(5, (i) {
                  final filled = i < _rating;
                  return IconButton(
                    onPressed: () => setState(() => _rating = i + 1),
                    icon: Icon(
                      filled ? Icons.star : Icons.star_border,
                      color: Colors.amber,
                      size: 40,
                    ),
                  );
                }),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                alignment: WrapAlignment.center,
                children: _quickTags.map((t) {
                  final sel = _tags.contains(t);
                  return FilterChip(
                    label: Text('ride.tag_${t.toLowerCase().replaceAll(' ', '_')}'.tr()),
                    selected: sel,
                    onSelected: (v) => setState(
                        () => v ? _tags.add(t) : _tags.remove(t)),
                    selectedColor: AppColors.primary.withValues(alpha: 0.2),
                  );
                }).toList(),
              ),
              const SizedBox(height: 28),
              CustomButton(
                label: 'common.submit'.tr(),
                loading: _submitting,
                onPressed: () => _submit(skip: false),
              ),
              const SizedBox(height: 8),
              TextButton(
                onPressed: _submitting ? null : () => _submit(skip: true),
                child: Text('common.skip'.tr()),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _fareBreakdown(
      BuildContext context, String gross, String commission, String net) {
    Widget row(String l, String v, {bool bold = false}) => Padding(
          padding: const EdgeInsets.symmetric(vertical: 3),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(l,
                  style: TextStyle(
                      fontWeight: bold ? FontWeight.bold : FontWeight.normal)),
              Text(Helpers.money(double.tryParse(v) ?? 0),
                  style: TextStyle(
                      fontWeight: bold ? FontWeight.bold : FontWeight.normal)),
            ],
          ),
        );
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          row('ride.gross_fare'.tr(), gross),
          row('ride.commission'.tr(), commission),
          const Divider(),
          row('ride.net_earning'.tr(), net, bold: true),
        ],
      ),
    );
  }
}
