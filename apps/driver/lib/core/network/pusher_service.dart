import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';

import '../constants/app_constants.dart';
import '../services/config_service.dart';
import '../storage/secure_storage.dart';

/// Real-time channel handling for the driver.
///
/// Channels:
///  - `private-driver.{driverId}` → order requests (NewOrderRequest /
///    OrderRequestCancelled)
///  - `private-order.{orderId}`   → active order updates (OrderStatusUpdated /
///    OrderCancelled)
///
/// App FOREGROUND + online → Pusher delivers order requests instantly.
/// App BACKGROUND / killed  → FCM is the backup channel.
class PusherService {
  PusherService(this._storage, this._dio);

  final SecureStorage _storage;
  final Dio _dio;

  PusherChannelsFlutter? _pusher;
  bool _isConnected = false;
  bool _initialized = false;

  bool get isConnected => _isConnected;
  bool get isInitialized => _initialized;

  /// Initialize + connect using cached config. No-op (returns false) when the
  /// backend has not configured a Pusher key yet — FCM remains the backup.
  Future<bool> init() async {
    if (_initialized) return true;

    // Dynamic /config is the source of truth (admin can change it). Fall back
    // to AppConstants only if the backend hasn't set a key yet.
    final config = await ConfigService.getCached();
    var key = config.pusherKey;
    var cluster = config.pusherCluster;
    if (key.isEmpty) {
      key = AppConstants.pusherKey;
      cluster = AppConstants.pusherCluster;
    }
    if (key.isEmpty) return false; // no key anywhere → FCM stays the backup

    try {
      final pusher = PusherChannelsFlutter.getInstance();
      await pusher.init(
        apiKey: key,
        cluster: cluster,
        onConnectionStateChange: (current, previous) {
          _isConnected = current == 'CONNECTED';
          debugPrint('Pusher state: $previous → $current');
        },
        onError: (msg, code, e) =>
            debugPrint('Pusher error: $msg (code=$code)'),
        onAuthorizer: (channelName, socketId, options) async {
          try {
            final token = await _storage.getToken();
            // Backend exposes the auth route at /api/broadcasting/auth —
            // OUTSIDE the /v1 prefix. Strip the trailing /v1 from baseUrl.
            final apiRoot =
                AppConstants.baseUrl.replaceFirst(RegExp(r'/v1/?$'), '');
            final res = await _dio.post(
              '$apiRoot/broadcasting/auth',
              data: {'socket_id': socketId, 'channel_name': channelName},
              options: Options(headers: {'Authorization': 'Bearer $token'}),
            );

            // Pusher's native iOS layer requires a Map (NSDictionary).
            // Returning a raw String crashes with:
            //   Could not cast '__NSCFConstantString' to 'NSDictionary'
            // Decode strings; reject everything else gracefully.
            final data = res.data;
            if (data is Map) return data;
            if (data is String && data.isNotEmpty) {
              try {
                final decoded = jsonDecode(data);
                if (decoded is Map) return decoded;
              } catch (_) {}
            }
            debugPrint(
              'PusherService: broadcasting/auth returned non-Map data '
              '(type=${data.runtimeType}); skipping channel auth.',
            );
            return <String, dynamic>{};
          } catch (e) {
            debugPrint('PusherService: broadcasting/auth failed: $e');
            return <String, dynamic>{};
          }
        },
      );
      await pusher.connect();
      _pusher = pusher;
      _initialized = true;
      return true;
    } catch (e) {
      debugPrint('PusherService: init failed, realtime disabled: $e');
      _initialized = false;
      _isConnected = false;
      return false;
    }
  }

  // ---- Driver channel: order requests ----
  Future<void> subscribeToDriverChannel(
    int driverId, {
    required void Function(Map) onNewOrderRequest,
    required void Function(Map) onOrderRequestCancelled,
  }) async {
    await _pusher?.subscribe(
      channelName: 'private-driver.$driverId',
      onEvent: (event) {
        final data = _decode(event.data);
        if (data is! Map) return;
        switch (event.eventName) {
          case 'NewOrderRequest':
          case 'new-order-request':
            onNewOrderRequest(data);
            break;
          case 'OrderRequestCancelled':
          case 'order-request-cancelled':
            onOrderRequestCancelled(data);
            break;
        }
      },
    );
  }

  // ---- Active order channel: status / cancellation ----
  Future<void> subscribeToOrder(
    int orderId, {
    required void Function(Map) onStatusUpdated,
    required void Function(Map) onCancelled,
  }) async {
    await _pusher?.subscribe(
      channelName: 'private-order.$orderId',
      onEvent: (event) {
        final data = _decode(event.data);
        if (data is! Map) return;
        // Laravel may emit the event name in different shapes (broadcastAs
        // PascalCase, a kebab alias, or a leading-dot namespace), so normalise
        // before matching — mirrors the driver-channel handling.
        final name = event.eventName.replaceAll('.', '').toLowerCase();
        switch (name) {
          case 'orderstatusupdated':
          case 'order-status-updated':
            onStatusUpdated(data);
            break;
          case 'ordercancelled':
          case 'order-cancelled':
          case 'orderrequestcancelled':
            onCancelled(data);
            break;
        }
      },
    );
  }

  // ---- Chat channel: messages / read receipts / conversation closed ----
  // `channelName` must be the exact `conversation.channel` string from the
  // backend (e.g. "private-conversation.7") — NEVER built from order_id, and
  // preferably not even self-built from conversation id. Passing the wrong
  // channel authorises against a conversation this user isn't a participant
  // of and the backend correctly rejects it with a 403
  // AccessDeniedHttpException from /broadcasting/auth. See test/CHAT_API.md §4.
  Future<void> subscribeToConversation(
    String channelName, {
    required void Function(Map) onMessageSent,
    required void Function(Map) onMessagesRead,
    required void Function(Map) onConversationClosed,
  }) async {
    await _pusher?.subscribe(
      channelName: channelName,
      onEvent: (event) {
        final data = _decode(event.data);
        if (data is! Map) return;
        final name = event.eventName.replaceAll('.', '').toLowerCase();
        switch (name) {
          case 'messagesent':
          case 'message-sent':
            onMessageSent(data);
            break;
          case 'messagesread':
          case 'messages-read':
            onMessagesRead(data);
            break;
          case 'conversationclosed':
          case 'conversation-closed':
            onConversationClosed(data);
            break;
        }
      },
    );
  }

  Future<void> unsubscribeFromConversation(String channelName) async {
    await _pusher?.unsubscribe(channelName: channelName);
  }

  Future<void> unsubscribeDriverChannel(int driverId) async {
    await _pusher?.unsubscribe(channelName: 'private-driver.$driverId');
  }

  Future<void> unsubscribeFromOrder(int orderId) async {
    await _pusher?.unsubscribe(channelName: 'private-order.$orderId');
  }

  Map? _decode(dynamic data) {
    if (data == null) return null;
    if (data is Map) return data;
    if (data is String && data.isNotEmpty) {
      try {
        final decoded = jsonDecode(data);
        return decoded is Map ? decoded : null;
      } catch (_) {
        return null;
      }
    }
    return null;
  }

  Future<void> disconnect() async {
    await _pusher?.disconnect();
    _isConnected = false;
    _initialized = false;
  }
}
