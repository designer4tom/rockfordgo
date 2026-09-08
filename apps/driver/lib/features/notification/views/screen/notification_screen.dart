import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/empty_state.dart';
import '../../../../core/widgets/loading_indicator.dart';
import '../../model/notification_model.dart';
import '../../provider/notification_provider.dart';

class NotificationScreen extends StatefulWidget {
  const NotificationScreen({super.key});

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<NotificationProvider>().loadNotifications();
    });
  }

  IconData _iconFor(String type) {
    switch (type) {
      case 'payment':
      case 'withdrawal':
        return Icons.payments_outlined;
      case 'document_expiry':
        return Icons.description_outlined;
      case 'due_warning':
        return Icons.warning_amber_rounded;
      case 'order_request':
      case 'order_update':
        return Icons.local_taxi_outlined;
      default:
        return Icons.notifications_outlined;
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<NotificationProvider>();
    return Scaffold(
      appBar: AppBar(
        title: Text('notification.title'.tr()),
        actions: [
          if (p.unreadCount > 0)
            TextButton(
              onPressed: p.markAllRead,
              child: Text('notification.mark_all_read'.tr()),
            ),
        ],
      ),
      body: p.loading && p.notifications.isEmpty
          ? const LoadingIndicator()
          : p.notifications.isEmpty
              ? EmptyState(
                  icon: Icons.notifications_none,
                  title: 'notification.empty'.tr())
              : RefreshIndicator(
                  onRefresh: () => p.loadNotifications(),
                  child: ListView.separated(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    itemCount: p.notifications.length,
                    separatorBuilder: (_, _) => const Divider(height: 1),
                    itemBuilder: (_, i) => _tile(p, p.notifications[i]),
                  ),
                ),
    );
  }

  Widget _tile(NotificationProvider p, NotificationModel n) {
    return ListTile(
      onTap: n.read ? null : () => p.markRead([n.id]),
      leading: CircleAvatar(
        backgroundColor: AppColors.primary.withValues(alpha: 0.12),
        child: Icon(_iconFor(n.type), color: AppColors.primary, size: 20),
      ),
      title: Text(
        n.title,
        style: TextStyle(
            fontWeight: n.read ? FontWeight.normal : FontWeight.bold),
      ),
      subtitle: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(n.body),
          Text(Helpers.dateTime(n.createdAt),
              style: const TextStyle(
                  fontSize: 11, color: AppColors.textSecondary)),
        ],
      ),
      trailing: n.read
          ? null
          : const CircleAvatar(radius: 4, backgroundColor: AppColors.primary),
    );
  }
}
