import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/theme/app_text_styles.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../auth/provider/auth_provider.dart';
import '../../provider/ride_provider.dart';
import '../widgets/coupon_input.dart';
import '../widgets/fare_breakdown_card.dart';
import '../widgets/payment_method_selector.dart';
import '../widgets/schedule_picker.dart';

class BookingConfirmScreen extends StatelessWidget {
  const BookingConfirmScreen({super.key});

  Future<void> _applyCoupon(BuildContext context, String code) async {
    final ride = context.read<RideProvider>();
    final ok = await ride.applyCoupon(code);
    if (!context.mounted) return;
    if (ok) {
      SnackbarHelper.showSuccess(context, 'coupon_applied'.tr());
    } else {
      SnackbarHelper.showError(context, ride.error ?? 'coupon_invalid'.tr());
    }
  }

  Future<void> _confirm(BuildContext context) async {
    final ride = context.read<RideProvider>();
    final ok = await ride.bookRide();
    if (!context.mounted) return;
    if (ok) {
      context.push('/searching-driver');
    } else {
      SnackbarHelper.showError(context, ride.error ?? 'ride.booking_failed'.tr());
    }
  }

  @override
  Widget build(BuildContext context) {
    final ride = context.watch<RideProvider>();
    final fare = ride.selectedFare;
    final walletBalance = double.tryParse(
          context.select<AuthProvider, String>(
              (p) => p.user?.walletBalance ?? '0.00'),
        ) ??
        0;

    if (fare == null) {
      return Scaffold(
        appBar: AppBar(title: Text('confirm_booking'.tr())),
        body: Center(child: Text('ride.no_ride_selected'.tr())),
      );
    }

    return Scaffold(
      appBar: AppBar(title: Text('ride.confirm_booking'.tr())),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text('fare_breakdown'.tr(), style: AppTextStyles.title),
          const SizedBox(height: 8),
          FareBreakdownCard(
            breakdown: fare.breakdown,
            couponDiscount: ride.couponDiscount,
            total: ride.payableAmount,
          ),
          const SizedBox(height: 20),
          Text('coupon'.tr(), style: AppTextStyles.title),
          const SizedBox(height: 8),
          CouponInput(
            appliedCoupon: ride.appliedCoupon,
            onApply: (code) async {
              await _applyCoupon(context, code);
              return ride.appliedCoupon != null;
            },
            onRemove: ride.removeCoupon,
          ),
          const SizedBox(height: 20),
          Text('payment_method'.tr(), style: AppTextStyles.title),
          const SizedBox(height: 8),
          PaymentMethodSelector(
            selected: ride.paymentMethod,
            walletBalance: walletBalance,
            payableAmount: ride.payableAmount,
            onChanged: ride.setPaymentMethod,
            onTopUp: () =>
                SnackbarHelper.showInfo(context, 'ride.topup_coming_soon'.tr()),
          ),
          const SizedBox(height: 20),
          Text('schedule'.tr(), style: AppTextStyles.title),
          const SizedBox(height: 8),
          SchedulePicker(
            scheduledAt: ride.scheduledAt,
            onScheduled: ride.setSchedule,
          ),
          const SizedBox(height: 24),
        ],
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: CustomButton(
            text: '${'confirm_booking'.tr()} • ${Helpers.currency(ride.payableAmount)}',
            isLoading: ride.isLoading,
            onPressed: () => _confirm(context),
          ),
        ),
      ),
    );
  }
}
