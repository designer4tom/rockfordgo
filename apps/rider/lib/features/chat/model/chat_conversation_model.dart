/// The API is not consistent about booleans — `is_online` has come back as
/// `true`, `1` and `"1"` depending on the driver. A strict `== true` check
/// made an online driver render as "Offline", so parse all three shapes.
bool _parseBool(dynamic value) {
  if (value is bool) return value;
  if (value is num) return value != 0;
  final s = value?.toString().toLowerCase();
  return s == 'true' || s == '1';
}

/// The backend sends UTC timestamps (`…Z`); parsed naively they still carry
/// UTC clock values, so "last seen" / "closed" times showed the server's
/// hour instead of the device's. Always convert to local before display.
DateTime? _parseLocal(dynamic value) {
  if (value == null) return null;
  final parsed = DateTime.tryParse(value.toString());
  return parsed?.toLocal();
}

/// The other side of the conversation — always the driver in the customer
/// app (per CHAT_API.md §3.1: "participant is always the other person").
class ChatParticipant {
  final String type;
  final int id;
  final String name;
  final String? avatar;
  final bool isOnline;
  final DateTime? lastSeenAt;

  ChatParticipant({
    required this.type,
    required this.id,
    required this.name,
    this.avatar,
    this.isOnline = false,
    this.lastSeenAt,
  });

  factory ChatParticipant.fromJson(Map<String, dynamic> json) {
    return ChatParticipant(
      type: json['type'] ?? '',
      id: json['id'] is int
          ? json['id']
          : int.tryParse('${json['id']}') ?? 0,
      name: json['name'] ?? '',
      avatar: json['avatar'],
      isOnline: _parseBool(json['is_online']),
      lastSeenAt: _parseLocal(json['last_seen_at']),
    );
  }
}

class ChatLastMessage {
  final String body;
  final String senderType;
  final bool isMine;
  final DateTime? createdAt;

  ChatLastMessage({
    required this.body,
    required this.senderType,
    required this.isMine,
    this.createdAt,
  });

  factory ChatLastMessage.fromJson(Map<String, dynamic> json) {
    return ChatLastMessage(
      body: json['body'] ?? '',
      senderType: json['sender_type'] ?? '',
      isMine: _parseBool(json['is_mine']),
      createdAt: _parseLocal(json['created_at']),
    );
  }
}

class ChatConversationModel {
  final int id;
  final int orderId;
  final String orderNumber;
  final String orderType;
  final String orderStatus;
  final String status;
  final bool isActive;
  final bool canSend;
  final DateTime? closedAt;
  final ChatParticipant participant;
  final int unreadCount;
  final ChatLastMessage? lastMessage;
  final DateTime? lastMessageAt;

  ChatConversationModel({
    required this.id,
    required this.orderId,
    required this.orderNumber,
    required this.orderType,
    required this.orderStatus,
    required this.status,
    required this.isActive,
    required this.canSend,
    this.closedAt,
    required this.participant,
    this.unreadCount = 0,
    this.lastMessage,
    this.lastMessageAt,
  });

  factory ChatConversationModel.fromJson(Map<String, dynamic> json) {
    return ChatConversationModel(
      id: json['id'],
      orderId: json['order_id'],
      orderNumber: json['order_number'] ?? '',
      orderType: json['order_type'] ?? 'ride',
      orderStatus: json['order_status'] ?? '',
      status: json['status'] ?? 'active',
      isActive: _parseBool(json['is_active']),
      canSend: _parseBool(json['can_send']),
      closedAt: _parseLocal(json['closed_at']),
      participant:
          ChatParticipant.fromJson(Map<String, dynamic>.from(json['participant'] ?? {})),
      unreadCount: json['unread_count'] ?? 0,
      lastMessage: json['last_message'] != null
          ? ChatLastMessage.fromJson(Map<String, dynamic>.from(json['last_message']))
          : null,
      lastMessageAt: _parseLocal(json['last_message_at']),
    );
  }

  ChatConversationModel copyWith({
    String? status,
    bool? isActive,
    bool? canSend,
    DateTime? closedAt,
    ChatParticipant? participant,
    int? unreadCount,
  }) {
    return ChatConversationModel(
      id: id,
      orderId: orderId,
      orderNumber: orderNumber,
      orderType: orderType,
      orderStatus: orderStatus,
      status: status ?? this.status,
      isActive: isActive ?? this.isActive,
      canSend: canSend ?? this.canSend,
      closedAt: closedAt ?? this.closedAt,
      participant: participant ?? this.participant,
      unreadCount: unreadCount ?? this.unreadCount,
      lastMessage: lastMessage,
      lastMessageAt: lastMessageAt,
    );
  }
}
