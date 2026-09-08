import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/loading_widget.dart';
import '../../../service/model/vehicle_category_model.dart';
import '../../model/fare_estimate_model.dart';

class VehicleCategoryCard extends StatelessWidget {
  final VehicleCategoryModel category;
  final bool isSelected;
  final VoidCallback onTap;
  final FareEstimateModel? fareEstimate;
  final bool isFareLoading;

  const VehicleCategoryCard({
    super.key,
    required this.category,
    required this.isSelected,
    required this.onTap,
    this.fareEstimate,
    this.isFareLoading = false,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 6),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: isSelected
              ? AppColors.primary.withValues(alpha: 0.06)
              : Theme.of(context).cardColor,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: isSelected ? AppColors.primary : Theme.of(context).dividerColor,
            width: isSelected ? 2 : 1,
          ),
        ),
        child: Row(
          children: [
            CircleAvatar(
              radius: 24,
              backgroundColor: Theme.of(context).scaffoldBackgroundColor,
              child: Icon(
                category.name.toLowerCase().contains('bike')
                    ? Icons.two_wheeler
                    : Icons.directions_car,
                color: AppColors.primary,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Text(
                        category.name,
                        style: const TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 15,
                        ),
                      ),
                      const SizedBox(width: 8),
                      if (fareEstimate?.surgeActive ?? false)
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: AppColors.warning.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            'surge'.tr(),
                            style: const TextStyle(
                              fontSize: 10,
                              color: AppColors.warning,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${'seat'.tr(namedArgs: {'number': '${category.capacity}'})} • ${'min_away'.tr(namedArgs: {'number': '${category.estimatedArrival}'})}',
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            if (isFareLoading)
              const SizedBox(width: 64, child: ShimmerBox(height: 18))
            else
              Text(
                Helpers.currency(
                  double.tryParse(fareEstimate?.finalFare ?? '0') ?? 0,
                ),
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
          ],
        ),
      ),
    );
  }
}
