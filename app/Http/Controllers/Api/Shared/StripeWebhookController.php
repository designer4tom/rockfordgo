<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StripeService;
use App\Services\WalletService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    use ApiResponse;

    public function __construct(
        private StripeService $stripe,
        private WalletService $wallet,
    ) {
    }

    public function handle(Request $request)
    {
        $secret = $this->stripe->webhookSecret();

        // Verify the signature when a webhook secret is configured.
        if ($secret && ! $this->verifySignature($request, $secret)) {
            return response()->json(['success' => false, 'message' => 'Invalid signature.'], 400);
        }

        $event = $request->json()->all();
        $type = $event['type'] ?? null;
        $object = $event['data']['object'] ?? [];

        if ($type === 'payment_intent.succeeded') {
            $this->creditTopup($object);
        } elseif ($type === 'payment_intent.payment_failed') {
            Log::info('Stripe payment failed for intent ' . ($object['id'] ?? '?'));
        }

        return response()->json(['success' => true]);
    }

    private function creditTopup(array $intent): void
    {
        $id = $intent['id'] ?? null;
        $userId = $intent['metadata']['user_id'] ?? null;
        $purpose = $intent['metadata']['purpose'] ?? null;

        if (! $id || $purpose !== 'wallet_topup' || ! $userId) {
            return;
        }

        $cacheKey = 'topup_done_' . $id;
        if (Cache::has($cacheKey)) {
            return; // already processed (idempotency)
        }

        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $amount = round(((int) ($intent['amount'] ?? 0)) / 100, 2);
        if ($amount > 0) {
            // Clears any outstanding sender due first, then tops up the balance.
            $this->wallet->processUserTopup($user, $amount, 'top_up', 'Wallet top-up via card (webhook)');
            Cache::put($cacheKey, true, now()->addDays(7));
        }
    }

    private function verifySignature(Request $request, string $secret): bool
    {
        $header = $request->header('Stripe-Signature', '');
        if (! preg_match('/t=(\d+),v1=([a-f0-9]+)/', $header, $m)) {
            return false;
        }
        [, $timestamp, $signature] = $m;
        $expected = hash_hmac('sha256', $timestamp . '.' . $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
