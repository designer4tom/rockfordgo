import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';

/// Reusable contact card (customer / sender / receiver) with a call button.
/// Pass [onMessage] to also render a chat button right next to the call
/// button — hidden entirely while [messageLoading] resolves and until a
/// chat exists (mirrors the message-icon rules in `test/CHAT_API.md`).
class CustomerInfoCard extends StatelessWidget {
  final String name;
  final String phone;
  final String? avatar;
  final String? rating;
  final String roleLabel;
  final VoidCallback onCall;
  final VoidCallback? onMessage;
  final bool messageLoading;
  final int unreadCount;

  const CustomerInfoCard({
    super.key,
    required this.name,
    required this.phone,
    required this.onCall,
    this.avatar,
    this.rating,
    this.roleLabel = 'Customer',
    this.onMessage,
    this.messageLoading = false,
    this.unreadCount = 0,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 24,
            backgroundColor: AppColors.primary.withValues(alpha: 0.15),
            backgroundImage: Helpers.imageUrl(avatar) != null
                ? NetworkImage(Helpers.imageUrl(avatar)!)
                : null,
            child: Helpers.imageUrl(avatar) != null
                ? null
                : const Icon(Icons.person, color: AppColors.primary),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(roleLabel,
                    style: TextStyle(
                        fontSize: 11, color: Theme.of(context).hintColor)),
                Text(
                  name.isEmpty ? '—' : name,
                  style: const TextStyle(
                      fontSize: 16, fontWeight: FontWeight.bold),
                ),
                if (rating != null)
                  Row(
                    children: [
                      const Icon(Icons.star, size: 14, color: Colors.amber),
                      const SizedBox(width: 2),
                      Text(rating!,
                          style: TextStyle(
                              fontSize: 12, color: Theme.of(context).hintColor)),
                    ],
                  ),
              ],
            ),
          ),
          if (onMessage != null) ...[
            _messageButton(context),
            const SizedBox(width: 8),
          ],
          IconButton.filled(
            onPressed: onCall,
            style: IconButton.styleFrom(backgroundColor: AppColors.success),
            icon: const Icon(Icons.call, color: Colors.white),
          ),
        ],
      ),
    );
  }

  Widget _messageButton(BuildContext context) {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        IconButton.filled(
          onPressed: messageLoading ? null : onMessage,
          style: IconButton.styleFrom(backgroundColor: AppColors.primary),
          icon: messageLoading
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(
                      strokeWidth: 2, color: Colors.white),
                )
              : const Icon(Icons.message_outlined, color: Colors.white),
        ),
        if (unreadCount > 0)
          Positioned(
            right: -2,
            top: -2,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
              decoration: BoxDecoration(
                color: AppColors.danger,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                '$unreadCount',
                style: const TextStyle(
                    color: Colors.white,
                    fontSize: 10,
                    fontWeight: FontWeight.bold),
              ),
            ),
          ),
      ],
    );
  }
}
