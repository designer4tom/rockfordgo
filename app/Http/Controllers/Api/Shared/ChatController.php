<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Driver;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * Ride/parcel chat — one controller serving BOTH apps.
 *
 * The caller's identity comes from the Sanctum token, so the same endpoints are
 * mounted under /user/chat and /driver/chat; whoAmI() resolves which side is
 * talking and every query is scoped to that participant.
 */
class ChatController extends Controller
{
    use ApiResponse;

    public function __construct(private ChatService $chat)
    {
    }

    /** GET /chat/conversations — inbox, newest activity first. */
    public function index(Request $request)
    {
        [$type, $id] = $this->whoAmI($request);

        $conversations = Conversation::query()
            ->forParticipant($type, $id)
            ->with(['order:id,order_number,type,status', 'user:id,name,avatar,last_seen_at', 'driver:id,name,avatar,last_seen_at'])
            ->when($request->boolean('active_only'), fn ($q) => $q->active())
            ->orderByRaw('COALESCE(last_message_at, created_at) DESC')
            ->paginate(min((int) $request->integer('per_page', 20), 50));

        $items = $conversations->getCollection()
            ->map(fn (Conversation $c) => $this->conversationPayload($c, $type))
            ->all();

        return $this->paginated($conversations, $items, 'Conversations fetched.');
    }

    /**
     * GET /chat/order/{orderId} — the chat for one order.
     * This is what the apps call when the ride screen opens.
     */
    public function showForOrder(Request $request, string $orderId)
    {
        [$type, $id] = $this->whoAmI($request);

        $conversation = Conversation::where('order_id', $orderId)
            ->forParticipant($type, $id)
            ->with(['order:id,order_number,type,status', 'user:id,name,avatar,last_seen_at', 'driver:id,name,avatar,last_seen_at'])
            ->first();

        // Self-heal: an order that was already under way before this feature
        // shipped never fired the "accepted" transition, so it has no
        // conversation. Create it on demand for orders that are still ongoing
        // and belong to the caller — a finished order stays 404.
        if (! $conversation) {
            $conversation = $this->openLegacyConversation($orderId, $type, $id);
        }

        if (! $conversation) {
            return $this->error('No chat for this order yet.', 404);
        }

        return $this->success(
            ['conversation' => $this->conversationPayload($conversation, $type)],
            'Conversation fetched.'
        );
    }

