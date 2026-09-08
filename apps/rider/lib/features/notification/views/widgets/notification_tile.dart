import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/notification_model.dart';

class NotificationTile extends StatelessWidget {
  final NotificationModel notification;
  final VoidCallback onTap;

  const NotificationTile({
    super.key,
    required this.notification,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      onTap: onTap,
      tileColor: notification.isRead
          ? null
          : AppColors.primary.withValues(alpha: 0.04),
      leading: CircleAvatar(
        backgroundColor: AppColors.primary.withValues(alpha: 0.12),
        child: const Icon(Icons.notifications, color: AppColors.primary),
      ),
      title: Text(
        notification.title,
        style: TextStyle(
          fontWeight:
              notification.isRead ? FontWeight.normal : FontWeight.w600,
        ),
      ),
      subtitle: Text(
        notification.body,
        maxLines: 2,
        overflow: TextOverflow.ellipsis,
      ),
      trailing: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.end,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            _formattedTime(),
            style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
          ),
          if (!notification.isRead) ...[
            const SizedBox(height: 6),
            const Icon(Icons.circle, size: 9, color: AppColors.primary),
          ],
        ],
      ),
    );
  }

  /// Render the timestamp in a consistent, readable format (e.g.
  /// "22 Jun 2026, 10:30 AM"); fall back to the raw value if unparseable.
  String _formattedTime() {
    final dt = DateTime.tryParse(notification.createdAt);
    if (dt == null) return notification.createdAt;
    return Helpers.formatDateTime(dt.toLocal());
  }
}
