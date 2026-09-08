import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/dio_client.dart';
import '../model/chat_models.dart';

/// Driver-side chat endpoints — see `test/CHAT_API.md`.
class ChatRepository {
  final DioClient _client;
  ChatRepository({DioClient? client}) : _client = client ?? DioClient.instance;

  /// 404 (no chat for this order yet) surfaces as a normal ApiException with
  /// statusCode 404 — callers should treat that as "hide the icon".
  Future<ChatConversation> getConversationForOrder(int orderId) async {
    try {
      final res = await _client.get(ApiEndpoints.chatByOrder(orderId));
      final data = res.data['data']['conversation'];
      return ChatConversation.fromJson((data as Map).cast<String, dynamic>());
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<
      ({
        ChatConversation conversation,
        List<ChatMessage> messages,
        bool hasMore,
        int? nextBeforeId,
      })> getMessages(int conversationId, {int? beforeId, int limit = 30}) async {
    try {
      final res = await _client.get(
        ApiEndpoints.chatMessages(conversationId),
        query: {
          'limit': limit,
          if (beforeId != null) 'before_id': beforeId,
        },
      );
      final data = res.data['data'];
      final conversation = ChatConversation.fromJson(
          (data['conversation'] as Map).cast<String, dynamic>());
      final messages = (data['messages'] as List? ?? const [])
          .map((e) => ChatMessage.fromJson((e as Map).cast<String, dynamic>()))
          .toList();
      final pagination = data['pagination'] as Map? ?? const {};
      return (
        conversation: conversation,
        messages: messages,
        hasMore: pagination['has_more'] == true,
        nextBeforeId: pagination['next_before_id'] is int
            ? pagination['next_before_id'] as int
            : int.tryParse(pagination['next_before_id']?.toString() ?? ''),
      );
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<ChatMessage> sendMessage(int conversationId, String body) async {
    try {
      final res = await _client.post(
        ApiEndpoints.chatSend(conversationId),
        data: {'body': body},
      );
      final data = res.data['data']['message'];
      return ChatMessage.fromJson((data as Map).cast<String, dynamic>());
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<void> markRead(int conversationId, {List<int>? messageIds}) async {
    try {
      await _client.post(
        ApiEndpoints.chatRead(conversationId),
        data: messageIds != null ? {'message_ids': messageIds} : {},
      );
    } catch (_) {
      // best-effort
    }
  }

  Future<int> getUnreadCount() async {
    try {
      final res = await _client.get(ApiEndpoints.chatUnreadCount);
      final count = res.data['data']['unread_count'];
      return count is int ? count : int.tryParse(count?.toString() ?? '') ?? 0;
    } catch (_) {
      return 0;
    }
  }

  Future<void> heartbeat() async {
    try {
      await _client.post(ApiEndpoints.chatHeartbeat);
    } catch (_) {
      // best-effort
    }
  }

  Future<void> goOffline() async {
    try {
      await _client.post(ApiEndpoints.chatOffline);
    } catch (_) {
      // best-effort
    }
  }
}
