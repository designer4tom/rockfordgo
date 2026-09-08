import '../model/chat_conversation_model.dart';
import '../model/chat_message_model.dart';

abstract class ChatRepository {
  /// The chat for this order, or `null` if the driver hasn't accepted yet
  /// (backend returns 404 — not an error state, just "no chat yet").
  Future<ChatConversationModel?> getConversationForOrder(int orderId);

  /// Opening this also marks the other side's messages as read.
  Future<ChatMessagesPage> getMessages(
    int conversationId, {
    int limit = 30,
    int? beforeId,
  });

  Future<ChatMessageModel> sendMessage(int conversationId, String body);

  Future<Map<String, dynamic>> markRead(
    int conversationId, {
    List<int>? messageIds,
  });

  Future<ChatConversationListPage> getConversations({
    int perPage = 20,
    bool activeOnly = false,
  });

  Future<int> getUnreadCount();

  /// Returns the server's `heartbeat_interval` (seconds).
  Future<int> heartbeat();

  Future<void> setOffline();
}
