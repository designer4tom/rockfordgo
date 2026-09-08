import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/loading_indicator.dart';
import '../../model/chat_models.dart';
import '../../provider/chat_provider.dart';
import '../widgets/chat_bubble.dart';

/// Ride/parcel chat thread. Expects [ChatProvider.conversation] to already
/// be resolved (the order screen calls `loadForOrder` before showing the
/// message icon) — this screen only opens the thread and drives sending.
class ChatScreen extends StatefulWidget {
  const ChatScreen({super.key});

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final _controller = TextEditingController();
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      await context.read<ChatProvider>().openThread();
      _jumpToBottom();
    });
  }

  void _onScroll() {
    // Reversed list → "top" of the visible content is near maxScrollExtent.
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 80) {
      context.read<ChatProvider>().loadOlder();
    }
  }

  void _jumpToBottom() {
    if (!_scrollController.hasClients) return;
    _scrollController.jumpTo(0);
  }

  Future<void> _send() async {
    final text = _controller.text;
    if (text.trim().isEmpty) return;
    _controller.clear();
    await context.read<ChatProvider>().send(text);
    if (_scrollController.hasClients) {
      _scrollController.animateTo(
        0,
        duration: const Duration(milliseconds: 250),
        curve: Curves.easeOut,
      );
    }
  }

  @override
  void dispose() {
    context.read<ChatProvider>().closeThread();
    _controller.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<ChatProvider>();
    final conversation = p.conversation;

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 0,
        title: conversation == null
            ? const Text('Chat')
            : _AppBarTitle(participant: conversation.participant),
      ),
      body: conversation == null
          ? const LoadingIndicator()
          : Column(
              children: [
                if (p.error != null && p.messages.isEmpty)
                  Padding(
                    padding: const EdgeInsets.all(16),
                    child: Text(p.error!,
                        style: const TextStyle(color: AppColors.danger)),
                  ),
                Expanded(
                  child: p.loadingMessages && p.messages.isEmpty
                      ? const LoadingIndicator()
                      : _messageList(p),
                ),
                if (conversation.isClosed) _closedBanner(),
                _composer(p),
              ],
            ),
    );
  }

  Widget _messageList(ChatProvider p) {
    if (p.messages.isEmpty) {
      return Center(
        child: Text('No messages yet — say hello 👋',
            style: TextStyle(color: Theme.of(context).hintColor)),
      );
    }
    final ordered = p.messages.reversed.toList();
    return ListView.builder(
      controller: _scrollController,
      reverse: true,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      itemCount: ordered.length + (p.loadingMore ? 1 : 0),
      itemBuilder: (context, index) {
        if (p.loadingMore && index == ordered.length) {
          return const Padding(
            padding: EdgeInsets.symmetric(vertical: 12),
            child: Center(
              child: SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(strokeWidth: 2),
              ),
            ),
          );
        }
        final m = ordered[index];
        return ChatBubble(
          message: m,
          onRetry: m.failed ? () => context.read<ChatProvider>().retry(m) : null,
        );
      },
    );
  }

  Widget _closedBanner() {
    return Container(
      width: double.infinity,
      color: AppColors.border.withValues(alpha: 0.4),
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: const Text(
        'This chat has ended',
        textAlign: TextAlign.center,
        style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w500),
      ),
    );
  }

  Widget _composer(ChatProvider p) {
    final enabled = p.canSend && !p.sending;
    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(10, 8, 10, 10),
        child: Row(
          children: [
            Expanded(
              child: TextField(
                controller: _controller,
                enabled: enabled,
                minLines: 1,
                maxLines: 4,
                maxLength: 2000,
                buildCounter: (context,
                        {required currentLength,
                        required isFocused,
                        maxLength}) =>
                    null,
                decoration: InputDecoration(
                  hintText: p.canSend ? 'Type a message…' : 'Chat ended',
                  filled: true,
                  fillColor: Theme.of(context).scaffoldBackgroundColor,
                  contentPadding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(24),
                    borderSide: BorderSide.none,
                  ),
                ),
                onSubmitted: enabled ? (_) => _send() : null,
              ),
            ),
            const SizedBox(width: 8),
            Material(
              color: enabled ? AppColors.primary : AppColors.border,
              shape: const CircleBorder(),
              child: InkWell(
                customBorder: const CircleBorder(),
                onTap: enabled ? _send : null,
                child: const Padding(
                  padding: EdgeInsets.all(11),
                  child: Icon(Icons.send, color: Colors.white, size: 20),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _AppBarTitle extends StatelessWidget {
  final ChatParticipant participant;
  const _AppBarTitle({required this.participant});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        CircleAvatar(
          radius: 18,
          backgroundColor: AppColors.border,
          backgroundImage: Helpers.imageUrl(participant.avatar) != null
              ? NetworkImage(Helpers.imageUrl(participant.avatar)!)
              : null,
          child: Helpers.imageUrl(participant.avatar) == null
              ? const Icon(Icons.person, color: AppColors.textSecondary)
              : null,
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(participant.name,
                  style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
                  overflow: TextOverflow.ellipsis),
              Text(
                participant.isOnline ? 'Online' : 'Offline',
                style: TextStyle(
                  fontSize: 11.5,
                  color:
                      participant.isOnline ? AppColors.online : Theme.of(context).hintColor,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
