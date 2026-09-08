import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/chat_message_model.dart';
import '../../provider/chat_provider.dart';
import '../widgets/chat_bubble.dart';
import '../widgets/chat_composer.dart';

/// Opens the chat for one order. The caller is expected to have already
/// resolved the conversation via [ChatProvider.openForOrder] (e.g. from the
/// tracking screen, which only shows the message icon once a driver is
/// assigned) — this screen loads history, subscribes to Pusher and starts
/// the presence heartbeat.
class ChatScreen extends StatefulWidget {
  const ChatScreen({super.key});

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final _scrollController = ScrollController();
  late final ChatProvider _chat;

  @override
  void initState() {
    super.initState();
    _chat = context.read<ChatProvider>();
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      await _chat.openThread();
      _scrollToBottom();
    });
    _scrollController.addListener(_onScroll);
  }

  void _onScroll() {
    if (_scrollController.position.pixels <= 40 &&
        !_chat.isLoadingMessages &&
        _chat.hasMore) {
      _chat.loadMessages(loadMore: true);
    }
  }

  void _scrollToBottom() {
    if (!_scrollController.hasClients) return;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scrollController.hasClients) return;
      _scrollController.jumpTo(_scrollController.position.maxScrollExtent);
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _chat.closeThread();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final chat = context.watch<ChatProvider>();
    final conversation = chat.conversation;
    final participant = conversation?.participant;

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 0,
        title: Row(
          children: [
            CircleAvatar(
              radius: 18,
              backgroundColor: Theme.of(context).scaffoldBackgroundColor,
              child: ClipOval(
                child: Helpers.imageUrl(participant?.avatar) != null
                    ? Image.network(
                        Helpers.imageUrl(participant!.avatar)!,
                        width: 36,
                        height: 36,
                        fit: BoxFit.cover,
                        errorBuilder: (_, _, _) => const Icon(
                          Icons.person,
                          color: AppColors.primary,
                        ),
                      )
                    : const Icon(Icons.person, color: AppColors.primary),
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    participant?.name ?? 'chat.title'.tr(),
                    style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
                    overflow: TextOverflow.ellipsis,
                  ),
                  Text(
                    participant?.isOnline == true
                        ? 'chat.online'.tr()
                        : 'chat.offline'.tr(),
                    style: TextStyle(
                      fontSize: 11.5,
                      color: participant?.isOnline == true
                          ? AppColors.success
                          : AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
      body: Column(
        children: [
          Expanded(child: _body(chat)),
          ChatComposer(
            enabled: chat.canSend,
            isSending: chat.isSending,
            onSend: (text) {
              chat.sendMessage(text);
              _scrollToBottom();
            },
          ),
        ],
      ),
    );
  }

  Widget _body(ChatProvider chat) {
    if (chat.isLoadingMessages && chat.messages.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }
    if (chat.messages.isEmpty) {
      return Center(
        child: Text(
          'chat.no_messages'.tr(),
          style: const TextStyle(color: AppColors.textSecondary),
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: () => chat.loadMessages(loadMore: true),
      child: ListView.builder(
        controller: _scrollController,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        itemCount: chat.messages.length + (chat.hasMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (chat.hasMore && index == 0) {
            return const Padding(
              padding: EdgeInsets.symmetric(vertical: 10),
              child: Center(
                child: SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              ),
            );
          }
          final message = chat.messages[index - (chat.hasMore ? 1 : 0)];
          return ChatBubble(
            message: message,
            onRetry: message.status == ChatMessageStatus.failed
                ? () => chat.retrySend(message)
                : null,
          );
        },
      ),
    );
  }
}
