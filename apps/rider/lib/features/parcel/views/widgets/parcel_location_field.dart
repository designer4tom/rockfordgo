import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../location/model/place_model.dart';

/// Tappable field that opens the map picker and returns a [PlaceModel].
class ParcelLocationField extends StatelessWidget {
  final String label;
  final PlaceModel? place;
  final ValueChanged<PlaceModel> onPicked;

  const ParcelLocationField({
    super.key,
    required this.label,
    required this.place,
    required this.onPicked,
  });

  Future<void> _pick(BuildContext context) async {
    // Opens address search (with a map-pick option) and returns a resolved
    // place — address string + coordinates.
    final picked =
        await context.push<PlaceModel>('/location-search', extra: label);
    if (picked != null) onPicked(picked);
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: Theme.of(context).textTheme.titleSmall),
        const SizedBox(height: 6),
        InkWell(
          onTap: () => _pick(context),
          borderRadius: BorderRadius.circular(12),
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Theme.of(context).cardColor,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Theme.of(context).dividerColor),
            ),
            child: Row(
              children: [
                const Icon(Icons.location_on_outlined,
                    color: AppColors.primary),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    place?.address ?? 'parcel.pick_from_map'.tr(),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: place == null
                          ? AppColors.textSecondary
                          : Theme.of(context).colorScheme.onSurface,
                    ),
                  ),
                ),
                const Icon(Icons.chevron_right, color: AppColors.textSecondary),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
