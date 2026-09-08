import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/chat_models.dart';

/// Single chat bubble. Alignment/color come from [ChatMessage.isMine] —
/// per the API guide, never derive it from ids yourself.
class ChatBubble extends StatelessWidget {
  final ChatMessage message;
  final VoidCallback? onRetry;
  const ChatBubble({super.key, required this.message, this.onRetry});

  @override
  Widget build(BuildContext context) {
    final mine = message.isMine;
    final bg = mine ? AppColors.primary : Theme.of(context).cardColor;
    final fg = mine ? Colors.white : Theme.of(context).colorScheme.onSurface;

    return Align(
      alignment: mine ? Alignment.centerRight : Alignment.centerLeft,
      child: Column(
        crossAxisAlignment:
            mine ? CrossAxisAlignment.end : CrossAxisAlignment.start,
        children: [
          Container(
            constraints: BoxConstraints(
              maxWidth: MediaQuery.of(context).size.width * 0.75,
            ),
            margin: const EdgeInsets.symmetric(vertical: 3),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: message.failed
                  ? AppColors.danger.withValues(alpha: 0.15)
                  : bg,
              borderRadius: BorderRadius.only(
                topLeft: const Radius.circular(16),
                topRight: const Radius.circular(16),
                bottomLeft: Radius.circular(mine ? 16 : 4),
                bottomRight: Radius.circular(mine ? 4 : 16),
              ),
              border: mine ? null : Border.all(color: AppColors.border),
            ),
            child: Text(
              message.body,
              style: TextStyle(
                color: message.failed ? AppColors.danger : fg,
                fontSize: 14.5,
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 4),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (message.failed) ...[
                  GestureDetector(
                    onTap: onRetry,
                    child: const Icon(Icons.refresh,
                        size: 13, color: AppColors.danger),
                  ),
                  const SizedBox(width: 4),
                  Text('Tap to retry',
                      style: TextStyle(
                          fontSize: 11, color: AppColors.danger)),
                ] else ...[
                  Text(
                    Helpers.time(message.createdAt),
                    style: TextStyle(
                        fontSize: 11, color: Theme.of(context).hintColor),
                  ),
                  if (mine) ...[
                    const SizedBox(width: 4),
                    Icon(
                      message.sending
                          ? Icons.access_time
                          : (message.isRead
                              ? Icons.done_all
                              : Icons.done),
                      size: 14,
                      color: message.isRead
                          ? AppColors.primary
                          : Theme.of(context).hintColor,
                    ),
                  ],
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}
