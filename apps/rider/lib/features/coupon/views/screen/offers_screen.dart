import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/widgets/empty_state.dart';
import '../../../../core/widgets/loading_widget.dart';
import '../../provider/coupon_provider.dart';
import '../widgets/coupon_card.dart';

class OffersScreen extends StatefulWidget {
  const OffersScreen({super.key});

  @override
  State<OffersScreen> createState() => _OffersScreenState();
}

class _OffersScreenState extends State<OffersScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => context.read<CouponProvider>().loadCoupons(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<CouponProvider>();

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: Text('offers.title'.tr())),
      body: provider.isLoading
          ? const LoadingWidget()
          : provider.coupons.isEmpty
              ? EmptyState(
                  icon: Icons.local_offer_outlined,
                  title: 'offers.no_offers'.tr(),
                )
              : RefreshIndicator(
                  onRefresh: () => provider.loadCoupons(),
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                    children: [
                      // Hint banner
                      Container(
                        margin: const EdgeInsets.only(bottom: 8),
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: AppColors.primary.withValues(alpha: 0.06),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.info_outline,
                                size: 18, color: AppColors.primary),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text('offers.hint'.tr(),
                                  style: const TextStyle(
                                      fontSize: 12,
                                      color: AppColors.textSecondary)),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 6),
                      ...provider.coupons.map((c) => CouponCard(coupon: c)),
                    ],
                  ),
                ),
    );
  }
}
