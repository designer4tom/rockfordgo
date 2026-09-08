import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/dio_client.dart';
import '../model/chat_conversation_model.dart';
import '../model/chat_message_model.dart';
import 'chat_repository.dart';

class ChatRepositoryImpl implements ChatRepository {
  final DioClient _client;

  ChatRepositoryImpl(this._client);

  @override
  Future<ChatConversationModel?> getConversationForOrder(int orderId) async {
    try {
      final res = await _client.dio.get(ApiEndpoints.chatByOrder(orderId));
      final data = Map<String, dynamic>.from(res.data['data']);
      return ChatConversationModel.fromJson(
        Map<String, dynamic>.from(data['conversation']),
      );
    } on DioException catch (e) {
      // No chat yet (driver hasn't accepted) — not an error state.
      if (e.response?.statusCode == 404) return null;
      throw ApiException.fromDioError(e);
    } catch (e) {
      // The response came back 200 but didn't match the shape we expect
      // (backend contract drift). Surface it instead of silently falling
      // through to "no chat yet" — that swallows real bugs.
      if (kDebugMode) debugPrint('chat: getConversationForOrder parse failed: $e');
      throw ApiException(message: 'Could not read the chat response.');
    }
  }

  @override
  Future<ChatMessagesPage> getMessages(
    int conversationId, {
    int limit = 30,
    int? beforeId,
  }) async {
    try {
      final res = await _client.dio.get(
        ApiEndpoints.chatMessages(conversationId),
        queryParameters: {
          'limit': limit,
          'before_id': ?beforeId,
        },
      );
      final data = Map<String, dynamic>.from(res.data['data']);
      final conversation = data['conversation'] != null
          ? ChatConversationModel.fromJson(
              Map<String, dynamic>.from(data['conversation']))
          : null;
      final messages = (data['messages'] as List? ?? [])
          .map((m) => ChatMessageModel.fromJson(Map<String, dynamic>.from(m)))
          .toList();
      final pagination = Map<String, dynamic>.from(data['pagination'] ?? {});
      return ChatMessagesPage(
        conversation: conversation,
        messages: messages,
        hasMore: pagination['has_more'] == true,
        nextBeforeId: pagination['next_before_id'],
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    } catch (e) {
      if (kDebugMode) debugPrint('chat: getMessages parse failed: $e');
      throw ApiException(message: 'Could not read the chat messages.');
    }
  }

  @override
  Future<ChatMessageModel> sendMessage(int conversationId, String body) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.chatSend(conversationId),
        data: {'body': body},
      );
      final data = Map<String, dynamic>.from(res.data['data']);
      return ChatMessageModel.fromJson(
        Map<String, dynamic>.from(data['message']),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    } catch (e) {
      if (kDebugMode) debugPrint('chat: sendMessage parse failed: $e');
      throw ApiException(message: 'Could not send the message.');
    }
  }

  @override
  Future<Map<String, dynamic>> markRead(
    int conversationId, {
    List<int>? messageIds,
  }) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.chatRead(conversationId),
        data: {'message_ids': ?messageIds},
      );
      return Map<String, dynamic>.from(res.data['data']);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<ChatConversationListPage> getConversations({
    int perPage = 20,
    bool activeOnly = false,
  }) async {
    try {
      final res = await _client.dio.get(
        ApiEndpoints.chatConversations,
        queryParameters: {
          'per_page': perPage,
          if (activeOnly) 'active_only': 1,
        },
      );
      final list = (res.data['data'] as List? ?? [])
          .map((c) =>
              ChatConversationModel.fromJson(Map<String, dynamic>.from(c)))
          .toList();
      final meta = Map<String, dynamic>.from(res.data['meta'] ?? {});
      return ChatConversationListPage(
        conversations: list,
        currentPage: meta['current_page'] ?? 1,
        lastPage: meta['last_page'] ?? 1,
        total: meta['total'] ?? list.length,
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<int> getUnreadCount() async {
    try {
      final res = await _client.dio.get(ApiEndpoints.chatUnreadCount);
      final data = Map<String, dynamic>.from(res.data['data']);
      return data['unread_count'] ?? 0;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<int> heartbeat() async {
    try {
      final res = await _client.dio.post(ApiEndpoints.chatHeartbeat);
      final data = Map<String, dynamic>.from(res.data['data']);
      return data['heartbeat_interval'] ?? 60;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<void> setOffline() async {
    try {
      await _client.dio.post(ApiEndpoints.chatOffline);
    } on DioException catch (_) {
      // Best-effort — the backend self-heals presence after ~2 minutes of
      // silence, so a failed offline ping is not worth surfacing to the user.
    }
  }
}
