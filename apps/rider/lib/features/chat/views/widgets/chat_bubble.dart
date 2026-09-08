import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/chat_message_model.dart';

class ChatBubble extends StatelessWidget {
  final ChatMessageModel message;
  final VoidCallback? onRetry;

  const ChatBubble({super.key, required this.message, this.onRetry});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isMine = message.isMine;
    final failed = message.status == ChatMessageStatus.failed;

    final bubble = Container(
      constraints: BoxConstraints(
        maxWidth: MediaQuery.of(context).size.width * 0.72,
      ),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: isMine
            ? (failed ? AppColors.danger.withValues(alpha: 0.15) : AppColors.primary)
            : theme.cardColor,
        borderRadius: BorderRadius.only(
          topLeft: const Radius.circular(14),
          topRight: const Radius.circular(14),
          bottomLeft: Radius.circular(isMine ? 14 : 2),
          bottomRight: Radius.circular(isMine ? 2 : 14),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Text(
            message.body,
            style: TextStyle(
              fontSize: 14.5,
              height: 1.35,
              color: isMine
                  ? (failed ? AppColors.danger : Colors.white)
                  : theme.colorScheme.onSurface,
            ),
          ),
          const SizedBox(height: 4),
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                Helpers.formatTime(message.createdAt),
                style: TextStyle(
                  fontSize: 10.5,
                  color: isMine
                      ? Colors.white.withValues(alpha: 0.75)
                      : AppColors.textSecondary,
                ),
              ),
              if (isMine) ...[
                const SizedBox(width: 4),
                if (message.status == ChatMessageStatus.sending)
                  SizedBox(
                    width: 10,
                    height: 10,
                    child: CircularProgressIndicator(
                      strokeWidth: 1.5,
                      color: Colors.white.withValues(alpha: 0.75),
                    ),
                  )
                else if (failed)
                  const Icon(Icons.error_outline,
                      size: 13, color: AppColors.danger)
                else
                  Icon(
                    message.isRead ? Icons.done_all : Icons.done,
                    size: 14,
                    color: message.isRead
                        ? Colors.lightBlueAccent
                        : Colors.white.withValues(alpha: 0.75),
                  ),
              ],
            ],
          ),
        ],
      ),
    );

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment:
            isMine ? MainAxisAlignment.end : MainAxisAlignment.start,
        children: [
          if (failed && onRetry != null)
            Padding(
              padding: const EdgeInsets.only(right: 6),
              child: InkWell(
                onTap: onRetry,
                child: const Icon(Icons.refresh,
                    size: 18, color: AppColors.danger),
              ),
            ),
          Flexible(child: bubble),
        ],
      ),
    );
  }
}
