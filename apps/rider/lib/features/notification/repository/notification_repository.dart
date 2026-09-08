import '../../../core/network/api_response.dart';
import '../model/notification_model.dart';

abstract class NotificationRepository {
  Future<({List<NotificationModel> items, PaginationMeta? meta, int unread})>
      getNotifications(int page);

  Future<void> markRead(List<int> ids);
}
