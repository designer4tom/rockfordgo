<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Dispute;
use App\Models\Driver;
use App\Models\DriverDocument;
use App\Models\DriverShiftLog;
use App\Models\DriverVehicle;
use App\Models\Notification;
use App\Models\Order;
use App\Models\ParcelPricing;
use App\Models\Rating;
use App\Models\Referral;
use App\Models\Service;
use App\Models\SosAlert;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\VehicleCategory;
use App\Models\WalletTransaction;
use App\Models\Zone;
use App\Models\WithdrawalRequest;
use Illuminate\Database\Seeder;

/**
 * Populates the app with realistic demo data for testing the admin panel and
 * the mobile APIs. Safe to run once on a fresh DB; guarded against re-seeding.
 */
class DemoDataSeeder extends Seeder
{
    // Dhaka coordinates for believable map data.
    private array $points = [
        ['Mirpur 10', 23.8069, 90.3687],
        ['Dhanmondi 27', 23.7946, 90.3713],
        ['Gulshan 1', 23.7806, 90.4152],
        ['Banani', 23.7937, 90.4066],
        ['Uttara Sector 7', 23.8759, 90.3795],
        ['Motijheel', 23.7330, 90.4172],
    ];

    public function run(): void
    {
        if (User::where('phone', '01700000001')->exists()) {
            $this->command?->warn('Demo data already seeded — skipping.');
            return;
        }

        $this->seedSettings();
        $this->seedZones();
        $this->seedParcelPricing();
        $this->seedCoupons();

        $customers = $this->seedCustomers();
        $drivers = $this->seedDrivers();
        $this->seedReferrals($customers);
        $this->seedOrders($customers, $drivers);
        $this->seedWithdrawals($drivers);
        $this->seedSos($customers, $drivers);

        $this->command?->info('Demo data seeded ✓');
    }

    private function seedSettings(): void
    {
        $settings = [
            ['currency', 'BDT', 'general'], ['currency_symbol', '৳', 'general'], ['app_name', 'ReadyRide', 'general'],
            ['ride_admin_commission_percent', '15', 'commission'], ['parcel_admin_commission_percent', '20', 'commission'],
            ['due_limit_amount', '500', 'driver'], ['withdrawal_minimum_amount', '100', 'driver'],
            ['search_radius_km', '5', 'map'], ['request_timeout_seconds', '30', 'booking'],
            ['cod_enabled', 'true', 'parcel'], ['proof_of_delivery_enabled', 'true', 'parcel'], ['proof_type', 'otp', 'parcel'],
            ['parcel_payment_timing', 'both', 'parcel'],
            ['cancellation_fee_enabled', 'true', 'cancellation'], ['cancellation_grace_minutes', '5', 'cancellation'], ['cancellation_fee_amount', '30', 'cancellation'],
            ['tip_enabled', 'true', 'tip'], ['tip_amounts', '10,20,50,100', 'tip'],
            ['surge_enabled', 'true', 'surge'],
            ['referral_enabled', 'true', 'referral'], ['referral_referrer_bonus', '50', 'referral'], ['referral_referee_bonus', '30', 'referral'],
            ['pay_online_enabled', 'true', 'payment'], ['pay_wallet_enabled', 'true', 'payment'], ['pay_cash_enabled', 'true', 'payment'],
            ['wallet_topup_enabled', 'true', 'payment'], ['wallet_topup_min', '50', 'payment'], ['wallet_topup_max', '10000', 'payment'],
            ['scheduled_booking_enabled', 'true', 'booking'], ['max_schedule_days', '7', 'booking'], ['driver_assign_before_minutes', '15', 'booking'],
        ];
        foreach ($settings as [$k, $v, $g]) {
            SystemSetting::updateOrCreate(['key' => $k], ['value' => $v, 'group' => $g]);
        }
    }

    private function seedZones(): void
    {
        $zones = [
            ['Dhaka North', 23.8103, 90.4125, 12],
            ['Dhaka South', 23.7104, 90.4074, 10],
            ['Uttara', 23.8759, 90.3795, 8],
        ];
        foreach ($zones as [$name, $lat, $lng, $r]) {
            Zone::updateOrCreate(['name' => $name], [
                'polygon' => [[$lat - 0.05, $lng - 0.05], [$lat + 0.05, $lng - 0.05], [$lat + 0.05, $lng + 0.05], [$lat - 0.05, $lng + 0.05]],
                'center_lat' => $lat, 'center_lng' => $lng, 'radius_km' => $r, 'is_active' => true,
            ]);
        }
    }

