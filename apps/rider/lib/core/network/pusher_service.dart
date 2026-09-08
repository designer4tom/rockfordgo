import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';

import '../constants/app_constants.dart';
import '../services/config_service.dart';
import '../storage/secure_storage.dart';

/// Real-time transport for order tracking. Private channels are authorized
/// with the Sanctum token via the backend `/broadcasting/auth` endpoint.
class PusherService {
  PusherChannelsFlutter? _pusher;
  final SecureStorage _storage;
  final Dio _dio;
  bool _isConnected = false;
  bool _initialized = false;

  PusherService(this._storage, this._dio);

  bool get isConnected => _isConnected;

  Future<void> init() async {
    if (_initialized) return;
    try {
      final config = ConfigService.getCached();
      if (config.pusherKey.isEmpty) {
        if (kDebugMode) debugPrint('Pusher key empty — skipping init.');
        return;
      }

      _pusher = PusherChannelsFlutter.getInstance();
      await _pusher!.init(
        apiKey: config.pusherKey,
        cluster: config.pusherCluster,
        onConnectionStateChange: (current, previous) {
          _isConnected = current == 'CONNECTED';
        },
        onError: (message, code, error) {
          if (kDebugMode) debugPrint('Pusher error: $message ($code)');
        },
        // Private channel authorization with the Sanctum token.
        onAuthorizer: (channelName, socketId, options) async {
          final token = await _storage.getToken();
          final res = await _dio.post(
            '${AppConstants.baseUrl}/broadcasting/auth',
            data: {'socket_id': socketId, 'channel_name': channelName},
            options: Options(
              headers: {'Authorization': 'Bearer $token'},
            ),
          );
          return res.data; // { auth: "..." }
        },
      );
      await _pusher!.connect();
      _initialized = true;
    } catch (e) {
      if (kDebugMode) debugPrint('Pusher init failed: $e');
    }
  }

  /// Re-connect (e.g. when the app returns to the foreground).
  Future<void> reconnect() async {
    try {
      if (!_initialized) {
        await init();
      } else {
        await _pusher?.connect();
      }
    } catch (_) {}
  }

  Future<void> subscribeToOrder(
    int orderId, {
    required void Function(Map<String, dynamic>) onDriverAccepted,
    required void Function(Map<String, dynamic>) onLocationUpdated,
    required void Function(Map<String, dynamic>) onStatusUpdated,
    required void Function(Map<String, dynamic>) onCompleted,
    required void Function(Map<String, dynamic>) onCancelled,
  }) async {
    await init();
    if (_pusher == null) return;
    try {
      await _pusher!.subscribe(
        channelName: 'private-order.$orderId',
        onEvent: (event) {
          final raw = event.data;
          if (raw == null || raw.isEmpty) return;
          final data = Map<String, dynamic>.from(jsonDecode(raw));
          switch (event.eventName) {
            case 'DriverAccepted':
              onDriverAccepted(data);
            case 'DriverLocationUpdated':
              onLocationUpdated(data);
            case 'OrderStatusUpdated':
              onStatusUpdated(data);
            case 'OrderCompleted':
              onCompleted(data);
            case 'OrderCancelled':
              onCancelled(data);
          }
        },
      );
    } catch (e) {
      if (kDebugMode) debugPrint('Pusher subscribe failed: $e');
    }
  }

  Future<void> unsubscribeFromOrder(int orderId) async {
    try {
      await _pusher?.unsubscribe(channelName: 'private-order.$orderId');
    } catch (_) {}
  }

  /// Chat channel for one conversation. Events: `MessageSent`,
  /// `MessagesRead`, `ConversationClosed`.
  Future<void> subscribeToConversation(
    int conversationId, {
    required void Function(Map<String, dynamic>) onMessageSent,
    required void Function(Map<String, dynamic>) onMessagesRead,
    required void Function(Map<String, dynamic>) onConversationClosed,
  }) async {
    await init();
    if (_pusher == null) return;
    try {
      await _pusher!.subscribe(
        channelName: 'private-conversation.$conversationId',
        onEvent: (event) {
          final raw = event.data;
          if (raw == null || raw.isEmpty) return;
          final data = Map<String, dynamic>.from(jsonDecode(raw));
          switch (event.eventName) {
            case 'MessageSent':
              onMessageSent(data);
            case 'MessagesRead':
              onMessagesRead(data);
            case 'ConversationClosed':
              onConversationClosed(data);
          }
        },
      );
    } catch (e) {
      if (kDebugMode) debugPrint('Pusher subscribe (chat) failed: $e');
    }
  }

  Future<void> unsubscribeFromConversation(int conversationId) async {
    try {
      await _pusher?.unsubscribe(
        channelName: 'private-conversation.$conversationId',
      );
    } catch (_) {}
  }

  Future<void> disconnect() async {
    try {
      await _pusher?.disconnect();
    } catch (_) {}
    _isConnected = false;
    _initialized = false;
  }
}
