import 'chat_conversation_model.dart';

/// Send/delivery state for the local optimistic bubble — not part of the
/// API payload.
enum ChatMessageStatus { sent, sending, failed }

/// The backend sends UTC timestamps (`…Z`); parsed naively they still carry
/// UTC clock values, so bubbles showed the server's hour instead of the
/// device's. Always convert to local before display.
DateTime? _parseLocal(dynamic value) {
  if (value == null) return null;
  final parsed = DateTime.tryParse(value.toString());
  return parsed?.toLocal();
}

class ChatMessageModel {
  final int id;
  final int conversationId;
  final String body;
  final String senderType;
  final int senderId;
  final bool isMine;
  final bool isRead;
  final DateTime? readAt;
  final DateTime createdAt;
  final ChatMessageStatus status;

  ChatMessageModel({
    required this.id,
    required this.conversationId,
    required this.body,
    required this.senderType,
    required this.senderId,
    required this.isMine,
    this.isRead = false,
    this.readAt,
    required this.createdAt,
    this.status = ChatMessageStatus.sent,
  });

  factory ChatMessageModel.fromJson(Map<String, dynamic> json) {
    return ChatMessageModel(
      id: json['id'],
      conversationId: json['conversation_id'],
      body: json['body'] ?? '',
      senderType: json['sender_type'] ?? '',
      senderId: json['sender_id'] is int
          ? json['sender_id']
          : int.tryParse('${json['sender_id']}') ?? 0,
      isMine: json['is_mine'] == true,
      isRead: json['is_read'] == true,
      readAt: _parseLocal(json['read_at']),
      createdAt: _parseLocal(json['created_at']) ?? DateTime.now(),
    );
  }

  /// `MessageSent` Pusher payload never carries `is_mine` (it's the same
  /// event for both sides) — derive it from `sender_type == mySide`.
  factory ChatMessageModel.fromPusherJson(
    Map<String, dynamic> json, {
    required String mySide,
  }) {
    final senderType = json['sender_type'] ?? '';
    return ChatMessageModel(
      id: json['id'],
      conversationId: json['conversation_id'],
      body: json['body'] ?? '',
      senderType: senderType,
      senderId: json['sender_id'] is int
          ? json['sender_id']
          : int.tryParse('${json['sender_id']}') ?? 0,
      isMine: senderType == mySide,
      isRead: json['is_read'] == true,
      createdAt: _parseLocal(json['created_at']) ?? DateTime.now(),
    );
  }

  /// A locally-created bubble shown immediately on send, before the server
  /// responds. Uses a negative id so it never collides with a real one.
  factory ChatMessageModel.optimistic({
    required int tempId,
    required int conversationId,
    required String body,
  }) {
    return ChatMessageModel(
      id: -tempId,
      conversationId: conversationId,
      body: body,
      senderType: 'user',
      senderId: 0,
      isMine: true,
      createdAt: DateTime.now(),
      status: ChatMessageStatus.sending,
    );
  }

  ChatMessageModel copyWith({
    bool? isRead,
    DateTime? readAt,
    ChatMessageStatus? status,
  }) {
    return ChatMessageModel(
      id: id,
      conversationId: conversationId,
      body: body,
      senderType: senderType,
      senderId: senderId,
      isMine: isMine,
      isRead: isRead ?? this.isRead,
      readAt: readAt ?? this.readAt,
      createdAt: createdAt,
      status: status ?? this.status,
    );
  }
}

class ChatMessagesPage {
  final ChatConversationModel? conversation;
  final List<ChatMessageModel> messages;
  final bool hasMore;
  final int? nextBeforeId;

  ChatMessagesPage({
    this.conversation,
    required this.messages,
    required this.hasMore,
    this.nextBeforeId,
  });
}

class ChatConversationListPage {
  final List<ChatConversationModel> conversations;
  final int currentPage;
  final int lastPage;
  final int total;

  ChatConversationListPage({
    required this.conversations,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });
}