    private function seedParcelPricing(): void
    {
        $rows = [
            ['Light (0-2kg)', 'normal', 0, 2, 50, 10],
            ['Medium (2-5kg)', 'normal', 2, 5, 80, 12],
            ['Fragile (0-5kg)', 'fragile', 0, 5, 100, 15],
            ['Document', 'document', 0, 1, 40, 8],
        ];
        foreach ($rows as [$name, $type, $min, $max, $base, $perKm]) {
            ParcelPricing::updateOrCreate(['name' => $name], [
                'parcel_type' => $type, 'min_weight' => $min, 'max_weight' => $max,
                'base_charge' => $base, 'per_km_charge' => $perKm, 'is_active' => true,
            ]);
        }
    }

    private function seedCoupons(): void
    {
        $coupons = [
            ['SAVE20', '20% off rides', 'percentage', 20, 50, 100, 'ride'],
            ['FLAT50', '৳50 off any order', 'fixed', 50, null, 200, 'all'],
            ['PARCEL15', '15% off parcels', 'percentage', 15, 40, 100, 'parcel'],
        ];
        foreach ($coupons as [$code, $desc, $type, $val, $max, $min, $service]) {
            Coupon::updateOrCreate(['code' => $code], [
                'description' => $desc, 'discount_type' => $type, 'discount_value' => $val,
                'max_discount' => $max, 'min_order_amount' => $min, 'usage_limit' => 1000,
                'used_count' => 0, 'per_user_limit' => 3, 'valid_from' => now()->subMonth(),
                'valid_until' => now()->addMonths(3), 'service_type' => $service, 'is_active' => true,
            ]);
        }
    }

    private function seedCustomers(): array
    {
        $names = ['Rahim Uddin', 'Karima Begum', 'Sabbir Ahmed', 'Nadia Islam', 'Tanvir Hasan', 'Fatima Khatun'];
        $customers = [];
        foreach ($names as $i => $name) {
            $balance = [500, 0, 1200, 250, 0, 800][$i];
            $customer = User::create([
                'name' => $name,
                'phone' => '0170000000' . ($i + 1),
                'email' => 'customer' . ($i + 1) . '@readyride.test',
                'wallet_balance' => $balance,
                'referral_code' => strtoupper(substr(str_replace(' ', '', $name), 0, 4)) . (1000 + $i),
                'is_active' => $i !== 5, // last one blocked
            ]);

            // A top-up transaction so the wallet history isn't empty.
            if ($balance > 0) {
                WalletTransaction::create([
                    'owner_type' => 'user', 'owner_id' => $customer->id, 'type' => 'credit',
                    'category' => 'top_up', 'amount' => $balance, 'balance_before' => 0,
                    'balance_after' => $balance, 'note' => 'Wallet top-up',
                ]);
            }
            $customers[] = $customer;
        }
        return $customers;
    }

