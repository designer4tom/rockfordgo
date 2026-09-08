import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../../ride/model/ride_status_model.dart';

class DriverInfoCard extends StatelessWidget {
  final DriverInfo driver;
  final VoidCallback onCall;
  final VoidCallback onShare;
  final VoidCallback? onChat;

  const DriverInfoCard({
    super.key,
    required this.driver,
    required this.onCall,
    required this.onShare,
    this.onChat,
  });

  /// Driver photo with graceful fallbacks: resolves relative URLs against the
  /// API host and falls back to a person icon if missing or it fails to load.
  Widget _avatar(BuildContext context) {
    final url = Helpers.imageUrl(driver.avatar);
    const fallback = Icon(Icons.person, color: AppColors.primary, size: 28);
    return CircleAvatar(
      radius: 28,
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      child: ClipOval(
        child: url == null
            ? fallback
            : Image.network(
                url,
                width: 56,
                height: 56,
                fit: BoxFit.cover,
                errorBuilder: (_, _, _) => fallback,
              ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final v = driver.vehicle;
    return Column(
      children: [
        Row(
          children: [
            _avatar(context),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    driver.name,
                    style: const TextStyle(
                        fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  Row(
                    children: [
                      const Icon(Icons.star, size: 14, color: AppColors.accent),
                      const SizedBox(width: 4),
                      Text(driver.rating,
                          style: const TextStyle(
                              fontSize: 13, color: AppColors.textSecondary)),
                    ],
                  ),
                ],
              ),
            ),
            if (driver.estimatedArrival > 0)
              Column(
                children: [
                  Text(
                    '${driver.estimatedArrival}',
                    style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 18,
                        color: AppColors.primary),
                  ),
                  Text('tracking.minutes_short'.tr(),
                      style: const TextStyle(
                          fontSize: 11, color: AppColors.textSecondary)),
                ],
              ),
          ],
        ),
        if (v.model.isNotEmpty || v.plateNumber.isNotEmpty) ...[
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              children: [
                const Icon(Icons.directions_car,
                    color: AppColors.textSecondary),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    [v.color, v.model].where((s) => s.isNotEmpty).join(' '),
                    style: const TextStyle(fontWeight: FontWeight.w500),
                  ),
                ),
                Text(
                  v.plateNumber,
                  style: const TextStyle(
                      fontWeight: FontWeight.bold, letterSpacing: 1),
                ),
              ],
            ),
          ),
        ],
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: onCall,
                icon: const Icon(Icons.call, size: 18),
                label: Text('tracking.call'.tr()),
              ),
            ),
            if (onChat != null) ...[
              const SizedBox(width: 12),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: onChat,
                  icon: const Icon(Icons.chat_bubble_outline, size: 18),
                  label: Text('chat.title'.tr()),
                ),
              ),
            ],
            const SizedBox(width: 12),
            Expanded(
              child: OutlinedButton.icon(
                onPressed: onShare,
                icon: const Icon(Icons.share, size: 18),
                label: Text('tracking.share'.tr()),
              ),
            ),
          ],
        ),
      ],
    );
  }
}
