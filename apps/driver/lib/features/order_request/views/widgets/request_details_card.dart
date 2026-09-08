import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/order_request_model.dart';

class RequestDetailsCard extends StatelessWidget {
  final OrderRequestModel request;
  const RequestDetailsCard({super.key, required this.request});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        // Estimated earning — the headline number.
        Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 16),
          decoration: BoxDecoration(
            color: AppColors.success.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(16),
          ),
          child: Column(
            children: [
              Text('order.estimated_earning'.tr(),
                  style: TextStyle(color: Theme.of(context).hintColor)),
              const SizedBox(height: 4),
              Text(
                Helpers.money(double.tryParse(request.estimatedEarning) ?? 0),
                style: const TextStyle(
                  fontSize: 32,
                  fontWeight: FontWeight.bold,
                  color: AppColors.success,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        _locationRow(
          icon: Icons.my_location,
          color: AppColors.secondary,
          title: 'order.pickup'.tr(),
          address: request.pickup.address,
          trailing: request.distanceToPickupKm != null
              ? '${request.distanceToPickupKm!.toStringAsFixed(1)} ${'order.km_away'.tr()}'
              : null,
        ),
        const Padding(
          padding: EdgeInsets.only(left: 11),
          child: SizedBox(
            height: 24,
            child: VerticalDivider(width: 2, thickness: 2),
          ),
        ),
        _locationRow(
          icon: Icons.location_on,
          color: AppColors.danger,
          title: 'order.dropoff'.tr(),
          address: request.drop.address,
          trailing: '${request.distanceKm.toStringAsFixed(1)} ${'order.km_trip'.tr()}',
        ),
        const SizedBox(height: 16),
        Row(
          children: [
            Expanded(
              child: _infoChip(
                Icons.payments_outlined,
                'order.payment'.tr(),
                request.paymentMethod.toUpperCase(),
              ),
            ),
            const SizedBox(width: 12),
            if (request.isParcel && request.isCod)
              Expanded(
                child: _infoChip(
                  Icons.account_balance_wallet_outlined,
                  'order.collect_cod'.tr(),
                  Helpers.money(double.tryParse(request.codAmount ?? '0') ?? 0),
                  highlight: true,
                ),
              ),
          ],
        ),
      ],
    );
  }

  Widget _locationRow({
    required IconData icon,
    required Color color,
    required String title,
    required String address,
    String? trailing,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, color: color, size: 22),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title,
                  style: const TextStyle(
                      fontSize: 12, color: AppColors.textSecondary)),
              const SizedBox(height: 2),
              Text(
                address.isEmpty ? '—' : address,
                style: const TextStyle(
                    fontSize: 14, fontWeight: FontWeight.w500),
              ),
            ],
          ),
        ),
        if (trailing != null) ...[
          const SizedBox(width: 8),
          Text(trailing,
              style: const TextStyle(
                  fontSize: 12,
                  color: AppColors.textSecondary,
                  fontWeight: FontWeight.w600)),
        ],
      ],
    );
  }

  Widget _infoChip(IconData icon, String label, String value,
      {bool highlight = false}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: highlight
            ? AppColors.warning.withValues(alpha: 0.12)
            : AppColors.background,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Icon(icon,
              size: 18,
              color: highlight ? AppColors.warning : AppColors.textSecondary),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label,
                    style: const TextStyle(
                        fontSize: 11, color: AppColors.textSecondary)),
                Text(value,
                    style: const TextStyle(
                        fontSize: 14, fontWeight: FontWeight.bold)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
