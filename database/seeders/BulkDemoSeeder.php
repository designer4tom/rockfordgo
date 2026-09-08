<?php

namespace Database\Seeders;

use App\Models\Dispute;
use App\Models\Driver;
use App\Models\DriverDocument;
use App\Models\DriverShiftLog;
use App\Models\DriverVehicle;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Rating;
use App\Models\Referral;
use App\Models\Service;
use App\Models\SosAlert;
use App\Models\User;
use App\Models\VehicleCategory;
use App\Models\WalletTransaction;
use App\Models\WithdrawalMethod;
use App\Models\WithdrawalRequest;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Adds a LARGER batch of demo data on top of DemoDataSeeder — more customers,
 * drivers (with vehicles/documents/shifts/wallet), and orders with ratings,
 * wallet transactions, COD payouts, withdrawals, disputes, SOS & notifications.
 * Uses distinct 0171…/0181… phone ranges so it never clashes. Idempotent.
 */
class BulkDemoSeeder extends Seeder
{
    private array $points = [
        ['Mirpur 10', 23.8069, 90.3687], ['Dhanmondi 27', 23.7946, 90.3713],
        ['Gulshan 1', 23.7806, 90.4152], ['Banani', 23.7937, 90.4066],
        ['Uttara Sector 7', 23.8759, 90.3795], ['Motijheel', 23.7330, 90.4172],
        ['Bashundhara', 23.8103, 90.4370], ['Mohammadpur', 23.7660, 90.3590],
    ];

    private const CUSTOMERS = 25;
    private const DRIVERS = 15;
    private const ORDERS = 80;

    public function run(): void
    {
        if (User::where('phone', '01710000000')->exists()) {
            $this->command?->warn('Bulk demo data already seeded — skipping.');
            return;
        }

        DB::transaction(function () {
            $customers = $this->seedCustomers();
            $drivers = $this->seedDrivers();
            $this->seedReferrals($customers);
            $this->seedOrders($customers, $drivers);
            $this->seedWithdrawals($customers, $drivers);
            $this->seedSos($customers, $drivers);
        });

        $this->command?->info('Bulk demo data seeded ✓ (' . self::CUSTOMERS . ' customers, ' . self::DRIVERS . ' drivers, ' . self::ORDERS . '+ orders)');
    }

    private function seedCustomers(): array
    {
        $customers = [];
        for ($i = 0; $i < self::CUSTOMERS; $i++) {
            $name = fake()->name();
            $balance = fake()->randomElement([0, 0, 150, 300, 500, 750, 1200, 2000]);

            $c = User::create([
                'name' => $name,
                'phone' => '0171' . str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'email' => 'bulkcust' . $i . '@readyride.test',
                'wallet_balance' => $balance,
                'referral_code' => strtoupper(fake()->bothify('????##')),
                'withdrawal_method' => fake()->randomElement([null, 'bkash', 'nagad', 'bank']),
                'withdrawal_account' => fake()->boolean(60) ? '0171' . fake()->numerify('#######') : null,
                'is_active' => fake()->boolean(92),
            ]);

            if ($balance > 0) {
                WalletTransaction::create([
                    'owner_type' => 'user', 'owner_id' => $c->id, 'type' => 'credit', 'category' => 'top_up',
                    'amount' => $balance, 'balance_before' => 0, 'balance_after' => $balance, 'note' => 'Wallet top-up',
                ]);
            }
            $customers[] = $c;
        }

        return $customers;
    }

