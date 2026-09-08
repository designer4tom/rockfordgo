import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../location/model/place_model.dart';

class PlaceSearchResult extends StatelessWidget {
  final PlaceModel place;
  final VoidCallback onTap;

  const PlaceSearchResult({
    super.key,
    required this.place,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: const Icon(Icons.location_on_outlined,
          color: AppColors.textSecondary),
      title: Text(
        place.name ?? place.address,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: const TextStyle(fontWeight: FontWeight.w500),
      ),
      subtitle: place.name != null
          ? Text(
              place.address,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(color: AppColors.textSecondary),
            )
          : null,
      onTap: onTap,
    );
  }
}
