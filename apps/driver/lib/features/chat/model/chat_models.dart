/// Ride & parcel chat models — mirrors `test/CHAT_API.md`.
class ChatParticipant {
  final String type; // "user" (customer) or "driver"
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

  factory ChatParticipant.fromJson(Map json) => ChatParticipant(
        type: (json['type'] ?? '').toString(),
        id: json['id'] is int
            ? json['id']
            : int.tryParse(json['id']?.toString() ?? '') ?? 0,
        name: (json['name'] ?? '').toString(),
        avatar: json['avatar']?.toString(),
        isOnline: json['is_online'] == true,
        lastSeenAt: DateTime.tryParse(json['last_seen_at']?.toString() ?? ''),
      );
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

  factory ChatLastMessage.fromJson(Map json) => ChatLastMessage(
        body: (json['body'] ?? '').toString(),
        senderType: (json['sender_type'] ?? '').toString(),
        isMine: json['is_mine'] == true,
        createdAt: DateTime.tryParse(json['created_at']?.toString() ?? ''),
      );
}

class ChatConversation {
  final int id;
  final int orderId;
  // Pusher channel name for this conversation, shipped by the backend since
  // 2026-08-12. MUST be used verbatim when subscribing — building it
  // ourselves from `id` (or worse, `order_id`) is the #1 cause of the
  // `403 AccessDeniedHttpException` from /broadcasting/auth (see §4 of
  // test/CHAT_API.md). Falls back to `private-conversation.{id}` only if an
  // older backend hasn't deployed this field yet.
  final String channel;
  final String orderNumber;
  final String orderType; // ride | parcel
  final String orderStatus;
  final String status; // active | closed
  final bool isActive;
  final bool canSend;
  final DateTime? closedAt;
  final ChatParticipant participant;
  final int unreadCount;
  final ChatLastMessage? lastMessage;
  final DateTime? lastMessageAt;

  ChatConversation({
    required this.id,
    required this.orderId,
    required this.channel,
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

  bool get isClosed => status == 'closed';

  factory ChatConversation.fromJson(Map json) => ChatConversation(
        id: json['id'] is int
            ? json['id']
            : int.tryParse(json['id']?.toString() ?? '') ?? 0,
        orderId: json['order_id'] is int
            ? json['order_id']
            : int.tryParse(json['order_id']?.toString() ?? '') ?? 0,
        channel: (json['channel'] ??
                'private-conversation.${json['id'] ?? ''}')
            .toString(),
        orderNumber: (json['order_number'] ?? '').toString(),
        orderType: (json['order_type'] ?? '').toString(),
        orderStatus: (json['order_status'] ?? '').toString(),
        status: (json['status'] ?? 'active').toString(),
        isActive: json['is_active'] == true,
        canSend: json['can_send'] == true,
        closedAt: DateTime.tryParse(json['closed_at']?.toString() ?? ''),
        participant:
            ChatParticipant.fromJson((json['participant'] as Map?) ?? {}),
        unreadCount: json['unread_count'] is int
            ? json['unread_count']
            : int.tryParse(json['unread_count']?.toString() ?? '') ?? 0,
        lastMessage: json['last_message'] is Map
            ? ChatLastMessage.fromJson(json['last_message'])
            : null,
        lastMessageAt:
            DateTime.tryParse(json['last_message_at']?.toString() ?? ''),
      );

  ChatConversation copyWith({
    String? status,
    bool? isActive,
    bool? canSend,
    DateTime? closedAt,
    ChatParticipant? participant,
    int? unreadCount,
  }) =>
      ChatConversation(
        id: id,
        orderId: orderId,
        channel: channel,
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

class ChatMessage {
  final int id;
  final int conversationId;
  final String body;
  final String senderType; // "user" | "driver"
  final int senderId;
  final bool isMine;
  final bool isRead;
  final DateTime? readAt;
  final DateTime? createdAt;
  final bool failed; // optimistic-send failure marker (local only)
  final bool sending; // optimistic-send in-flight marker (local only)

  ChatMessage({
    required this.id,
    required this.conversationId,
    required this.body,
    required this.senderType,
    required this.senderId,
    required this.isMine,
    this.isRead = false,
    this.readAt,
    this.createdAt,
    this.failed = false,
    this.sending = false,
  });

  factory ChatMessage.fromJson(Map json, {bool? isMineOverride}) =>
      ChatMessage(
        id: json['id'] is int
            ? json['id']
            : int.tryParse(json['id']?.toString() ?? '') ?? 0,
        conversationId: json['conversation_id'] is int
            ? json['conversation_id']
            : int.tryParse(json['conversation_id']?.toString() ?? '') ?? 0,
        body: (json['body'] ?? '').toString(),
        senderType: (json['sender_type'] ?? '').toString(),
        senderId: json['sender_id'] is int
            ? json['sender_id']
            : int.tryParse(json['sender_id']?.toString() ?? '') ?? 0,
        isMine: isMineOverride ?? json['is_mine'] == true,
        isRead: json['is_read'] == true,
        readAt: DateTime.tryParse(json['read_at']?.toString() ?? ''),
        createdAt: DateTime.tryParse(json['created_at']?.toString() ?? ''),
      );

  ChatMessage copyWith({
    int? id,
    bool? isRead,
    DateTime? readAt,
    bool? failed,
    bool? sending,
  }) =>
      ChatMessage(
        id: id ?? this.id,
        conversationId: conversationId,
        body: body,
        senderType: senderType,
        senderId: senderId,
        isMine: isMine,
        isRead: isRead ?? this.isRead,
        readAt: readAt ?? this.readAt,
        createdAt: createdAt,
        failed: failed ?? false,
        sending: sending ?? false,
      );
}