    private function seedDrivers(): array
    {
        $zone = Zone::first();
        $bike = VehicleCategory::where('name', 'Bike')->first();
        $car = VehicleCategory::where('name', 'Car')->first();

        $defs = [
            ['Jamal Mia', 'approved', true, 'Bike', 4.8, 800, 0],
            ['Sohel Rana', 'approved', true, 'Car', 4.6, 1500, 120],
            ['Abdul Karim', 'approved', false, 'Bike', 4.9, 300, 0],
            ['Rafiq Sheikh', 'pending', false, 'Car', 0, 0, 0],
            ['Belal Hossain', 'approved', true, 'Car', 3.2, 600, 550], // over due limit
        ];

        $drivers = [];
        foreach ($defs as $i => [$name, $status, $online, $catName, $rating, $wallet, $due]) {
            $cat = $catName === 'Bike' ? $bike : $car;
            [$pName, $lat, $lng] = $this->points[$i % count($this->points)];

            $driver = Driver::create([
                'name' => $name,
                'phone' => '0180000000' . ($i + 1),
                'email' => 'driver' . ($i + 1) . '@readyride.test',
                'status' => $status,
                'is_online' => $online,
                'is_active' => true,
                'wallet_balance' => $wallet,
                'due_amount' => $due,
                'can_accept_orders' => $due < 500,
                'average_rating' => $rating,
                'total_trips' => [120, 340, 56, 0, 88][$i],
                'acceptance_rate' => [92, 88, 95, 0, 70][$i],
                'completion_rate' => [98, 96, 99, 0, 85][$i],
                'cancellation_rate' => [2, 4, 1, 0, 15][$i],
                'current_lat' => $lat, 'current_lng' => $lng, 'last_location_at' => now(),
                'zone_id' => $zone?->id,
            ]);

            if ($cat) {
                DriverVehicle::create([
                    'driver_id' => $driver->id, 'vehicle_category_id' => $cat->id,
                    'make' => $catName === 'Bike' ? 'Honda' : 'Toyota',
                    'model' => $catName === 'Bike' ? 'CB150R' : 'Axio',
                    'year' => '2021', 'color' => ['Red', 'White', 'Black', 'Blue', 'Silver'][$i],
                    'registration_number' => 'DHA-' . (1000 + $i), 'front_photo' => 'demo/vehicle.png',
                    'back_photo' => 'demo/vehicle.png', 'is_active' => true,
                ]);
            }

            // Documents
            foreach (['nid' => null, 'driving_license' => now()->addYear(), 'vehicle_registration' => now()->addMonths(8)] as $type => $expiry) {
                DriverDocument::create([
                    'driver_id' => $driver->id, 'type' => $type, 'front_image' => 'demo/doc.png',
                    'expiry_date' => $expiry, 'status' => $status === 'approved' ? 'approved' : 'pending',
                    'verified_at' => $status === 'approved' ? now() : null,
                ]);
            }

            // A couple of shift logs for approved drivers
            if ($status === 'approved') {
                DriverShiftLog::create(['driver_id' => $driver->id, 'went_online_at' => now()->subDays(1)->setTime(8, 0), 'went_offline_at' => now()->subDays(1)->setTime(14, 30), 'total_minutes' => 390]);
                DriverShiftLog::create(['driver_id' => $driver->id, 'went_online_at' => now()->setTime(9, 0), 'went_offline_at' => null]);
            }

            $drivers[] = $driver;
        }
        return $drivers;
    }

    private function seedReferrals(array $customers): void
    {
        // customer[0] referred customer[2] and customer[4]
        foreach ([2, 4] as $idx) {
            $customers[$idx]->update(['referred_by' => $customers[0]->id]);
            Referral::create([
                'referrer_id' => $customers[0]->id, 'referee_id' => $customers[$idx]->id,
                'referrer_bonus' => 50, 'referee_bonus' => 30, 'status' => 'rewarded', 'rewarded_at' => now(),
            ]);
        }
    }

