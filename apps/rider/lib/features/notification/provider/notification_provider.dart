import 'package:flutter/material.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/network/api_response.dart';
import '../model/notification_model.dart';
import '../repository/notification_repository.dart';

class NotificationProvider extends ChangeNotifier {
  final NotificationRepository _repository;

  NotificationProvider(this._repository);

  List<NotificationModel> notifications = [];
  int unreadCount = 0;
  PaginationMeta? meta;
  bool isLoading = false;
  bool isLoadingMore = false;

  Future<void> loadNotifications({bool refresh = false}) async {
    if (refresh) {
      notifications = [];
      meta = null;
    }
    final nextPage = (meta?.currentPage ?? 0) + 1;
    if (meta != null && !meta!.hasMore && !refresh) return;

    if (nextPage == 1) {
      isLoading = true;
    } else {
      isLoadingMore = true;
    }
    notifyListeners();

    try {
      final result = await _repository.getNotifications(nextPage);
      notifications.addAll(result.items);
      meta = result.meta;
      unreadCount = result.unread;
    } on ApiException {
      // Keep existing list on error.
    } finally {
      isLoading = false;
      isLoadingMore = false;
      notifyListeners();
    }
  }

  Future<void> markRead(List<int> ids) async {
    try {
      await _repository.markRead(ids);
      notifications = notifications
          .map((n) => ids.contains(n.id)
              ? NotificationModel(
                  id: n.id,
                  title: n.title,
                  body: n.body,
                  createdAt: n.createdAt,
                  isRead: true,
                  data: n.data,
                )
              : n)
          .toList();
      unreadCount = notifications.where((n) => !n.isRead).length;
      notifyListeners();
    } on ApiException {
      // Ignore.
    }
  }

  Future<void> markAllRead() async {
    final unreadIds =
        notifications.where((n) => !n.isRead).map((n) => n.id).toList();
    if (unreadIds.isNotEmpty) await markRead(unreadIds);
  }

  /// Called by the FCM service when a foreground message arrives. Keeps the
  /// unread badge fresh without a full reload.
  void onForegroundMessage() {
    unreadCount++;
    notifyListeners();
  }
}