    /**
     * GET /chat/{conversation}/messages — cursor paginated, oldest→newest.
     * Opening the thread marks the other side's messages as read.
     */
    public function messages(Request $request, string $conversation)
    {
        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'before_id' => ['nullable', 'integer', 'min:1'],
        ]);

        [$type, $id, $convo] = $this->authorizeConversation($request, $conversation);
        if (! $convo) {
            return $this->error('Conversation not found.', 404);
        }

        $limit = (int) ($data['limit'] ?? 30);

        $rows = Message::where('conversation_id', $convo->id)
            ->when($data['before_id'] ?? null, fn ($q, $before) => $q->where('id', '<', $before))
            ->orderByDesc('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit)->reverse()->values();

        // Opening the thread = reading it.
        $this->chat->markRead($convo, $type);

        return $this->success([
            'conversation' => $this->conversationPayload($convo->fresh(), $type),
            'messages' => $rows->map(fn (Message $m) => $this->messagePayload($m, $type))->all(),
            'pagination' => [
                'has_more' => $hasMore,
                'next_before_id' => $hasMore ? $rows->first()?->id : null,
                'limit' => $limit,
            ],
        ], 'Messages fetched.');
    }

    /** POST /chat/{conversation}/send — { body } */
    public function send(Request $request, string $conversation)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        [$type, $id, $convo] = $this->authorizeConversation($request, $conversation);
        if (! $convo) {
            return $this->error('Conversation not found.', 404);
        }

        $message = $this->chat->send($convo, $type, $id, trim($data['body']));

        // Closed chat: the ride is over, nothing more can be said.
        if (! $message) {
            return $this->error('This chat has ended because the order is no longer active.', 422, [
                'conversation_status' => 'closed',
            ]);
        }

        return $this->success(
            ['message' => $this->messagePayload($message, $type)],
            'Message sent.',
            201
        );
    }

    /** POST /chat/{conversation}/read — optional { message_ids: [] } */
    public function markRead(Request $request, string $conversation)
    {
        $data = $request->validate([
            'message_ids' => ['nullable', 'array'],
            'message_ids.*' => ['integer'],
        ]);

        [$type, $id, $convo] = $this->authorizeConversation($request, $conversation);
        if (! $convo) {
            return $this->error('Conversation not found.', 404);
        }

        $count = $this->chat->markRead($convo, $type, $data['message_ids'] ?? []);

        return $this->success([
            'read_count' => $count,
            'unread_count' => $this->chat->unreadFor($convo, $type),
        ], 'Messages marked as read.');
    }

    /** GET /chat/unread-count — badge for the whole inbox. */
    public function unreadCount(Request $request)
    {
        [$type, $id] = $this->whoAmI($request);

        return $this->success([
            'unread_count' => $this->chat->unreadTotal($type, $id),
        ], 'Unread count fetched.');
    }

    /** POST /chat/heartbeat — call every ~60s while a chat screen is open. */
    public function heartbeat(Request $request)
    {
        [$type, $id] = $this->whoAmI($request);
        $this->chat->touchPresence($type, $id);

        return $this->success([
            'is_online' => true,
            'last_seen_at' => now()->toISOString(),
            'heartbeat_interval' => 60,
        ], 'Presence updated.');
    }

    /** POST /chat/offline — call when leaving the chat / backgrounding. */
    public function offline(Request $request)
    {
        [$type, $id] = $this->whoAmI($request);
        $this->chat->goOffline($type, $id);

        return $this->success(['is_online' => false], 'Marked offline.');
    }

    // ---------------------------------------------------------------------

    /**
     * Open the chat for an in-flight order that predates this feature.
     * Returns null unless the order is still ongoing, has a driver, and the
     * caller is genuinely one of its two participants.
     */
    private function openLegacyConversation(string $orderId, string $type, int $id): ?Conversation
    {
        $order = \App\Models\Order::query()
            ->whereKey($orderId)
            ->whereNotNull('driver_id')
            ->whereIn('status', \App\Models\Order::ONGOING_STATUSES)
            ->when($type === 'driver', fn ($q) => $q->where('driver_id', $id), fn ($q) => $q->where('user_id', $id))
            ->first();

        if (! $order) {
            return null;
        }

        $conversation = $this->chat->openForOrder($order);

        return $conversation?->load([
            'order:id,order_number,type,status',
            'user:id,name,avatar,last_seen_at',
            'driver:id,name,avatar,last_seen_at',
        ]);
    }

    /** Which side of the chat is calling — ['user'|'driver', id]. */
    private function whoAmI(Request $request): array
    {
        $account = $request->user();

        return $account instanceof Driver
            ? ['driver', (int) $account->id]
            : ['user', (int) $account->id];
    }

    /** Resolve the conversation and confirm the caller is a participant. */
    private function authorizeConversation(Request $request, string $conversationId): array
    {
        [$type, $id] = $this->whoAmI($request);

        $conversation = Conversation::query()
            ->whereKey($conversationId)
            ->forParticipant($type, $id)
            ->with(['order:id,order_number,type,status', 'user:id,name,avatar,last_seen_at', 'driver:id,name,avatar,last_seen_at'])
            ->first();

        return [$type, $id, $conversation];
    }

    private function conversationPayload(Conversation $c, string $viewerType): array
    {
        // Show the OTHER person in the thread header.
        $other = $viewerType === 'driver' ? $c->user : $c->driver;
        $presence = $this->chat->presenceOf($other);
        $last = $c->relationLoaded('latestMessage') ? $c->latestMessage : $c->latestMessage()->first();

        return [
            'id' => $c->id,
            'order_id' => (int) $c->order_id,
            // Subscribe to exactly this string. It is built from the CONVERSATION
            // id, never the order id — the two differ, and using order_id here is
            // the one mistake that makes /broadcasting/auth return 403.
            'channel' => 'private-conversation.' . $c->id,
            'order_number' => $c->order->order_number ?? null,
            'order_type' => $c->order->type ?? null,
            'order_status' => $c->order->status ?? null,
            'status' => $c->status,
            'is_active' => $c->isActive(),
            'can_send' => $c->isActive(),
            'closed_at' => $c->closed_at?->toISOString(),
            'participant' => [
                'type' => $viewerType === 'driver' ? 'user' : 'driver',
                'id' => (int) ($other->id ?? 0),
                'name' => $other->name ?? null,
                'avatar' => $other && $other->avatar ? asset('storage/' . $other->avatar) : null,
                'is_online' => $presence['is_online'],
                'last_seen_at' => $presence['last_seen_at'],
            ],
            'unread_count' => $this->chat->unreadFor($c, $viewerType),
            'last_message' => $last ? [
                'body' => $last->body,
                'sender_type' => $last->sender_type,
                'is_mine' => $last->sender_type === $viewerType,
                'created_at' => $last->created_at->toISOString(),
            ] : null,
            'last_message_at' => $c->last_message_at?->toISOString(),
        ];
    }

    private function messagePayload(Message $m, string $viewerType): array
    {
        return [
            'id' => $m->id,
            'conversation_id' => (int) $m->conversation_id,
            'body' => $m->body,
            'sender_type' => $m->sender_type,
            'sender_id' => (int) $m->sender_id,
            'is_mine' => $m->sender_type === $viewerType,
            'is_read' => $m->isRead(),
            'read_at' => $m->read_at?->toISOString(),
            'created_at' => $m->created_at->toISOString(),
        ];
    }
}