    private function seedOrders(array $customers, array $drivers): void
    {
        $rideService = Service::where('type', 'ride')->first();
        $parcelService = Service::where('type', 'parcel')->first();
        $bike = VehicleCategory::where('name', 'Bike')->first();
        $approved = collect($drivers)->where('status', 'approved')->values();

        $n = 0;
        // 12 completed rides
        for ($i = 0; $i < 12; $i++) {
            $cust = $customers[$i % count($customers)];
            $drv = $approved[$i % $approved->count()];
            [$pName, $pLat, $pLng] = $this->points[$i % count($this->points)];
            [$dName, $dLat, $dLng] = $this->points[($i + 2) % count($this->points)];
            $total = [80, 120, 65, 210, 95, 140, 75, 180, 110, 90, 160, 130][$i];
            $admin = round($total * 0.15, 2);

            $order = Order::create([
                'order_number' => 'RR-' . now()->year . '-' . str_pad((string) (++$n), 5, '0', STR_PAD_LEFT),
                'user_id' => $cust->id, 'driver_id' => $drv->id, 'service_id' => $rideService->id,
                'vehicle_category_id' => $bike?->id, 'type' => 'ride', 'status' => 'completed',
                'pickup_address' => $pName . ', Dhaka', 'pickup_lat' => $pLat, 'pickup_lng' => $pLng,
                'drop_address' => $dName . ', Dhaka', 'drop_lat' => $dLat, 'drop_lng' => $dLng,
                'distance_km' => rand(20, 80) / 10, 'duration_minutes' => rand(8, 30),
                'base_fare' => 20, 'distance_charge' => $total - 35, 'time_charge' => 15,
                'surge_multiplier' => 1, 'surge_amount' => 0, 'total_amount' => $total,
                'admin_commission' => $admin, 'driver_earning' => $total - $admin, 'tip_amount' => $i % 4 === 0 ? 20 : 0,
                'payment_method' => ['cash', 'wallet', 'online'][$i % 3], 'payment_status' => 'paid',
                'completed_at' => now()->subDays(rand(0, 14))->subHours(rand(0, 12)),
                'accepted_at' => now()->subDays(rand(0, 14)),
            ]);

            // Driver earning transaction
            WalletTransaction::create([
                'owner_type' => 'driver', 'owner_id' => $drv->id, 'order_id' => $order->id,
                'type' => 'credit', 'category' => 'trip_earning', 'amount' => $order->driver_earning,
                'balance_before' => 0, 'balance_after' => $order->driver_earning, 'note' => 'Trip ' . $order->order_number,
            ]);

            // Customer rating for driver
            if ($i % 2 === 0) {
                Rating::create([
                    'order_id' => $order->id, 'rated_by' => 'user', 'rater_id' => $cust->id,
                    'ratee_id' => $drv->id, 'ratee_type' => 'driver', 'rating' => rand(4, 5), 'comment' => 'Good ride',
                ]);
            }
        }

        // 4 completed COD parcels
        for ($i = 0; $i < 4; $i++) {
            $cust = $customers[$i % count($customers)];
            $drv = $approved[$i % $approved->count()];
            [$pName, $pLat, $pLng] = $this->points[$i % count($this->points)];
            [$dName, $dLat, $dLng] = $this->points[($i + 3) % count($this->points)];
            $delivery = [80, 95, 110, 70][$i];
            $cod = [500, 1200, 300, 750][$i];

            Order::create([
                'order_number' => 'RR-' . now()->year . '-' . str_pad((string) (++$n), 5, '0', STR_PAD_LEFT),
                'user_id' => $cust->id, 'driver_id' => $drv->id, 'service_id' => $parcelService->id,
                'type' => 'parcel', 'status' => 'completed',
                'pickup_address' => $pName . ', Dhaka', 'pickup_lat' => $pLat, 'pickup_lng' => $pLng,
                'drop_address' => $dName . ', Dhaka', 'drop_lat' => $dLat, 'drop_lng' => $dLng,
                'sender_name' => $cust->name, 'sender_phone' => $cust->phone,
                'receiver_name' => 'Receiver ' . ($i + 1), 'receiver_phone' => '0191000000' . $i,
                'parcel_type' => 'normal', 'parcel_weight' => rand(5, 30) / 10, 'parcel_size' => 'small',
                'is_cod' => true, 'cod_amount' => $cod, 'payment_timing' => 'before',
                'distance_km' => rand(20, 60) / 10, 'delivery_charge' => $delivery,
                'total_amount' => $delivery, 'admin_commission' => round($delivery * 0.2, 2),
                'driver_earning' => round($delivery * 0.8, 2), 'payment_method' => 'cash', 'payment_status' => 'paid',
                'proof_type' => 'otp', 'proof_collected_at' => now()->subDays(rand(0, 7)),
                'completed_at' => now()->subDays(rand(0, 7)),
            ]);
        }

        // Ongoing ride
        Order::create([
            'order_number' => 'RR-' . now()->year . '-' . str_pad((string) (++$n), 5, '0', STR_PAD_LEFT),
            'user_id' => $customers[0]->id, 'driver_id' => $approved[0]->id, 'service_id' => $rideService->id,
            'vehicle_category_id' => $bike?->id, 'type' => 'ride', 'status' => 'start_ride',
            'pickup_address' => 'Mirpur 10, Dhaka', 'pickup_lat' => 23.8069, 'pickup_lng' => 90.3687,
            'drop_address' => 'Gulshan 1, Dhaka', 'drop_lat' => 23.7806, 'drop_lng' => 90.4152,
            'distance_km' => 6.2, 'duration_minutes' => 22, 'base_fare' => 20, 'distance_charge' => 95, 'time_charge' => 22,
            'surge_multiplier' => 1, 'total_amount' => 137, 'admin_commission' => 20.55, 'driver_earning' => 116.45,
            'otp' => '4521', 'payment_method' => 'cash', 'payment_status' => 'pending',
            'accepted_at' => now()->subMinutes(15), 'started_at' => now()->subMinutes(5),
        ]);

        // Pending (searching) ride
        Order::create([
            'order_number' => 'RR-' . now()->year . '-' . str_pad((string) (++$n), 5, '0', STR_PAD_LEFT),
            'user_id' => $customers[2]->id, 'service_id' => $rideService->id, 'vehicle_category_id' => $bike?->id,
            'type' => 'ride', 'status' => 'pending', 'pickup_address' => 'Banani, Dhaka', 'pickup_lat' => 23.7937, 'pickup_lng' => 90.4066,
            'drop_address' => 'Motijheel, Dhaka', 'drop_lat' => 23.7330, 'drop_lng' => 90.4172,
            'distance_km' => 8.5, 'duration_minutes' => 28, 'base_fare' => 20, 'distance_charge' => 130, 'time_charge' => 28,
            'total_amount' => 178, 'admin_commission' => 26.7, 'driver_earning' => 151.3, 'otp' => '8830',
            'payment_method' => 'wallet', 'payment_status' => 'pending',
        ]);

        // Scheduled ride
        Order::create([
            'order_number' => 'RR-' . now()->year . '-' . str_pad((string) (++$n), 5, '0', STR_PAD_LEFT),
            'user_id' => $customers[3]->id, 'service_id' => $rideService->id, 'vehicle_category_id' => $bike?->id,
            'type' => 'ride', 'status' => 'scheduled', 'pickup_address' => 'Uttara Sector 7', 'pickup_lat' => 23.8759, 'pickup_lng' => 90.3795,
            'drop_address' => 'Airport', 'drop_lat' => 23.8513, 'drop_lng' => 90.4086,
            'distance_km' => 5, 'duration_minutes' => 18, 'base_fare' => 20, 'distance_charge' => 90, 'time_charge' => 18,
            'total_amount' => 128, 'admin_commission' => 19.2, 'driver_earning' => 108.8, 'otp' => '1199',
            'payment_method' => 'cash', 'payment_status' => 'pending', 'scheduled_at' => now()->addDay()->setTime(9, 0),
        ]);

        // Cancelled ride + dispute
        $cancelled = Order::create([
            'order_number' => 'RR-' . now()->year . '-' . str_pad((string) (++$n), 5, '0', STR_PAD_LEFT),
            'user_id' => $customers[1]->id, 'driver_id' => $approved[1]->id, 'service_id' => $rideService->id,
            'vehicle_category_id' => $bike?->id, 'type' => 'ride', 'status' => 'cancelled',
            'pickup_address' => 'Dhanmondi 27', 'pickup_lat' => 23.7946, 'pickup_lng' => 90.3713,
            'drop_address' => 'Banani', 'drop_lat' => 23.7937, 'drop_lng' => 90.4066,
            'distance_km' => 4, 'duration_minutes' => 14, 'base_fare' => 20, 'distance_charge' => 60, 'time_charge' => 14,
            'total_amount' => 94, 'admin_commission' => 14.1, 'driver_earning' => 79.9,
            'payment_method' => 'cash', 'payment_status' => 'pending', 'cancelled_by' => 'user',
            'cancellation_reason' => 'Driver was too far', 'cancelled_at' => now()->subDays(2),
        ]);

        Dispute::create([
            'order_id' => $cancelled->id, 'raised_by' => 'user', 'raised_by_id' => $customers[1]->id,
            'category' => 'driver_behavior', 'description' => 'Driver cancelled after long wait.', 'status' => 'open',
        ]);

        // Notifications
        foreach ([$customers[0], $customers[2]] as $c) {
            Notification::create(['notifiable_type' => 'user', 'notifiable_id' => $c->id, 'title' => 'Welcome to ReadyRide', 'body' => 'Book your first ride and get a discount!', 'type' => 'promo']);
        }
        foreach ($approved as $d) {
            Notification::create(['notifiable_type' => 'driver', 'notifiable_id' => $d->id, 'title' => 'Account approved', 'body' => 'You can now go online and accept trips.', 'type' => 'order_update', 'read_at' => now()]);
        }
    }

