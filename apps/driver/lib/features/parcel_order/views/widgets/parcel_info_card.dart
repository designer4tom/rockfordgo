import 'package:cached_network_image/cached_network_image.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../model/active_parcel_model.dart';

class ParcelInfoCard extends StatelessWidget {
  final ParcelDetails parcel;
  const ParcelInfoCard({super.key, required this.parcel});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.inventory_2_outlined, color: AppColors.primary),
              const SizedBox(width: 8),
              Text(parcel.type,
                  style: const TextStyle(
                      fontSize: 16, fontWeight: FontWeight.bold)),
            ],
          ),
          const SizedBox(height: 10),
          Wrap(
            spacing: 16,
            runSpacing: 6,
            children: [
              if (parcel.weight != null)
                _meta('parcel.weight'.tr(), parcel.weight!),
              if (parcel.size != null) _meta('parcel.size'.tr(), parcel.size!),
            ],
          ),
          if (parcel.note != null && parcel.note!.isNotEmpty) ...[
            const SizedBox(height: 10),
            Text('${'parcel.note'.tr()}: ${parcel.note}',
                style: TextStyle(color: Theme.of(context).hintColor)),
          ],
          if (parcel.photo != null && parcel.photo!.isNotEmpty) ...[
            const SizedBox(height: 10),
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: CachedNetworkImage(
                imageUrl: parcel.photo!,
                height: 120,
                width: double.infinity,
                fit: BoxFit.cover,
                errorWidget: (_, _, _) => const SizedBox.shrink(),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _meta(String label, String value) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label,
            style:
                const TextStyle(fontSize: 11, color: AppColors.textSecondary)),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w600)),
      ],
    );
  }
}
