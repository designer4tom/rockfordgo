import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../service/model/vehicle_category_model.dart';

/// A single "Choose a ride" option card, driven by the existing
/// [VehicleCategoryModel] + ride state. UI only — selection is reported back
/// through [onTap] (which the screen wires to `RideProvider.selectCategory`).
class VehicleOptionCard extends StatelessWidget {
  final VehicleCategoryModel category;
  final bool selected;
  final bool fareLoading;
  final String fareText;
  final bool surge;
  final VoidCallback onTap;

  const VehicleOptionCard({
    super.key,
    required this.category,
    required this.selected,
    required this.fareLoading,
    required this.fareText,
    required this.surge,
    required this.onTap,
  });

  bool get _isBike => category.name.toLowerCase().contains('bike');

  /// Short muted description (presentation only — no such field on the model).
  String _description() {
    final n = category.name.toLowerCase();
    if (n.contains('bike')) return 'ride.desc_bike'.tr();
    if (n.contains('comfort')) return 'ride.desc_comfort'.tr();
    if (n.contains('premium') || n.contains('luxury')) {
      return 'ride.desc_premium'.tr();
    }
    if (n.contains('economy') || n.contains('car')) {
      return 'ride.desc_economy'.tr();
    }
    return 'ride.desc_default'.tr();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final onSurface = theme.colorScheme.onSurface;

    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 6),
        padding: const EdgeInsets.symmetric(horizontal: 14,vertical: 10),
        decoration: BoxDecoration(
          color: theme.cardColor,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              blurRadius: 6,
              offset: const Offset(0, 3),
            ),
          ],
          border: Border.all(
            color: selected ? AppColors.primary.withValues(alpha: 0.3) : Colors.transparent,
            width: selected ? 1.5 : 1,
          ),
        ),
        child: Row(
          children: [
            _thumbnail(),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Flexible(
                        child: Text(
                          category.name,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                              fontWeight: FontWeight.w600,
                              fontSize: 15,
                              color: onSurface),
                        ),
                      ),
                      const SizedBox(width: 8),
                      const Icon(Icons.person_outline,
                          size: 14, color: AppColors.textSecondary),
                      const SizedBox(width: 2),
                      Text('${category.capacity}',
                          style: const TextStyle(
                              fontSize: 13, color: AppColors.textSecondary)),
                      if (surge) ...[
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: AppColors.warning.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text('ride.surge'.tr(),
                              style: const TextStyle(
                                  fontSize: 10,
                                  color: AppColors.warning,
                                  fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 3),
                  Text(
                    _description(),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                        fontSize: 12, color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.access_time,
                          size: 14, color: AppColors.textSecondary),
                      const SizedBox(width: 4),
                      Text(
                        'ride.min_away'.tr(
                            namedArgs: {'number': '${category.estimatedArrival}'}),
                        style: const TextStyle(
                            fontSize: 12, color: AppColors.textSecondary),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                if (fareLoading)
                  const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                else
                  Text(fareText,
                      style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                          color: onSurface)),
                const SizedBox(width: 8),
                _selectionIndicator(theme),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _thumbnail() {
    final fallback = Icon(
      _isBike ? Icons.two_wheeler : Icons.directions_car,
      color: AppColors.primary,
    );
    return Container(
      width: 64,
      height: 52,
      alignment: Alignment.center,
      // decoration: BoxDecoration(
      //   color: AppColors.primary.withValues(alpha: 0.08),
      //   borderRadius: BorderRadius.circular(12),
      // ),
      child: (category.icon != null && category.icon!.isNotEmpty)
          ? ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: Image.network(
                category.icon!,
                width: 56,
                height: 44,
                fit: BoxFit.contain,
                errorBuilder: (context, error, stack) => fallback,
              ),
            )
          : fallback,
    );
  }

  Widget _selectionIndicator(ThemeData theme) {
    if (selected) {
      return Container(
        width: 22,
        height: 22,
        decoration: const BoxDecoration(
            color: AppColors.primary, shape: BoxShape.circle),
        child: const Icon(Icons.check, size: 18, color: Colors.white),
      );
    }
    return Container(
      width: 22,
      height: 22,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: Border.all(color: theme.dividerColor, width: 1.5),
      ),
    );
  }
}