    private function seedDrivers(): array
    {
        $zones = Zone::pluck('id')->all();
        $categories = VehicleCategory::all();
        $drivers = [];

        for ($i = 0; $i < self::DRIVERS; $i++) {
            $status = fake()->randomElement(['approved', 'approved', 'approved', 'pending', 'suspended']);
            $approved = $status === 'approved';
            $cat = $categories->random();
            [$pName, $lat, $lng] = $this->points[$i % count($this->points)];
            $due = fake()->randomElement([0, 0, 0, 120, 350, 550]);
            $wallet = $approved ? fake()->numberBetween(0, 3000) : 0;

            $d = Driver::create([
                'name' => fake()->name('male'),
                'phone' => '0181' . str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'email' => 'bulkdriver' . $i . '@readyride.test',
                'status' => $status,
                'is_online' => $approved && fake()->boolean(50),
                'is_active' => true,
                'wallet_balance' => $wallet,
                'due_amount' => $due,
                'can_accept_orders' => $approved && $due < 500,
                'average_rating' => $approved ? fake()->randomFloat(1, 3.5, 5) : 0,
                'total_trips' => $approved ? fake()->numberBetween(10, 500) : 0,
                'acceptance_rate' => $approved ? fake()->numberBetween(70, 99) : 0,
                'completion_rate' => $approved ? fake()->numberBetween(80, 100) : 0,
                'cancellation_rate' => $approved ? fake()->numberBetween(0, 15) : 0,
                'current_lat' => $lat, 'current_lng' => $lng, 'last_location_at' => now(),
                'zone_id' => $zones ? fake()->randomElement($zones) : null,
            ]);

            DriverVehicle::create([
                'driver_id' => $d->id, 'vehicle_category_id' => $cat->id,
                'make' => fake()->randomElement(['Honda', 'Yamaha', 'Toyota', 'Suzuki']),
                'model' => fake()->randomElement(['CB150R', 'FZ', 'Axio', 'Alto']),
                'year' => (string) fake()->numberBetween(2016, 2023),
                'color' => fake()->safeColorName(),
                'registration_number' => 'DHA-' . fake()->numberBetween(1000, 9999),
                'front_photo' => 'demo/vehicle.png', 'back_photo' => 'demo/vehicle.png', 'is_active' => true,
            ]);

            foreach (['nid' => null, 'driving_license' => now()->addYear(), 'vehicle_registration' => now()->addMonths(fake()->numberBetween(1, 12))] as $type => $expiry) {
                DriverDocument::create([
                    'driver_id' => $d->id, 'type' => $type, 'front_image' => 'demo/doc.png',
                    'expiry_date' => $expiry, 'status' => $approved ? 'approved' : 'pending',
                    'verified_at' => $approved ? now() : null,
                ]);
            }

            if ($approved) {
                DriverShiftLog::create(['driver_id' => $d->id, 'went_online_at' => now()->subDays(rand(1, 5))->setTime(8, 0), 'went_offline_at' => now()->subDays(rand(1, 5))->setTime(15, 0), 'total_minutes' => 420]);
                if (fake()->boolean(40)) {
                    DriverShiftLog::create(['driver_id' => $d->id, 'went_online_at' => now()->setTime(9, 0), 'went_offline_at' => null]);
                }
            }

            $drivers[] = $d;
        }

        return $drivers;
    }

    private function seedReferrals(array $customers): void
    {
        $referrer = $customers[0];
        foreach (array_slice($customers, 1, 5) as $referee) {
            $referee->update(['referred_by' => $referrer->id]);
            Referral::create([
                'referrer_id' => $referrer->id, 'referee_id' => $referee->id,
                'referrer_bonus' => 50, 'referee_bonus' => 30,
                'status' => fake()->randomElement(['rewarded', 'pending']),
                'rewarded_at' => now()->subDays(rand(1, 20)),
            ]);
        }
    }

