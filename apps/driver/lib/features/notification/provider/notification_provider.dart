import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../model/notification_model.dart';
import '../repository/notification_repository.dart';

class NotificationProvider extends ChangeNotifier {
  final NotificationRepository _repository;
  NotificationProvider(this._repository);

  List<NotificationModel> notifications = [];
  int unreadCount = 0;
  bool loading = false;
  String? error;

  /// Clears cached notifications (e.g. on logout / account deletion).
  void reset() {
    notifications = [];
    unreadCount = 0;
    loading = false;
    error = null;
    notifyListeners();
  }

  Future<void> loadNotifications({bool refresh = false}) async {
    loading = true;
    notifyListeners();
    try {
      final page = await _repository.getNotifications();
      notifications = page.items;
      // Prefer the server's unread count; fall back to counting this page.
      unreadCount = page.unread > 0
          ? page.unread
          : notifications.where((n) => !n.read).length;
      error = null;
    } on ApiException catch (e) {
      error = e.message;
    }
    loading = false;
    notifyListeners();
  }

  Future<void> markRead(List<int> ids) async {
    await _repository.markRead(ids);
    notifications = [
      for (final n in notifications)
        if (ids.contains(n.id))
          NotificationModel(
            id: n.id,
            title: n.title,
            body: n.body,
            type: n.type,
            read: true,
            createdAt: n.createdAt,
          )
        else
          n
    ];
    unreadCount = notifications.where((n) => !n.read).length;
    notifyListeners();
  }

  Future<void> markAllRead() async {
    await _repository.markAllRead();
    notifications = [
      for (final n in notifications)
        NotificationModel(
          id: n.id,
          title: n.title,
          body: n.body,
          type: n.type,
          read: true,
          createdAt: n.createdAt,
        )
    ];
    unreadCount = 0;
    notifyListeners();
  }

  /// New FCM message arrived while app open → refresh list/badge.
  void handleFcm(RemoteMessage message) {
    loadNotifications();
  }
}
