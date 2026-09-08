import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_response.dart';
import '../../../core/network/dio_client.dart';
import '../model/notification_model.dart';
import 'notification_repository.dart';

class NotificationRepositoryImpl implements NotificationRepository {
  final DioClient _client;

  NotificationRepositoryImpl(this._client);

  @override
  Future<({List<NotificationModel> items, PaginationMeta? meta, int unread})>
      getNotifications(int page) async {
    try {
      final res = await _client.dio.get(
        ApiEndpoints.notifications,
        queryParameters: {'page': page},
      );
      // Backend wraps the list as data: { unread_count, notifications: [...] }.
      // Fall back to a flat list shape for safety.
      final data = res.data['data'];
      final rawList = (data is Map ? data['notifications'] : data) as List? ?? [];
      final list = rawList
          .map((e) => NotificationModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
      final meta = res.data['meta'] != null
          ? PaginationMeta.fromJson(Map<String, dynamic>.from(res.data['meta']))
          : null;
      final unread =
          (data is Map ? data['unread_count'] : res.data['unread_count']) ?? 0;
      return (items: list, meta: meta, unread: (unread as num).toInt());
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<void> markRead(List<int> ids) async {
    try {
      await _client.dio.post(
        ApiEndpoints.notificationsMarkRead,
        data: {'notification_ids': ids},
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
