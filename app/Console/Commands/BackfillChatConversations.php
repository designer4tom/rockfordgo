<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Order;
use App\Services\ChatService;
use Illuminate\Console\Command;

/**
 * Creates chat conversations for orders that were already under way when the
 * chat feature was deployed.
 *
 * The lifecycle is driven by the "accepted" status transition, so an order that
 * was accepted BEFORE deployment never fired it and has no conversation — the
 * apps would get a permanent 404 for those rides. Run this once after deploying.
 */
class BackfillChatConversations extends Command
{
    protected $signature = 'chat:backfill {--dry-run : List what would be created without writing}';

    protected $description = 'Open chat conversations for in-progress orders that predate the chat feature';

    public function handle(ChatService $chat): int
    {
        $orders = Order::query()
            ->whereNotNull('driver_id')
            ->whereNotNull('user_id')
            ->whereIn('status', Order::ONGOING_STATUSES)
            ->whereNotIn('id', Conversation::query()->select('order_id'))
            ->get(['id', 'order_number', 'type', 'status', 'user_id', 'driver_id']);

        if ($orders->isEmpty()) {
            $this->info('Nothing to backfill — every ongoing order already has a chat.');

            return self::SUCCESS;
        }

        $this->info($orders->count() . ' ongoing order(s) without a chat:');
        $this->table(
            ['Order', 'Number', 'Type', 'Status'],
            $orders->map(fn ($o) => [$o->id, $o->order_number, $o->type, $o->status])->all()
        );

        if ($this->option('dry-run')) {
            $this->comment('Dry run — nothing written. Re-run without --dry-run to create them.');

            return self::SUCCESS;
        }

        $created = 0;
        foreach ($orders as $order) {
            if ($chat->openForOrder($order)) {
                $created++;
            }
        }

        $this->info("Created {$created} conversation(s).");

        return self::SUCCESS;
    }
}