    private function seedOrders(array $customers, array $drivers): void
    {
        $rideService = Service::where('type', 'ride')->first();
        $parcelService = Service::where('type', 'parcel')->first();
        $cats = VehicleCategory::pluck('id')->all();
        $approved = collect($drivers)->where('status', 'approved')->values();
        if ($approved->isEmpty()) {
            return;
        }

        $n = (int) Order::max('id') + 1000;

        for ($i = 0; $i < self::ORDERS; $i++) {
            $cust = fake()->randomElement($customers);
            $drv = $approved->random();
            [$pName, $pLat, $pLng] = $this->points[$i % count($this->points)];
            [$dName, $dLat, $dLng] = $this->points[($i + 3) % count($this->points)];
            $isParcel = fake()->boolean(35);
            $status = fake()->randomElement(['completed', 'completed', 'completed', 'completed', 'cancelled', 'pending', 'start_ride', 'scheduled']);
            $orderNo = 'RR-' . now()->year . '-B' . str_pad((string) (++$n), 5, '0', STR_PAD_LEFT);

            if ($isParcel) {
                $delivery = fake()->numberBetween(60, 150);
                $cod = fake()->boolean(70) ? fake()->numberBetween(200, 2000) : 0;
                $order = Order::create([
                    'order_number' => $orderNo, 'user_id' => $cust->id,
                    'driver_id' => $status === 'pending' ? null : $drv->id,
                    'service_id' => $parcelService?->id, 'type' => 'parcel', 'status' => $status,
                    'pickup_address' => $pName . ', Dhaka', 'pickup_lat' => $pLat, 'pickup_lng' => $pLng,
                    'drop_address' => $dName . ', Dhaka', 'drop_lat' => $dLat, 'drop_lng' => $dLng,
                    'sender_name' => $cust->name, 'sender_phone' => $cust->phone,
                    'receiver_name' => fake()->name(), 'receiver_phone' => '019' . fake()->numerify('########'),
                    'parcel_type' => fake()->randomElement(['normal', 'fragile', 'document']),
                    'parcel_weight' => fake()->randomFloat(1, 0.5, 5), 'parcel_size' => fake()->randomElement(['small', 'medium', 'large']),
                    'is_cod' => $cod > 0, 'cod_amount' => $cod, 'payment_timing' => 'before',
                    'distance_km' => fake()->randomFloat(1, 1, 12), 'delivery_charge' => $delivery,
                    'total_amount' => $delivery, 'admin_commission' => round($delivery * 0.2, 2),
                    'driver_earning' => round($delivery * 0.8, 2), 'payment_method' => 'cash',
                    'payment_status' => $status === 'completed' ? 'paid' : 'pending',
                    'completed_at' => $status === 'completed' ? now()->subDays(rand(0, 20)) : null,
                ]);

                // COD payout → sender's wallet (so customers have withdrawable balance).
                if ($status === 'completed' && $cod > 0) {
                    $before = (float) $cust->wallet_balance;
                    $cust->increment('wallet_balance', $cod);
                    WalletTransaction::create([
                        'owner_type' => 'user', 'owner_id' => $cust->id, 'order_id' => $order->id,
                        'type' => 'credit', 'category' => 'cod_payout', 'amount' => $cod,
                        'balance_before' => $before, 'balance_after' => $before + $cod, 'note' => 'COD payout ' . $orderNo,
                    ]);
                }
            } else {
                $total = fake()->numberBetween(60, 350);
                $admin = round($total * 0.15, 2);
                $order = Order::create([
                    'order_number' => $orderNo, 'user_id' => $cust->id,
                    'driver_id' => $status === 'pending' ? null : $drv->id,
                    'service_id' => $rideService?->id, 'vehicle_category_id' => $cats ? fake()->randomElement($cats) : null,
                    'type' => 'ride', 'status' => $status,
                    'pickup_address' => $pName . ', Dhaka', 'pickup_lat' => $pLat, 'pickup_lng' => $pLng,
                    'drop_address' => $dName . ', Dhaka', 'drop_lat' => $dLat, 'drop_lng' => $dLng,
                    'distance_km' => fake()->randomFloat(1, 1, 12), 'duration_minutes' => fake()->numberBetween(8, 40),
                    'base_fare' => 20, 'distance_charge' => max(0, $total - 35), 'time_charge' => 15,
                    'surge_multiplier' => 1, 'surge_amount' => 0, 'total_amount' => $total,
                    'admin_commission' => $admin, 'driver_earning' => $total - $admin,
                    'tip_amount' => fake()->boolean(25) ? fake()->randomElement([10, 20, 50]) : 0,
                    'payment_method' => fake()->randomElement(['cash', 'wallet', 'online']),
                    'payment_status' => $status === 'completed' ? 'paid' : 'pending',
                    'otp' => in_array($status, ['pending', 'start_ride', 'scheduled']) ? (string) fake()->numberBetween(1000, 9999) : null,
                    'scheduled_at' => $status === 'scheduled' ? now()->addDays(rand(1, 5))->setTime(rand(7, 20), 0) : null,
                    'started_at' => $status === 'start_ride' ? now()->subMinutes(rand(3, 20)) : null,
                    'completed_at' => $status === 'completed' ? now()->subDays(rand(0, 20)) : null,
                    'cancelled_by' => $status === 'cancelled' ? fake()->randomElement(['user', 'driver']) : null,
                    'cancellation_reason' => $status === 'cancelled' ? fake()->randomElement(['Driver was too far', 'Changed my mind', 'Found another ride']) : null,
                    'cancelled_at' => $status === 'cancelled' ? now()->subDays(rand(0, 10)) : null,
                    'accepted_at' => in_array($status, ['completed', 'start_ride', 'cancelled']) ? now()->subDays(rand(0, 20)) : null,
                ]);

                if ($status === 'completed') {
                    WalletTransaction::create([
                        'owner_type' => 'driver', 'owner_id' => $drv->id, 'order_id' => $order->id,
                        'type' => 'credit', 'category' => 'trip_earning', 'amount' => $order->driver_earning,
                        'balance_before' => 0, 'balance_after' => $order->driver_earning, 'note' => 'Trip ' . $orderNo,
                    ]);
                }
            }

            // Ratings on most completed orders.
            if ($status === 'completed' && fake()->boolean(70)) {
                Rating::create([
                    'order_id' => $order->id, 'rated_by' => 'user', 'rater_id' => $cust->id,
                    'ratee_id' => $order->driver_id, 'ratee_type' => 'driver',
                    'rating' => fake()->numberBetween(3, 5), 'comment' => fake()->randomElement(['Great ride', 'Polite driver', 'On time', 'Good service', null]),
                ]);
            }

            // A few disputes on cancelled orders.
            if ($status === 'cancelled' && fake()->boolean(30)) {
                Dispute::create([
                    'order_id' => $order->id, 'raised_by' => 'user', 'raised_by_id' => $cust->id,
                    'category' => fake()->randomElement(['driver_behavior', 'fare_issue', 'other']),
                    'description' => fake()->sentence(), 'status' => fake()->randomElement(['open', 'under_review', 'resolved']),
                ]);
            }
        }

        // Some notifications.
        foreach (array_slice($customers, 0, 6) as $c) {
            Notification::create(['notifiable_type' => 'user', 'notifiable_id' => $c->id, 'title' => 'Special offer', 'body' => 'Get 20% off your next ride!', 'type' => 'promo']);
        }
    }

