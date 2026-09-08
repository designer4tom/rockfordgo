import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../ride_order/provider/ride_order_provider.dart';
import '../../provider/order_request_provider.dart';
import '../widgets/countdown_timer.dart';
import '../widgets/request_details_card.dart';

/// Full-screen, urgent order request popup shown for [timeoutSeconds].
class OrderRequestScreen extends StatefulWidget {
  const OrderRequestScreen({super.key});

  @override
  State<OrderRequestScreen> createState() => _OrderRequestScreenState();
}

class _OrderRequestScreenState extends State<OrderRequestScreen> {
  @override
  Widget build(BuildContext context) {
    final provider = context.watch<OrderRequestProvider>();
    final request = provider.currentRequest;

    // Timed out / dismissed while open → close this screen.
    if (request == null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted && context.canPop()) context.pop();
      });
      return const SizedBox.shrink();
    }

    return PopScope(
      canPop: false, // force an explicit accept/reject decision
      child: Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        body: SafeArea(
          child: Column(
            children: [
              const SizedBox(height: 16),
              CountdownTimer(
                remaining: provider.remainingSeconds,
                total: request.timeoutSeconds,
              ),
              const SizedBox(height: 12),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  request.isParcel
                      ? 'order.parcel_delivery'.tr()
                      : 'order.ride_request'.tr(),
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    color: AppColors.primary,
                    letterSpacing: 0.5,
                  ),
                ),
              ),
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(20),
                  child: RequestDetailsCard(request: request),
                ),
              ),
              _actionBar(provider),
            ],
          ),
        ),
      ),
    );
  }

  Widget _actionBar(OrderRequestProvider provider) {
    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Expanded(
              child: CustomButton(
                label: 'common.reject'.tr(),
                outlined: true,
                color: AppColors.danger,
                onPressed: provider.processing
                    ? null
                    : () async {
                        await provider.rejectOrder();
                        if (mounted && context.canPop()) context.pop();
                      },
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              flex: 2,
              child: CustomButton(
                label: 'common.accept'.tr(),
                loading: provider.processing,
                onPressed: () => _accept(provider),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _accept(OrderRequestProvider provider) async {
    final req = provider.currentRequest;
    final ok = await provider.acceptOrder();
    if (!mounted) return;
    if (ok) {
      final isParcel = req?.isParcel ?? false;
      // Hand the authoritative fare from the accept response to the ride screen
      // so it shows the exact backend figures (the order-detail endpoint may
      // omit the full breakdown).
      final accepted = provider.acceptedRide;
      if (!isParcel && accepted != null) {
        context.read<RideOrderProvider>().seedAcceptedFare(accepted.fare);
      }
      if (context.canPop()) context.pop();
      final route = isParcel ? RouteNames.parcelOrder : RouteNames.rideOrder;
      context.go(route, extra: req?.orderId);
    } else {
      AppSnackbar.error(
          context, provider.error ?? 'order.could_not_accept'.tr());
    }
  }
}
