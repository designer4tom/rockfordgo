import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../provider/parcel_order_provider.dart';

class ParcelCompleteScreen extends StatelessWidget {
  final int orderId;
  const ParcelCompleteScreen({super.key, required this.orderId});

  @override
  Widget build(BuildContext context) {
    final p = context.read<ParcelOrderProvider>();
    final parcel = p.activeParcel;
    final earning = double.tryParse(parcel?.deliveryCharge ?? '0') ?? 0;
    final cod = parcel?.codCollectible ?? 0;

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
              Text('parcel.delivery_complete'.tr(),
                  style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              Text('parcel.you_earned'.tr(namedArgs: {'amount': Helpers.money(earning)}),
                  style: const TextStyle(
                      fontSize: 18,
                      color: AppColors.success,
                      fontWeight: FontWeight.w600)),
              const SizedBox(height: 24),
              if (parcel?.isCod ?? false)
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Theme.of(context).scaffoldBackgroundColor,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Column(
                    children: [
                      _row('parcel.cod_collected'.tr(), Helpers.money(cod)),
                      const Divider(),
                      _row('parcel.sender_payout'.tr(), Helpers.money(cod),
                          bold: true),
                    ],
                  ),
                ),
              const SizedBox(height: 28),
              CustomButton(
                label: 'common.done'.tr(),
                onPressed: () {
                  p.clear();
                  context.go(RouteNames.home);
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _row(String l, String v, {bool bold = false}) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 3),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(l,
                style: TextStyle(
                    fontWeight: bold ? FontWeight.bold : FontWeight.normal)),
            Text(v,
                style: TextStyle(
                    fontWeight: bold ? FontWeight.bold : FontWeight.normal)),
          ],
        ),
      );
}
