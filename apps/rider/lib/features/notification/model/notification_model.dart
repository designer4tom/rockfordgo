class NotificationModel {
  final int id;
  final String title;
  final String body;
  final String createdAt;
  final bool isRead;
  final Map<String, dynamic> data;

  NotificationModel({
    required this.id,
    this.title = '',
    this.body = '',
    this.createdAt = '',
    this.isRead = false,
    this.data = const {},
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    return NotificationModel(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      body: json['body'] ?? json['message'] ?? '',
      createdAt: json['created_at'] ?? '',
      isRead: json['is_read'] ?? json['read'] ?? false,
      data: json['data'] != null
          ? Map<String, dynamic>.from(json['data'])
          : {},
    );
  }
}
