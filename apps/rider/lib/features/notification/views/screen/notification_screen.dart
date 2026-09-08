import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../../core/widgets/empty_state.dart';
import '../../../../core/widgets/loading_widget.dart';
import '../../provider/notification_provider.dart';
import '../widgets/notification_tile.dart';

class NotificationScreen extends StatefulWidget {
  const NotificationScreen({super.key});

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen> {
  final _scroll = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => context.read<NotificationProvider>()
          .loadNotifications(refresh: true),
    );
    _scroll.addListener(() {
      if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 200) {
        context.read<NotificationProvider>().loadNotifications();
      }
    });
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<NotificationProvider>();

    return Scaffold(
      appBar: AppBar(
        title: Text('notification.title'.tr()),
        actions: [
          if (provider.unreadCount > 0)
            TextButton(
              onPressed: provider.markAllRead,
              child: Text('notification.mark_all_read'.tr()),
            ),
        ],
      ),
      body: provider.isLoading
          ? const LoadingWidget()
          : provider.notifications.isEmpty
              ? EmptyState(
                  icon: Icons.notifications_off_outlined,
                  title: 'notification.empty'.tr(),
                )
              : RefreshIndicator(
                  onRefresh: () =>
                      provider.loadNotifications(refresh: true),
                  child: ListView.separated(
                    controller: _scroll,
                    itemCount: provider.notifications.length +
                        (provider.isLoadingMore ? 1 : 0),
                    separatorBuilder: (context, index) =>
                        const Divider(height: 1),
                    itemBuilder: (context, index) {
                      if (index >= provider.notifications.length) {
                        return const Padding(
                          padding: EdgeInsets.all(16),
                          child: Center(child: CircularProgressIndicator()),
                        );
                      }
                      final n = provider.notifications[index];
                      return NotificationTile(
                        notification: n,
                        onTap: () => provider.markRead([n.id]),
                      );
                    },
                  ),
                ),
    );
  }
}
