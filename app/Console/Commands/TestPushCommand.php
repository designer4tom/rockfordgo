<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Console\Command;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

/**
 * Answers one question on a live server: does FCM accept a push for this
 * driver/customer, yes or no, and if not, why.
 *
 * The chat path hides this. It writes an in-app row first, so a refused push
 * still leaves a healthy-looking notification list, and a token FCM rejects
 * throws nothing — it comes back inside the send report. This command prints
 * that report verbatim so a driver can be compared against a customer with
 * everything else held constant.
 *
 *   php artisan push:test --driver=3
 *   php artisan push:test --driver=3 --user=12   # side by side
 */
class TestPushCommand extends Command
{
    protected $signature = 'push:test
        {--driver= : Driver id to push to}
        {--user= : Customer id to push to}
        {--type=chat : The data "type" the app switches on}
        {--body=Test push from the server : Notification body}';

    protected $description = 'Send a real FCM push to a driver and/or customer and print the raw FCM verdict';

    public function handle(): int
    {
        if (! $this->option('driver') && ! $this->option('user')) {
            $this->error('Give --driver=ID and/or --user=ID.');

            return self::FAILURE;
        }

        $this->line('');
        $this->line('<comment>Firebase configuration</comment>');
        $credentials = config('firebase.projects.app.credentials');
        $exists = $credentials && file_exists($credentials);
        $this->line('  credentials file : ' . ($exists ? "<info>found</info> ({$credentials})" : "<error>MISSING</error> ({$credentials})"));

        if ($exists) {
            $json = json_decode((string) file_get_contents($credentials), true);
            // The project the SERVER sends as. Must equal the project_id in each
            // app's google-services.json, or FCM refuses that app's tokens.
            $this->line('  server project_id: <info>' . ($json['project_id'] ?? '?') . '</info>');
            $this->line('  client_email     : ' . ($json['client_email'] ?? '?'));
        }

        $messaging = null;

        try {
            $messaging = app('firebase.messaging');
        } catch (\Throwable $e) {
            $this->line('  messaging        : <error>unavailable</error> — ' . $e->getMessage());
        }

        if (! $messaging) {
            $this->line('');
            $this->error('Firebase messaging is not available, so NO push can be sent to anyone.');

            return self::FAILURE;
        }

        $this->line('  messaging        : <info>ready</info>');

        foreach ([['driver', $this->option('driver')], ['user', $this->option('user')]] as [$type, $id]) {
            if ($id) {
                $this->probe($messaging, $type, (int) $id);
            }
        }

        $this->line('');
        $this->line('<comment>How to read this</comment>');
        $this->line('  accepted by FCM  -> the transport works; if the device still shows nothing,');
        $this->line('                      the app is not displaying the message (foreground handler,');
        $this->line('                      missing notification channel, or notifications disabled in OS settings).');
        $this->line('  SenderIdMismatch -> that app was built against a different Firebase project.');
        $this->line('  Unregistered     -> token is dead; the app must re-register on onTokenRefresh.');
        $this->line('');

        return self::SUCCESS;
    }

    private function probe($messaging, string $ownerType, int $id): void
    {
        $owner = $ownerType === 'driver' ? Driver::find($id) : User::find($id);

        $this->line('');
        $this->line('<comment>' . strtoupper($ownerType) . ' #' . $id . '</comment>' . ($owner ? ' — ' . $owner->name : ''));

        if (! $owner) {
            $this->line('  <error>no such record</error>');

            return;
        }

        $rows = DeviceToken::where('owner_type', $ownerType)->where('owner_id', $id)->pluck('token')->all();
        $legacy = $owner->fcm_token;

        $this->line('  device_tokens rows : ' . count($rows));
        $this->line('  legacy fcm_token   : ' . ($legacy ? substr($legacy, 0, 24) . '…' : '(none)'));

        // Mirror tokensFor(): device_tokens wins, legacy is only a fallback.
        $tokens = $rows ?: array_filter([$legacy]);
        $tokens = array_values(array_unique($tokens));

        if (! $tokens) {
            $this->line('  <error>NO TOKEN STORED — nothing can be delivered. The app never registered one.</error>');

            return;
        }

        $message = CloudMessage::new()
            ->withNotification(FcmNotification::create('Test push', (string) $this->option('body')))
            ->withData(['type' => (string) $this->option('type'), 'test' => '1']);

        try {
            $report = $messaging->sendMulticast($message, $tokens);
        } catch (\Throwable $e) {
            $this->line('  <error>send threw: ' . $e->getMessage() . '</error>');

            return;
        }

        $this->line('  sent to ' . count($tokens) . ' token(s): <info>' . $report->successes()->count() . ' accepted</info>, '
            . ($report->failures()->count() ? '<error>' . $report->failures()->count() . ' rejected</error>' : '0 rejected'));

        foreach ($report->failures()->getItems() as $failure) {
            $this->line('    <error>rejected</error>: ' . ($failure->error()?->getMessage() ?? 'unknown reason'));
        }

        if ($report->successes()->count() > 0) {
            $this->line('  <info>FCM accepted it. If the device shows nothing, the problem is in the app.</info>');
        }
    }
}
