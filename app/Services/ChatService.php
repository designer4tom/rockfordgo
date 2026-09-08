<?php

namespace App\Services;

use App\Events\ChatConversationClosed;
use App\Events\ChatMessageSent;
use App\Events\ChatMessagesRead;
use App\Models\Conversation;
use App\Models\Driver;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Ride/parcel chat between the customer and the assigned driver.
 *
 * Lifecycle (driven by order status — see OrderObserver):
 *   driver accepts  → openForOrder()  → conversation 'active'
 *   ride ends       → closeForOrder() → conversation 'closed', no more messages
 *   next order      → a NEW conversation, because it is keyed on order_id
 *
 * Presence is a heartbeat timestamp, not a flag: drivers.is_online already means
 * "available for dispatch" and must not be touched by chat.
 */
class ChatService
{
    // A participant counts as online if seen within this window.
    public const PRESENCE_WINDOW_SECONDS = 120;

    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Open the chat for an order. Idempotent — re-accepting or a retried job
     * returns the existing conversation instead of creating a duplicate.
     */
    public function openForOrder(Order $order): ?Conversation
    {
        if (! $order->user_id || ! $order->driver_id) {
            return null;
        }

        return DB::transaction(function () use ($order) {
            $conversation = Conversation::where('order_id', $order->id)->lockForUpdate()->first();

            if ($conversation) {
                // Driver re-assigned to the same order: point the chat at them
                // and reopen, so the customer isn't stuck talking to nobody.
                if ((int) $conversation->driver_id !== (int) $order->driver_id) {
                    $conversation->update([
                        'driver_id' => $order->driver_id,
                        'status' => Conversation::STATUS_ACTIVE,
                        'closed_at' => null,
                    ]);
                }

                return $conversation;
            }

            return Conversation::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'driver_id' => $order->driver_id,
                'status' => Conversation::STATUS_ACTIVE,
            ]);
        });
    }

    /**
     * Close the chat when the order reaches a final state. Idempotent.
     */
    public function closeForOrder(Order $order, string $reason = 'completed'): ?Conversation
    {
        $conversation = Conversation::where('order_id', $order->id)->first();

        if (! $conversation || ! $conversation->isActive()) {
            return $conversation;
        }

        $conversation->update([
            'status' => Conversation::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        broadcast(new ChatConversationClosed($conversation->id, (int) $order->id, $reason));

        return $conversation->refresh();
    }

    /**
     * Post a message. Returns null when the conversation is closed — a finished
     * ride can never be messaged again, which is the whole point of closing.
     */
    public function send(Conversation $conversation, string $senderType, int $senderId, string $body): ?Message
    {
        if (! $conversation->isActive()) {
            return null;
        }

        $message = DB::transaction(function () use ($conversation, $senderType, $senderId, $body) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_type' => $senderType,
                'sender_id' => $senderId,
                'body' => $body,
            ]);

            $conversation->update(['last_message_at' => $message->created_at]);

            return $message;
        });

        $senderName = $this->displayName($senderType, $senderId);

        broadcast(new ChatMessageSent($message, $senderName));
        $this->pushToRecipient($conversation, $senderType, $senderName, $body);

        return $message;
    }

    /**
     * Mark everything the other side sent as read. Returns how many changed.
     */
    public function markRead(Conversation $conversation, string $readerType, array $messageIds = []): int
    {
        $count = Message::query()
            ->where('conversation_id', $conversation->id)
            ->receivedBy($readerType)
            ->unread()
            ->when($messageIds, fn ($q) => $q->whereIn('id', $messageIds))
            ->update(['read_at' => now()]);

        if ($count > 0) {
            broadcast(new ChatMessagesRead($conversation->id, $readerType, $count));
        }

        return $count;
    }

    // Unread messages waiting for this participant, across all their chats.
    public function unreadTotal(string $type, int $id): int
    {
        return Message::query()
            ->receivedBy($type)
            ->unread()
            ->whereIn('conversation_id', Conversation::query()->forParticipant($type, $id)->select('id'))
            ->count();
    }

    public function unreadFor(Conversation $conversation, string $type): int
    {
        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->receivedBy($type)
            ->unread()
            ->count();
    }

    // ----- Presence -----

    public function touchPresence(string $type, int $id): void
    {
        $type === 'driver'
            ? Driver::whereKey($id)->update(['last_seen_at' => now()])
            : User::whereKey($id)->update(['last_seen_at' => now()]);
    }

    public function goOffline(string $type, int $id): void
    {
        // Backdate past the window so the other side sees them as offline now.
        $stale = now()->subSeconds(self::PRESENCE_WINDOW_SECONDS + 1);

        $type === 'driver'
            ? Driver::whereKey($id)->update(['last_seen_at' => $stale])
            : User::whereKey($id)->update(['last_seen_at' => $stale]);
    }

    public function presenceOf(?\Illuminate\Database\Eloquent\Model $account): array
    {
        $seen = $account?->last_seen_at;

        return [
            'is_online' => $seen !== null && $seen->gt(now()->subSeconds(self::PRESENCE_WINDOW_SECONDS)),
            'last_seen_at' => $seen?->toISOString(),
        ];
    }

    // ----- Internals -----

    // Push + in-app notice so a backgrounded app still learns about the message.
    private function pushToRecipient(Conversation $conversation, string $senderType, string $senderName, string $body): void
    {
        [$targetType, $targetId] = $conversation->counterpart($senderType);

        try {
            $this->notifications->sendPush(
                $targetType,
                $targetId,
                $senderName,
                mb_strimwidth($body, 0, 120, '…'),
                'chat',
                [
                    'conversation_id' => (string) $conversation->id,
                    'order_id' => (string) $conversation->order_id,
                ],
            );
        } catch (\Throwable $e) {
            // Never let a push failure break sending a message.
            Log::warning('Chat push failed: ' . $e->getMessage());
        }
    }

    private function displayName(string $type, int $id): string
    {
        $name = $type === 'driver'
            ? Driver::whereKey($id)->value('name')
            : User::whereKey($id)->value('name');

        return (string) ($name ?: ($type === 'driver' ? 'Driver' : 'Customer'));
    }
}
