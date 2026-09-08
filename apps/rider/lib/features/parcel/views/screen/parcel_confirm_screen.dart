import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../ride/views/widgets/coupon_input.dart';
import '../../provider/parcel_provider.dart';
import '../widgets/parcel_step_indicator.dart';

class ParcelConfirmScreen extends StatefulWidget {
  const ParcelConfirmScreen({super.key});

  @override
  State<ParcelConfirmScreen> createState() => _ParcelConfirmScreenState();
}

class _ParcelConfirmScreenState extends State<ParcelConfirmScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final parcel = context.read<ParcelProvider>();
      if (parcel.estimate == null) parcel.loadEstimate();
    });
  }

  Future<void> _confirm() async {
    final parcel = context.read<ParcelProvider>();
    final booking = await parcel.bookParcel();
    if (!mounted) return;
    if (booking != null) {
      SnackbarHelper.showSuccess(context,
          'parcel.booked'.tr(namedArgs: {'number': booking.orderNumber.toString()}));
      final orderId = booking.orderId;
      parcel.reset();
      context.go(RouteNames.parcelTracking, extra: orderId);
    } else if (parcel.isDueLimitBlocked) {
      _showDueBlockedDialog(parcel.error);
    } else {
      SnackbarHelper.showError(context, parcel.error ?? 'parcel.booking_failed'.tr());
    }
  }

  /// The sender's outstanding delivery-charge due has reached the limit —
  /// COD bookings are blocked until it is cleared via a wallet top-up.
  void _showDueBlockedDialog(String? message) {
    showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('parcel.cod_blocked_title'.tr()),
        content: Text(message ?? 'parcel.cod_blocked_due'.tr()),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text('common.cancel'.tr()),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              context.go(RouteNames.wallet);
            },
            child: Text('wallet.go_to_wallet'.tr()),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final parcel = context.watch<ParcelProvider>();
    final timingOptions = parcel.estimate?.paymentTimingOptions ?? const ['before'];

    return Scaffold(
      appBar: AppBar(title: Text('parcel.title'.tr())),
      body: Column(
        children: [
          const ParcelStepIndicator(currentStep: 4),
          Expanded(
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                _summaryCard(parcel),
                const SizedBox(height: 16),
                _chargeCard(parcel),
                const SizedBox(height: 16),
                CouponInput(
                  appliedCoupon: parcel.appliedCoupon,
                  onApply: (code) async {
                    final ok = await parcel.applyCoupon(code);
                    if (context.mounted && !ok) {
                      SnackbarHelper.showError(
                          context, parcel.error ?? 'ride.coupon_invalid'.tr());
                    }
                    return ok;
                  },
                  onRemove: parcel.removeCoupon,
                ),
                const SizedBox(height: 20),
                if (timingOptions.length > 1) ...[
                  Text('parcel.payment_when'.tr(),
                      style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 8),
                  _timingSelector(parcel, timingOptions),
                  const SizedBox(height: 20),
                ],
                if (parcel.paymentTiming == 'before') ...[
                  Text('ride.payment_method'.tr(),
                      style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 8),
                  _paymentMethods(parcel),
                ],
              ],
            ),
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: CustomButton(
                text: '${'parcel.confirm_booking'.tr()} • ${Helpers.currency(parcel.payableAmount)}',
                isLoading: parcel.isLoading,
                onPressed: _confirm,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _summaryCard(ParcelProvider p) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _personLine(Icons.my_location, AppColors.secondary,
              '${p.senderName} • ${p.senderPhone}', p.pickup?.address ?? ''),
          Padding(
            padding: const EdgeInsets.only(left: 9),
            child: SizedBox(
              height: 16,
              child: VerticalDivider(
                  width: 2, color: Theme.of(context).dividerColor),
            ),
          ),
          _personLine(Icons.location_on, AppColors.danger,
              '${p.receiverName} • ${p.receiverPhone}', p.drop?.address ?? ''),
          const Divider(height: 20),
          Text(
            '${_typeLabel(p.parcelType)} • ${p.weight.toStringAsFixed(1)} kg • ${_sizeLabel(p.size)}',
            style: const TextStyle(color: AppColors.textSecondary),
          ),
          if (p.isCod && p.codAmount != null)
            Padding(
              padding: const EdgeInsets.only(top: 6),
              child: Text(
                'COD: ${Helpers.currency(p.codAmount!)}',
                style: const TextStyle(
                    color: AppColors.primary, fontWeight: FontWeight.w600),
              ),
            ),
        ],
      ),
    );
  }

  Widget _personLine(IconData icon, Color color, String title, String addr) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: color),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(fontWeight: FontWeight.w500)),
              Text(addr,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                      fontSize: 12, color: AppColors.textSecondary)),
            ],
          ),
        ),
      ],
    );
  }

  Widget _chargeCard(ParcelProvider p) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        children: [
          _row('parcel.delivery_charge'.tr(), p.deliveryCharge),
          if (p.couponDiscount > 0)
            _row('ride.coupon_discount'.tr(), -p.couponDiscount,
                color: AppColors.success),
          const Divider(height: 20),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('ride.total'.tr(),
                  style: const TextStyle(
                      fontWeight: FontWeight.bold, fontSize: 16)),
              Text(
                Helpers.currency(p.payableAmount),
                style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                    color: AppColors.primary),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _row(String label, double amount, {Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: AppColors.textSecondary)),
          Text(
            '${amount < 0 ? '-' : ''}${Helpers.currency(amount.abs())}',
            style: TextStyle(color: color),
          ),
        ],
      ),
    );
  }

  Widget _timingSelector(ParcelProvider p, List<String> options) {
    return Row(
      children: options.map((o) {
        final selected = p.paymentTiming == o;
        return Expanded(
          child: GestureDetector(
            onTap: () => p.setPaymentTiming(o),
            child: Container(
              margin: const EdgeInsets.symmetric(horizontal: 4),
              padding: const EdgeInsets.symmetric(vertical: 12),
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: selected ? AppColors.primary : Theme.of(context).cardColor,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(
                    color: selected
                        ? AppColors.primary
                        : Theme.of(context).dividerColor),
              ),
              child: Text(
                o == 'before' ? 'parcel.before'.tr() : 'parcel.after'.tr(),
                style: TextStyle(
                  color: selected
                      ? Colors.white
                      : Theme.of(context).colorScheme.onSurface,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
          ),
        );
      }).toList(),
    );
  }

  Widget _paymentMethods(ParcelProvider p) {
    const methods = [
      ('cash', 'ride.cash'),
      ('online', 'ride.online'),
      ('wallet', 'ride.wallet'),
    ];
    return Column(
      children: methods.map((m) {
        final selected = p.paymentMethod == m.$1;
        return GestureDetector(
          onTap: () => p.setPaymentMethod(m.$1),
          child: Container(
            margin: const EdgeInsets.symmetric(vertical: 4),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Theme.of(context).cardColor,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: selected
                    ? AppColors.primary
                    : Theme.of(context).dividerColor,
                width: selected ? 2 : 1,
              ),
            ),
            child: Row(
              children: [
                Text(m.$2.tr()),
                const Spacer(),
                Icon(
                  selected
                      ? Icons.radio_button_checked
                      : Icons.radio_button_unchecked,
                  color: selected
                      ? AppColors.primary
                      : Theme.of(context).dividerColor,
                ),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }

  String _typeLabel(String t) => switch (t) {
        'fragile' => 'parcel.type_fragile'.tr(),
        'document' => 'parcel.type_document'.tr(),
        _ => 'parcel.type_normal'.tr(),
      };

  String _sizeLabel(String s) => switch (s) {
        'medium' => 'parcel.size_medium'.tr(),
        'large' => 'parcel.size_large'.tr(),
        _ => 'parcel.size_small'.tr(),
      };
}
