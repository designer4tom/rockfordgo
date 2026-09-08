import 'dart:async';

import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/network/pusher_service.dart';
import '../model/chat_conversation_model.dart';
import '../model/chat_message_model.dart';
import '../repository/chat_repository.dart';

/// Backs one open chat thread. Single app-level instance (same pattern as
/// [TrackingProvider]) — [openForOrder] resets its state for each new order,
/// so a new ride never reuses a stale conversation (CHAT_API.md §5 rule 1).
class ChatProvider extends ChangeNotifier {
  final ChatRepository _repository;
  final PusherService _pusher;

  ChatProvider(this._repository, this._pusher);

  ChatConversationModel? conversation;
  final List<ChatMessageModel> messages = [];
  bool isLoadingConversation = false;
  bool isLoadingMessages = false;
  bool isSending = false;
  bool hasMore = false;
  int? _nextBeforeId;
  String? error;

  Timer? _heartbeatTimer;
  Timer? _presenceTimer;
  int _tempIdSeq = 0;

  /// `sender_type` for this app's side — used to derive `is_mine` on the
  /// realtime `MessageSent` payload, which never includes it.
  static const String _mySide = 'user';

  bool get hasChat => conversation != null;
  bool get canSend => conversation?.canSend ?? false;
  bool get isClosed => conversation?.status == 'closed';

  /// Step 1: does this order have a chat yet? (driver must have accepted)
  Future<bool> openForOrder(int orderId) async {
    _resetForNewOrder();
    isLoadingConversation = true;
    notifyListeners();
    try {
      conversation = await _repository.getConversationForOrder(orderId);
      error = null;
    } on ApiException catch (e) {
      error = e.message;
      conversation = null;
    } catch (e) {
      // Don't let an unexpected shape/parse error vanish silently — the
      // caller needs `error` set so it can tell "no chat yet" (null, no
      // error) apart from "something actually broke".
      error = 'Something went wrong opening the chat.';
      conversation = null;
    } finally {
      isLoadingConversation = false;
      notifyListeners();
    }
    return conversation != null;
  }

  void _resetForNewOrder() {
    _stopHeartbeat();
    conversation = null;
    messages.clear();
    hasMore = false;
    _nextBeforeId = null;
    error = null;
  }

  /// Step 2: user tapped the message icon — load history, go live.
  Future<void> openThread() async {
    final c = conversation;
    if (c == null) return;
    await loadMessages();
    await _pusher.subscribeToConversation(
      c.id,
      onMessageSent: _onMessageSent,
      onMessagesRead: _onMessagesRead,
      onConversationClosed: _onConversationClosed,
    );
    _startHeartbeat();
    _startPresencePolling();
  }

  /// The driver's online flag only ever arrived with the initial conversation
  /// fetch, so a driver who came online after the screen opened stayed stuck
  /// on "Offline". There's no presence event on the channel, so re-read the
  /// conversation on a slow timer and refresh just the participant.
  void _startPresencePolling() {
    _presenceTimer?.cancel();
    _presenceTimer =
        Timer.periodic(const Duration(seconds: 20), (_) => _refreshPresence());
  }

  Future<void> _refreshPresence() async {
    final c = conversation;
    if (c == null) return;
    try {
      final fresh = await _repository.getConversationForOrder(c.orderId);
      final current = conversation;
      // Bail if the thread was swapped/closed while the request was in flight.
      if (fresh == null || current == null || current.id != c.id) return;
      if (fresh.participant.isOnline == current.participant.isOnline &&
          fresh.participant.lastSeenAt == current.participant.lastSeenAt) {
        return;
      }
      conversation = current.copyWith(participant: fresh.participant);
      notifyListeners();
    } catch (_) {
      // Presence is cosmetic — a failed poll must not surface an error.
    }
  }

