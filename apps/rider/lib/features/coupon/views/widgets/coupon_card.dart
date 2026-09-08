import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../model/coupon_offer_model.dart';

/// Ticket-style coupon card. Tapping anywhere (or the Copy button) copies the
/// code to the clipboard.
class CouponCard extends StatelessWidget {
  final CouponOfferModel coupon;

  const CouponCard({super.key, required this.coupon});

  void _copy(BuildContext context) {
    Clipboard.setData(ClipboardData(text: coupon.code));
    HapticFeedback.lightImpact();
    SnackbarHelper.showSuccess(
        context, 'offers.copied'.tr(namedArgs: {'code': coupon.code}));
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final onSurface = theme.colorScheme.onSurface;

    return GestureDetector(
      onTap: () => _copy(context),
      child: Container(
        margin: const EdgeInsets.only(bottom: 14),
        decoration: BoxDecoration(
          color: theme.cardColor,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 10,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: IntrinsicHeight(
          child: Row(
            children: [
              // Left discount strip
              Container(
                width: 88,
                decoration: const BoxDecoration(
                  color: AppColors.primary,
                  borderRadius: BorderRadiusDirectional.horizontal(
                    start: Radius.circular(16),
                  ),
                ),
                child: Center(
                  child: Padding(
                    padding: const EdgeInsets.all(8),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.local_offer,
                            color: Colors.white, size: 22),
                        const SizedBox(height: 6),
                        Text(
                          coupon.isPercentage
                              ? '${coupon.discountLabel.split('%').first}%'
                              : coupon.discountLabel.replaceAll(' OFF', ''),
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                              fontSize: 18),
                        ),
                        const Text('OFF',
                            style: TextStyle(
                                color: Colors.white70,
                                fontSize: 11,
                                letterSpacing: 1)),
                      ],
                    ),
                  ),
                ),
              ),
              // Body
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(14, 12, 8, 12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      // Code chip (dashed)
                      Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: AppColors.primary.withValues(alpha: 0.08),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(
                            color: AppColors.primary.withValues(alpha: 0.5),
                          ),
                        ),
                        child: Text(
                          coupon.code,
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            letterSpacing: 1.5,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                      if ((coupon.description ?? '').isNotEmpty) ...[
                        const SizedBox(height: 6),
                        Text(
                          coupon.description!,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(fontSize: 13, color: onSurface),
                        ),
                      ],
                      const SizedBox(height: 6),
                      Text(
                        _conditions(),
                        style: const TextStyle(
                            fontSize: 11, color: AppColors.textSecondary),
                      ),
                    ],
                  ),
                ),
              ),
              // Copy button
              Padding(
                padding: const EdgeInsetsDirectional.only(end: 8),
                child: TextButton.icon(
                  onPressed: () => _copy(context),
                  icon: const Icon(Icons.copy, size: 16),
                  label: Text('offers.copy'.tr()),
                  style: TextButton.styleFrom(
                    foregroundColor: AppColors.primary,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _conditions() {
    final parts = <String>[];
    final min = double.tryParse(coupon.minOrderAmount) ?? 0;
    if (min > 0) {
      parts.add('offers.min_order'
          .tr(namedArgs: {'amount': coupon.minOrderDisplay}));
    }
    final valid = coupon.validUntil;
    if (valid != null && valid.isNotEmpty) {
      final dt = DateTime.tryParse(valid);
      parts.add('offers.valid_till'.tr(namedArgs: {
        'date': dt != null ? Helpers.formatDate(dt) : valid,
      }));
    }
    return parts.join(' · ');
  }
}
