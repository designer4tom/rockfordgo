import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../provider/tracking_provider.dart';
import '../widgets/tip_selector.dart';

class TripCompleteScreen extends StatelessWidget {
  final int orderId;

  const TripCompleteScreen({super.key, required this.orderId});

  @override
  Widget build(BuildContext context) {
    final tracking = context.read<TrackingProvider>();
    final isParcel = tracking.orderType == 'parcel';

    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const SizedBox(height: 24),
            Center(
              child: Container(
                height: 96,
                width: 96,
                decoration: BoxDecoration(
                  color: AppColors.success.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.check_circle,
                    size: 64, color: AppColors.success),
              ),
            ),
            const SizedBox(height: 20),
            Center(
              child: Text(
                isParcel
                    ? 'tracking.delivery_completed'.tr()
                    : 'tracking.trip_completed'.tr(),
                style: Theme.of(context).textTheme.headlineSmall,
              ),
            ),
            const SizedBox(height: 8),
            Center(
              child: Text(
                'tracking.thanks'.tr(),
                style: const TextStyle(color: AppColors.textSecondary),
              ),
            ),
            const SizedBox(height: 32),
            if (!isParcel) ...[
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Theme.of(context).cardColor,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: Theme.of(context).dividerColor),
                ),
                child: TipSelector(
                  onTip: (amount) async {
                    await tracking.addTip(amount);
                    if (context.mounted) {
                      SnackbarHelper.showSuccess(
                          context, 'tracking.tip_thanks'.tr());
                    }
                  },
                ),
              ),
              const SizedBox(height: 20),
            ],
            CustomButton(
              text: 'tracking.rate_trip'.tr(),
              onPressed: () => context.go('/rating', extra: orderId),
            ),
            const SizedBox(height: 12),
            CustomButton(
              text: 'common.done'.tr(),
              isOutlined: true,
              onPressed: () => context.go(RouteNames.home),
            ),
          ],
        ),
      ),
    );
  }
}