    private function seedWithdrawals(array $customers, array $drivers): void
    {
        $methods = WithdrawalMethod::active()->pluck('code')->all() ?: ['bkash', 'nagad', 'bank'];
        $approvedDrivers = collect($drivers)->where('status', 'approved')->where('wallet_balance', '>', 200)->values();

        // Driver withdrawals.
        foreach ($approvedDrivers->take(5) as $d) {
            WithdrawalRequest::create([
                'driver_id' => $d->id, 'amount' => fake()->randomElement([200, 300, 500]),
                'method' => fake()->randomElement($methods), 'account_details' => ['account' => $d->phone],
                'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
                'processed_at' => fake()->boolean(50) ? now()->subDays(rand(1, 10)) : null,
            ]);
        }

        // Customer withdrawals (COD-funded wallets).
        $fundedCustomers = collect($customers)->where('wallet_balance', '>', 200)->values();
        foreach ($fundedCustomers->take(6) as $c) {
            WithdrawalRequest::create([
                'user_id' => $c->id, 'amount' => fake()->randomElement([100, 200, 500]),
                'method' => fake()->randomElement($methods),
                'account_details' => ['account' => $c->withdrawal_account ?? $c->phone],
                'status' => fake()->randomElement(['pending', 'pending', 'approved']),
                'processed_at' => null,
            ]);
        }
    }

    private function seedSos(array $customers, array $drivers): void
    {
        $approved = collect($drivers)->where('status', 'approved')->values();
        SosAlert::create([
            'triggered_by' => 'user', 'triggered_by_id' => fake()->randomElement($customers)->id,
            'lat' => 23.8069, 'lng' => 90.3687, 'status' => 'active',
        ]);
        if ($approved->isNotEmpty()) {
            SosAlert::create([
                'triggered_by' => 'driver', 'triggered_by_id' => $approved->random()->id,
                'lat' => 23.7806, 'lng' => 90.4152, 'status' => 'resolved',
                'acknowledged_at' => now()->subDay(), 'resolved_at' => now()->subDay()->addMinutes(15), 'note' => 'Resolved over call.',
            ]);
        }
    }
}
