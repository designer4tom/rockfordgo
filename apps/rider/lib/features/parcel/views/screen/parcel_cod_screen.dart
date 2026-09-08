import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../wallet/provider/wallet_provider.dart';
import '../../provider/parcel_provider.dart';
import '../widgets/cod_input.dart';
import '../widgets/parcel_step_indicator.dart';

class ParcelCodScreen extends StatefulWidget {
  const ParcelCodScreen({super.key});

  @override
  State<ParcelCodScreen> createState() => _ParcelCodScreenState();
}

class _ParcelCodScreenState extends State<ParcelCodScreen> {
  @override
  void initState() {
    super.initState();
    // Estimate gives us the delivery charge used in the COD info box, and a
    // fresh wallet fetch keeps can_place_cod (due-limit gate) current.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ParcelProvider>().loadEstimate();
      context.read<WalletProvider>().loadBalance();
    });
  }

  void _next() {
    final parcel = context.read<ParcelProvider>();
    parcel.currentStep = 4;
    context.push('/parcel-confirm');
  }

  @override
  Widget build(BuildContext context) {
    final parcel = context.watch<ParcelProvider>();
    final canPlaceCod = context.watch<WalletProvider>().canPlaceCod;
    if (!canPlaceCod && parcel.isCod) {
      // COD is blocked by the due limit — make sure it can't stay selected.
      WidgetsBinding.instance.addPostFrameCallback(
        (_) => context.read<ParcelProvider>().setCod(false, null),
      );
    }

    return Scaffold(
      appBar: AppBar(title: Text('parcel.title'.tr())),
      body: Column(
        children: [
          const ParcelStepIndicator(currentStep: 3),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('parcel.cod'.tr(),
                      style: Theme.of(context).textTheme.titleLarge),
                  const SizedBox(height: 16),
                  if (!canPlaceCod) ...[
                    _dueBlockedBanner(context),
                    const SizedBox(height: 16),
                  ],
                  CodInput(
                    isCod: parcel.isCod,
                    codAmount: parcel.codAmount,
                    deliveryCharge: parcel.deliveryCharge,
                    enabled: canPlaceCod,
                    onToggle: (v) => parcel.setCod(v, parcel.codAmount),
                    onAmountChanged: (v) => parcel.setCod(parcel.isCod, v),
                  ),
                ],
              ),
            ),
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: CustomButton(text: 'common.next'.tr(), onPressed: _next),
            ),
          ),
        ],
      ),
    );
  }

  Widget _dueBlockedBanner(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.danger.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.error_outline,
                  color: AppColors.danger, size: 20),
              const SizedBox(width: 8),
              Expanded(
                child: Text('parcel.cod_blocked_due'.tr(),
                    style: const TextStyle(
                        fontSize: 13, color: AppColors.danger)),
              ),
            ],
          ),
          Align(
            alignment: AlignmentDirectional.centerEnd,
            child: TextButton(
              onPressed: () => context.go(RouteNames.wallet),
              child: Text('wallet.go_to_wallet'.tr(),
                  style: const TextStyle(fontWeight: FontWeight.w600)),
            ),
          ),
        ],
      ),
    );
  }
}
