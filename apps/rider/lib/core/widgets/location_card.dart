import 'package:flutter/material.dart';

import '../constants/app_colors.dart';
import '../theme/app_dimensions.dart';

/// Pickup → Drop card with a dashed connector. Reusable across booking flows
/// (ride, parcel). Pass already-localized labels; addresses and trailing
/// widgets come from the caller's state.
class LocationCard extends StatelessWidget {
  final String pickupLabel;
  final String pickupAddress;
  final String dropLabel;
  final String dropAddress;
  final Widget? pickupTrailing; // e.g. a "Now" pill
  final Widget? dropTrailing; // e.g. an add-stop "+"
  final VoidCallback? onPickupTap;
  final VoidCallback? onDropTap;

  const LocationCard({
    super.key,
    required this.pickupLabel,
    required this.pickupAddress,
    required this.dropLabel,
    required this.dropAddress,
    this.pickupTrailing,
    this.dropTrailing,
    this.onPickupTap,
    this.onDropTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final onSurface = theme.colorScheme.onSurface;

    return Container(
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(AppDimensions.cardRadius),
        border: Border.all(color: theme.dividerColor),
      ),
      padding: const EdgeInsetsDirectional.fromSTEB(16, 14, 14, 14),
      child: Column(
        children: [
          _row(
            context,
            marker: Container(
              width: 14,
              height: 14,
              decoration: const BoxDecoration(
                  color: AppColors.primary, shape: BoxShape.circle),
              child: Center(
                child: Container(
                  width: 5,
                  height: 5,
                  decoration: const BoxDecoration(
                      color: Colors.white, shape: BoxShape.circle),
                ),
              ),
            ),
            label: pickupLabel,
            address: pickupAddress,
            onSurface: onSurface,
            trailing: pickupTrailing,
            onTap: onPickupTap,
          ),
          Padding(
            padding: const EdgeInsetsDirectional.only(start: 6),
            child: Align(
              alignment: AlignmentDirectional.centerStart,
              child: _dashed(theme),
            ),
          ),
          _row(
            context,
            marker: Container(
              width: 14,
              height: 14,
              decoration: BoxDecoration(
                  color: onSurface, borderRadius: BorderRadius.circular(4)),
            ),
            label: dropLabel,
            address: dropAddress,
            onSurface: onSurface,
            trailing: dropTrailing,
            onTap: onDropTap,
          ),
        ],
      ),
    );
  }

  Widget _row(
    BuildContext context, {
    required Widget marker,
    required String label,
    required String address,
    required Color onSurface,
    Widget? trailing,
    VoidCallback? onTap,
  }) {
    final content = Row(
      children: [
        marker,
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label,
                  style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppColors.primary)),
              const SizedBox(height: 2),
              Text(address,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style:
                      TextStyle(fontWeight: FontWeight.w500, color: onSurface)),
            ],
          ),
        ),
        if (trailing != null) ...[const SizedBox(width: 8), trailing],
      ],
    );
    return onTap == null
        ? content
        : InkWell(onTap: onTap, child: content);
  }

  Widget _dashed(ThemeData theme) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Column(
        children: List.generate(
          4,
          (_) => Container(
            width: 2,
            height: 4,
            margin: const EdgeInsets.symmetric(vertical: 1.5),
            color: theme.dividerColor,
          ),
        ),
      ),
    );
  }
}