    private function seedWithdrawals(array $drivers): void
    {
        $approved = collect($drivers)->where('status', 'approved')->values();
        WithdrawalRequest::create([
            'driver_id' => $approved[1]->id, 'amount' => 500, 'method' => 'bkash',
            'account_details' => ['account' => '01800000002'], 'status' => 'pending',
        ]);
        WithdrawalRequest::create([
            'driver_id' => $approved[0]->id, 'amount' => 300, 'method' => 'nagad',
            'account_details' => ['account' => '01800000001'], 'status' => 'approved',
            'processed_at' => now()->subDays(3),
        ]);
    }

    private function seedSos(array $customers, array $drivers): void
    {
        $approved = collect($drivers)->where('status', 'approved')->values();
        SosAlert::create([
            'triggered_by' => 'user', 'triggered_by_id' => $customers[0]->id,
            'lat' => 23.8069, 'lng' => 90.3687, 'status' => 'active',
        ]);
        SosAlert::create([
            'triggered_by' => 'driver', 'triggered_by_id' => $approved[0]->id,
            'lat' => 23.7806, 'lng' => 90.4152, 'status' => 'resolved',
            'acknowledged_at' => now()->subDay(), 'resolved_at' => now()->subDay()->addMinutes(20),
            'note' => 'False alarm, resolved over call.',
        ]);
    }
}
