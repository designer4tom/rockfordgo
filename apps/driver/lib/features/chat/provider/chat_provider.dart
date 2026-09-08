import 'dart:async';

import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/network/pusher_service.dart';
import '../model/chat_models.dart';
import '../repository/chat_repository.dart';

/// Drives both the message-icon badge (order screens) and the thread screen.
/// One instance is shared app-wide (see app.dart) and re-targeted per order
/// via [loadForOrder] — mirrors the "resolve from order_id, never cache
/// against a driver" rule in the API guide.
class ChatProvider extends ChangeNotifier {
  final ChatRepository _repository;
  final PusherService _pusher;
  ChatProvider(this._repository, this._pusher);

  ChatConversation? conversation;
  List<ChatMessage> messages = [];
  bool hasMore = false;
  int? _nextBeforeId;

  bool loadingIcon = false; // §3.1 lookup, drives the message-icon visibility
  bool loadingMessages = false;
  bool sending = false;
  bool loadingMore = false;
  String? error;

  Timer? _heartbeatTimer;
  String? _subscribedChannel;

  bool get hasChat => conversation != null;
  bool get canSend => conversation?.canSend ?? false;
  int get unreadBadge => conversation?.unreadCount ?? 0;

  /// §3.1 — call when an order screen opens. 404 just means "no chat yet"
  /// (driver hasn't accepted / backend hasn't opened it) — not an error.
  Future<void> loadForOrder(int orderId) async {
    loadingIcon = true;
    error = null;
    notifyListeners();
    try {
      conversation = await _repository.getConversationForOrder(orderId);
    } on ApiException catch (e) {
      conversation = e.statusCode == 404 ? null : conversation;
      if (e.statusCode != 404) error = e.message;
    } catch (_) {
      // ignore — icon simply stays hidden
    }
    loadingIcon = false;
    notifyListeners();
  }

  /// §3.2 — open the thread. Also marks the other side's messages read.
  Future<void> openThread() async {
    final c = conversation;
    if (c == null) return;
    loadingMessages = true;
    error = null;
    notifyListeners();
    try {
      final page = await _repository.getMessages(c.id);
      conversation = page.conversation.copyWith(unreadCount: 0);
      messages = page.messages;
      hasMore = page.hasMore;
      _nextBeforeId = page.nextBeforeId;
      await _subscribe(conversation!.channel);
      _startHeartbeat();
    } on ApiException catch (e) {
      error = e.message;
    }
    loadingMessages = false;
    notifyListeners();
  }

  Future<void> loadOlder() async {
    final c = conversation;
    if (c == null || !hasMore || loadingMore || _nextBeforeId == null) return;
    loadingMore = true;
    notifyListeners();
    try {
      final page =
          await _repository.getMessages(c.id, beforeId: _nextBeforeId);
      messages = [...page.messages, ...messages];
      hasMore = page.hasMore;
      _nextBeforeId = page.nextBeforeId;
    } catch (_) {
      // best-effort
    }
    loadingMore = false;
    notifyListeners();
  }

  /// §3.3 — optimistic send: render immediately with a temp negative id,
  /// then reconcile with the server copy (or mark failed).
  Future<void> send(String body) async {
    final c = conversation;
    final text = body.trim();
    if (c == null || text.isEmpty || sending) return;

    final tempId = -DateTime.now().millisecondsSinceEpoch;
    final optimistic = ChatMessage(
      id: tempId,
      conversationId: c.id,
      body: text,
      senderType: 'driver',
      senderId: c.participant.id,
      isMine: true,
      isRead: false,
      createdAt: DateTime.now(),
      sending: true,
    );
    messages = [...messages, optimistic];
    sending = true;
    notifyListeners();

    try {
      final sent = await _repository.sendMessage(c.id, text);
      messages = [
        for (final m in messages)
          if (m.id == tempId) sent else m,
      ];
    } on ApiException catch (e) {
      if (e.statusCode == 422 &&
          e.errors is Map &&
          (e.errors as Map)['conversation_status'] == 'closed') {
        conversation = c.copyWith(status: 'closed', canSend: false);
      }
      messages = [
        for (final m in messages)
          if (m.id == tempId) m.copyWith(failed: true, sending: false) else m,
      ];
      error = e.message;
    }
    sending = false;
    notifyListeners();
  }

  Future<void> retry(ChatMessage failedMessage) async {
    messages = messages.where((m) => m.id != failedMessage.id).toList();
    notifyListeners();
    await send(failedMessage.body);
  }

  Future<void> _subscribe(String channelName) async {
    if (_subscribedChannel == channelName) return;
    if (_subscribedChannel != null) {
      await _pusher.unsubscribeFromConversation(_subscribedChannel!);
    }
    _subscribedChannel = channelName;
    await _pusher.subscribeToConversation(
      channelName,
      onMessageSent: _onMessageSent,
      onMessagesRead: _onMessagesRead,
      onConversationClosed: _onConversationClosed,
    );
  }

  void _onMessageSent(Map data) {
    final raw = data['message'];
    if (raw is! Map) return;
    // §5 rule 3: ignore if this id is already present (protects the
    // optimistic copy on our own sends — we never receive our own over
    // Pusher, but stay defensive).
    final incomingId = raw['id'];
    final id = incomingId is int
        ? incomingId
        : int.tryParse(incomingId?.toString() ?? '') ?? 0;
    if (messages.any((m) => m.id == id)) return;

    final mySide = 'driver';
    final isMine = (raw['sender_type']?.toString() ?? '') == mySide;
    final message = ChatMessage.fromJson(raw, isMineOverride: isMine);
    messages = [...messages, message];
    if (!isMine) {
      conversation = conversation?.copyWith(
        unreadCount: 0, // thread is open → mark-read happens implicitly
      );
      _repository.markRead(message.conversationId, messageIds: [message.id]);
    }
    notifyListeners();
  }

  void _onMessagesRead(Map data) {
    messages = [
      for (final m in messages)
        if (m.isMine) m.copyWith(isRead: true, readAt: DateTime.now()) else m,
    ];
    notifyListeners();
  }

  void _onConversationClosed(Map data) {
    conversation = conversation?.copyWith(status: 'closed', canSend: false);
    notifyListeners();
  }

  void _startHeartbeat() {
    _heartbeatTimer?.cancel();
    _repository.heartbeat();
    _heartbeatTimer =
        Timer.periodic(const Duration(seconds: 60), (_) => _repository.heartbeat());
  }

  /// Call when the thread screen closes.
  Future<void> closeThread() async {
    _heartbeatTimer?.cancel();
    _heartbeatTimer = null;
    if (_subscribedChannel != null) {
      await _pusher.unsubscribeFromConversation(_subscribedChannel!);
      _subscribedChannel = null;
    }
    await _repository.goOffline();
  }

  /// Call when leaving the order screen entirely (ride/parcel finished).
  void clear() {
    conversation = null;
    messages = [];
    hasMore = false;
    _nextBeforeId = null;
    error = null;
    notifyListeners();
  }

  @override
  void dispose() {
    _heartbeatTimer?.cancel();
    super.dispose();
  }
}