  Future<void> loadMessages({bool loadMore = false}) async {
    final c = conversation;
    if (c == null) return;
    if (loadMore && !hasMore) return;
    isLoadingMessages = true;
    notifyListeners();
    try {
      final page = await _repository.getMessages(
        c.id,
        beforeId: loadMore ? _nextBeforeId : null,
      );
      if (page.conversation != null) conversation = page.conversation;
      if (loadMore) {
        messages.insertAll(0, page.messages);
      } else {
        messages
          ..clear()
          ..addAll(page.messages);
      }
      hasMore = page.hasMore;
      _nextBeforeId = page.nextBeforeId;
      error = null;
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      isLoadingMessages = false;
      notifyListeners();
    }
  }

  /// Optimistic send: the bubble appears instantly, gets reconciled with the
  /// server copy on success, or flagged failed on error. The sender never
  /// receives their own message back over Pusher (CHAT_API.md §3.3).
  Future<void> sendMessage(String body) async {
    final c = conversation;
    final text = body.trim();
    if (c == null || text.isEmpty || !c.canSend) return;

    final tempId = ++_tempIdSeq;
    final optimistic = ChatMessageModel.optimistic(
      tempId: tempId,
      conversationId: c.id,
      body: text,
    );
    messages.add(optimistic);
    isSending = true;
    notifyListeners();

    try {
      final sent = await _repository.sendMessage(c.id, text);
      final idx = messages.indexWhere((m) => m.id == optimistic.id);
      if (idx != -1) messages[idx] = sent;
      error = null;
    } on ApiException catch (e) {
      final idx = messages.indexWhere((m) => m.id == optimistic.id);
      if (idx != -1) {
        messages[idx] =
            messages[idx].copyWith(status: ChatMessageStatus.failed);
      }
      if (e.errors?['conversation_status'] == 'closed') {
        _onConversationClosed({
          'conversation_id': c.id,
          'status': 'closed',
          'reason': 'completed',
        });
      }
      error = e.message;
    } finally {
      isSending = false;
      notifyListeners();
    }
  }

  Future<void> retrySend(ChatMessageModel failed) async {
    messages.removeWhere((m) => m.id == failed.id);
    await sendMessage(failed.body);
  }

  Future<void> markRead() async {
    final c = conversation;
    if (c == null) return;
    try {
      await _repository.markRead(c.id);
    } on ApiException catch (_) {
      // Non-critical — the badge just won't clear until the next fetch.
    }
  }

  void _onMessageSent(Map<String, dynamic> data) {
    final raw = data['message'] as Map?;
    if (raw == null) return;
    final incoming = ChatMessageModel.fromPusherJson(
      Map<String, dynamic>.from(raw),
      mySide: _mySide,
    );
    // Guard against duplicating the optimistic copy already reconciled by
    // the send response (CHAT_API.md §5 rule 3).
    if (messages.any((m) => m.id == incoming.id)) return;
    messages.add(incoming);
    notifyListeners();
    if (!incoming.isMine) markRead();
  }

  void _onMessagesRead(Map<String, dynamic> data) {
    for (var i = 0; i < messages.length; i++) {
      final m = messages[i];
      if (m.isMine && !m.isRead) {
        messages[i] = m.copyWith(isRead: true, readAt: DateTime.now());
      }
    }
    notifyListeners();
  }

  void _onConversationClosed(Map<String, dynamic> data) {
    final c = conversation;
    if (c == null) return;
    conversation = c.copyWith(status: 'closed', canSend: false);
    _stopHeartbeat();
    notifyListeners();
  }

  void _startHeartbeat() {
    _heartbeatTimer?.cancel();
    _heartbeatTimer = Timer.periodic(const Duration(seconds: 60), (_) {
      _repository.heartbeat().catchError((_) => 60);
    });
    // Fire one immediately so presence flips to online right away.
    _repository.heartbeat().catchError((_) => 60);
  }

  void _stopHeartbeat() {
    _heartbeatTimer?.cancel();
    _heartbeatTimer = null;
    _presenceTimer?.cancel();
    _presenceTimer = null;
  }

  /// Call when leaving the chat screen.
  Future<void> closeThread() async {
    final c = conversation;
    _stopHeartbeat();
    if (c != null) {
      await _pusher.unsubscribeFromConversation(c.id);
      await _repository.setOffline();
    }
  }

  @override
  void dispose() {
    _stopHeartbeat();
    super.dispose();
  }
}
