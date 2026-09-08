import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';

class CodInput extends StatelessWidget {
  final bool isCod;
  final double? codAmount;
  final double deliveryCharge;
  final bool enabled;
  final ValueChanged<bool> onToggle;
  final ValueChanged<double?> onAmountChanged;

  const CodInput({
    super.key,
    required this.isCod,
    required this.codAmount,
    required this.deliveryCharge,
    this.enabled = true,
    required this.onToggle,
    required this.onAmountChanged,
  });

  @override
  Widget build(BuildContext context) {
    // Receiver pays only the product price; the delivery charge is billed to
    // the sender's wallet (or due) at delivery completion.
    final product = codAmount ?? 0;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SwitchListTile(
          contentPadding: EdgeInsets.zero,
          title: Text('parcel.cod'.tr()),
          subtitle: Text('parcel.cod_subtitle'.tr()),
          value: isCod,
          activeThumbColor: AppColors.primary,
          onChanged: enabled ? onToggle : null,
        ),
        if (isCod) ...[
          const SizedBox(height: 8),
          TextField(
            keyboardType: TextInputType.number,
            decoration: InputDecoration(
              labelText: 'parcel.product_price'.tr(),
              prefixText: '${Helpers.currencySymbol} ',
            ),
            onChanged: (v) => onAmountChanged(double.tryParse(v)),
          ),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.06),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Column(
              children: [
                _row('parcel.receiver_pays'.tr(), product,
                    'parcel.cod_receiver_formula'.tr()),
                const SizedBox(height: 6),
                _row('parcel.you_receive'.tr(), product,
                    'parcel.cod_you_receive_formula'.tr(),
                    color: AppColors.success),
                const SizedBox(height: 6),
                _row('parcel.delivery_charge'.tr(), deliveryCharge,
                    'parcel.cod_delivery_note'.tr(),
                    color: AppColors.danger),
              ],
            ),
          ),
        ],
      ],
    );
  }

  Widget _row(String label, double amount, String sub, {Color? color}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: const TextStyle(fontWeight: FontWeight.w500)),
            Text(sub,
                style: const TextStyle(
                    fontSize: 11, color: AppColors.textSecondary)),
          ],
        ),
        Text(
          Helpers.currency(amount),
          style: TextStyle(fontWeight: FontWeight.bold, color: color),
        ),
      ],
    );
  }
}
