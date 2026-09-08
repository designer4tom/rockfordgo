import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/dio_client.dart';
import '../model/notification_model.dart';

class NotificationRepository {
  final DioClient _client;
  NotificationRepository({DioClient? client})
      : _client = client ?? DioClient.instance;

  /// Returns the notifications page plus the server-reported unread count.
  /// API shape: `data: { unread_count, notifications: [...] }`.
  Future<({List<NotificationModel> items, int unread})> getNotifications(
      {int page = 1}) async {
    try {
      final res =
          await _client.get(ApiEndpoints.notifications, query: {'page': page});
      final data = res.data['data'];

      List rawList;
      int unread = 0;
      if (data is Map) {
        final n = data['notifications'] ?? data['data'];
        rawList = n is List ? n : const [];
        unread = data['unread_count'] is int
            ? data['unread_count']
            : int.tryParse(data['unread_count']?.toString() ?? '') ?? 0;
      } else if (data is List) {
        rawList = data;
      } else {
        rawList = const [];
      }

      final items = rawList
          .map((e) =>
              NotificationModel.fromJson((e as Map).cast<String, dynamic>()))
          .toList();
      return (items: items, unread: unread);
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  /// Mark specific notifications read. Backend expects `notification_ids`.
  Future<void> markRead(List<int> ids) async {
    if (ids.isEmpty) return;
    try {
      await _client.post(
        ApiEndpoints.notificationsMarkRead,
        data: {'notification_ids': ids},
      );
    } catch (_) {
      // best-effort
    }
  }

  /// Mark all notifications read via the backend `mark_all` flag.
  Future<void> markAllRead() async {
    try {
      await _client.post(
        ApiEndpoints.notificationsMarkRead,
        data: {'mark_all': true},
      );
    } catch (_) {
      // best-effort
    }
  }
}
