<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Seeder;

/**
 * Seeds payment gateway placeholders.
 *
 * SECURITY:
 * Real payment credentials must never be committed to source control.
 * Configure payment gateways through secure application settings or
 * environment variables before enabling them.
 */
class MultiPayGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            'Adyen' => [
                'is_active' => false,
                'api_key_data' => 'YOUR_ADYEN_API_KEY',
                'merchant_account_name' => 'YOUR_ADYEN_MERCHANT_ACCOUNT',
                'country_code_data' => 'US',
            ],

            'Stripe' => [
                'is_active' => false,
                'secret_key_data' => 'YOUR_STRIPE_SECRET_KEY',
                'public_key_data' => 'YOUR_STRIPE_PUBLIC_KEY',
            ],

            'PayTabs' => [
                'is_active' => false,
                'profile_id' => 'YOUR_PAYTABS_PROFILE_ID',
                'secret_key_data' => 'YOUR_PAYTABS_SECRET_KEY',
                'base_url' => 'https://secure-global.paytabs.com',
            ],

            'Square' => [
                'is_active' => false,
                'access_token' => 'YOUR_SQUARE_ACCESS_TOKEN',
                'location_id' => 'YOUR_SQUARE_LOCATION_ID',
                'environment' => 'Sandbox',
            ],

            'PayStack' => [
                'is_active' => false,
                'secret_key_data' => 'YOUR_PAYSTACK_SECRET_KEY',
            ],

            'RazorPay' => [
                'is_active' => false,
                'secret_key_data' => 'YOUR_RAZORPAY_SECRET_KEY',
                'public_key_data' => 'YOUR_RAZORPAY_PUBLIC_KEY',
            ],

            'Braintree' => [
                'is_active' => false,
                'environment' => 'sandbox',
                'merchant_id' => 'YOUR_BRAINTREE_MERCHANT_ID',
                'public_key' => 'YOUR_BRAINTREE_PUBLIC_KEY',
                'private_key' => 'YOUR_BRAINTREE_PRIVATE_KEY',
            ],

            'WorldPay' => [
                'is_active' => false,
                'authorization' => 'YOUR_WORLDPAY_AUTHORIZATION',
                'merchant_entity' => 'YOUR_WORLDPAY_MERCHANT_ENTITY',
                'narrative_line1' => 'RockfordGo',
                'base_url' => 'https://try.access.worldpay.com',
            ],

            'Mollie' => [
                'is_active' => false,
                'api_key' => 'YOUR_MOLLIE_API_KEY',
                'base_url' => 'https://api.mollie.com',
                'send_webhook_url' => 'false',
            ],

            'PayU' => [
                'is_active' => false,
                'merchant_key' => 'YOUR_PAYU_MERCHANT_KEY',
                'salt' => 'YOUR_PAYU_SALT',
                'base_url' => 'https://test.payu.in',
            ],

            'CashFree' => [
                'is_active' => false,
                'client_id' => 'YOUR_CASHFREE_CLIENT_ID',
                'client_secret' => 'YOUR_CASHFREE_CLIENT_SECRET',
                'base_url' => 'https://sandbox.cashfree.com',
                'api_version' => '2025-01-01',
                'environment' => 'sandbox',
            ],

            'FlutterWave' => [
                'is_active' => false,
                'secret_key' => 'YOUR_FLUTTERWAVE_SECRET_KEY',
                'base_url' => 'https://api.flutterwave.com',
            ],

            'Hesabe' => [
                'is_active' => false,
                'merchant_code' => 'YOUR_HESABE_MERCHANT_CODE',
                'access_code' => 'YOUR_HESABE_ACCESS_CODE',
                'secret_key' => 'YOUR_HESABE_SECRET_KEY',
                'iv_key' => 'YOUR_HESABE_IV_KEY',
                'base_url' => 'https://sandbox.hesabe.com',
                'mode' => 'sandbox',
            ],
        ];

        $created = 0;

        foreach ($gateways as $name => $data) {
            $row = Gateway::firstOrCreate(
                ['name' => $name],
                ['data' => json_encode($data)]
            );

            if ($row->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command?->info(
            "Seeded {$created} payment gateway placeholder(s); existing rows left untouched."
        );
    }
}