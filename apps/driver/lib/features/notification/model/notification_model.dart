import '../../../core/utils/helpers.dart';

class NotificationModel {
  final int id;
  final String title;
  final String body;
  final String type;
  final bool read;
  final DateTime? createdAt;

  NotificationModel({
    required this.id,
    required this.title,
    required this.body,
    required this.type,
    required this.read,
    this.createdAt,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) =>
      NotificationModel(
        id: json['id'] is int
            ? json['id']
            : int.tryParse(json['id']?.toString() ?? '') ?? 0,
        title: (json['title'] ?? '').toString(),
        body: (json['body'] ?? json['message'] ?? '').toString(),
        type: (json['type'] ?? 'general').toString(),
        read: json['read'] == true ||
            json['is_read'] == true ||
            json['read_at'] != null,
        createdAt: Helpers.tryParse(json['created_at'] ?? json['date']),
      );
}
