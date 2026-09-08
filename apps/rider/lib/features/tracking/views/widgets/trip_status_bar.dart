import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';

class TripStatusBar extends StatelessWidget {
  final String status;

  const TripStatusBar({super.key, required this.status});

  ({String label, IconData icon}) get _display => switch (status) {
        'accepted' || 'go_to_pickup' => (
            label: 'tracking.driver_coming'.tr(),
            icon: Icons.directions_car,
          ),
        'confirm_arrival' => (
            label: 'tracking.driver_arrived'.tr(),
            icon: Icons.location_on,
          ),
        'picked_up' || 'start_ride' => (
            label: 'tracking.trip_ongoing'.tr(),
            icon: Icons.navigation,
          ),
        'dropped_off' => (
            label: 'tracking.arrived_destination'.tr(),
            icon: Icons.flag,
          ),
        'completed' => (
            label: 'tracking.completed'.tr(),
            icon: Icons.check_circle,
          ),
        'picked' || 'in_transit' => (
            label: 'tracking.parcel_in_transit'.tr(),
            icon: Icons.local_shipping,
          ),
        'delivered' => (
            label: 'tracking.delivered'.tr(),
            icon: Icons.check_circle,
          ),
        _ => (label: 'tracking.waiting'.tr(), icon: Icons.hourglass_empty),
      };

  @override
  Widget build(BuildContext context) {
    final d = _display;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(12),
        boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 6)],
      ),
      child: Row(
        children: [
          Icon(d.icon, color: AppColors.primary),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              d.label,
              style: const TextStyle(
                fontWeight: FontWeight.w600,
                fontSize: 15,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
