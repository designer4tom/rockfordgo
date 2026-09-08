# ReadyRide — Claude Code Master Instruction
# পর্যায় ১: Project Foundation

---

## তুমি কী বানাবে
ReadyRide নামের একটি Ride + Parcel Delivery platform-এর **Admin Panel Backend**।
- Tech: **Laravel (PHP)**, Blade template, Tailwind CSS
- Database: **MySQL**
- Real-time: **Pusher**
- Push Notification: **FCM**
- Payment: **Stripe**
- Map: **Google Maps API**
- Cache/Queue: **Redis**

---

## এই পর্যায়ে শুধু যা করবে (পর্যায় ১)

1. Laravel project setup
2. সব Database Migrations (নিচে schema দেওয়া আছে)
3. Seeders (Super Admin, Default System Settings)
4. Base Layout (Blade + Tailwind — Sidebar, Navbar)
5. Admin Auth (Login, Logout, Session)

**এর বাইরে কিছু করবে না এই পর্যায়ে।**

---

## Project Structure যেভাবে রাখবে

```
app/
  Models/          -- সব Eloquent Models
  Http/
    Controllers/
      Admin/       -- সব Admin Controllers
      Api/         -- App-এর জন্য API Controllers (পরে)
    Middleware/
  Services/        -- Business Logic (OrderService, WalletService, etc.)
  Helpers/

resources/
  views/
    layouts/
      admin.blade.php     -- Main admin layout
      auth.blade.php      -- Login layout
    admin/
      dashboard/
      drivers/
      users/
      orders/
      payments/
      settings/
    components/           -- Reusable Blade components

routes/
  web.php          -- Admin Panel routes
  api.php          -- App API routes (পরে)

database/
  migrations/
  seeders/
```

---

## Database Schema (সম্পূর্ণ)

### Table 1: admins
```sql
id (bigint, PK, auto increment)
name (varchar 255)
email (varchar 255, unique)
password (varchar 255)
role (enum: super_admin, sub_admin, fleet_manager)
is_active (boolean, default true)
two_factor_enabled (boolean, default false)
two_factor_secret (varchar, nullable)
last_login_at (timestamp, nullable)
last_login_ip (varchar, nullable)
remember_token (varchar, nullable)
created_at, updated_at (timestamps)
```

### Table 2: admin_permissions
```sql
id, admin_id (FK admins), 
module (enum: dashboard,users,drivers,orders,payments,settings,reports,sos,disputes)
can_read, can_write, can_delete (boolean, default false)
created_at, updated_at
```

### Table 3: admin_login_logs
```sql
id, admin_id (FK admins)
ip_address (varchar), user_agent (text)
status (enum: success, failed)
created_at
```

### Table 4: users (Customer App)
```sql
id, name, phone (unique), email (nullable, unique)
avatar (varchar, nullable)
wallet_balance (decimal 15,2, default 0)
referral_code (varchar, unique)
referred_by (FK users.id, nullable)
is_active (boolean, default true)
fcm_token (text, nullable)
created_at, updated_at
```

### Table 5: drivers
```sql
id, name, phone (unique), email (nullable)
avatar (varchar, nullable)
wallet_balance (decimal 15,2, default 0)
due_amount (decimal 15,2, default 0)
status (enum: pending, approved, rejected, suspended, blocked, default: pending)
is_online (boolean, default false)
current_lat (decimal 10,8, nullable)
current_lng (decimal 11,8, nullable)
last_location_at (timestamp, nullable)
average_rating (decimal 3,2, default 0)
total_trips (int, default 0)
acceptance_rate (decimal 5,2, default 0)
completion_rate (decimal 5,2, default 0)
cancellation_rate (decimal 5,2, default 0)
zone_id (FK zones.id, nullable)
fcm_token (text, nullable)
rejection_reason (text, nullable)
is_active (boolean, default true)
created_at, updated_at
```

### Table 6: driver_documents
```sql
id, driver_id (FK drivers)
type (enum: nid, driving_license, vehicle_registration, vehicle_insurance, vehicle_photo, bank_info)
front_image (varchar)
back_image (varchar, nullable)
expiry_date (date, nullable)
status (enum: pending, approved, rejected, default: pending)
rejection_reason (text, nullable)
verified_at (timestamp, nullable)
verified_by (FK admins.id, nullable)
created_at, updated_at
```

### Table 7: driver_vehicles
```sql
id, driver_id (FK drivers)
vehicle_category_id (FK vehicle_categories)
make, model, year (varchar), color
registration_number (varchar)
front_photo, back_photo (varchar)
is_active (boolean, default true)
created_at, updated_at
```

### Table 8: driver_shift_logs
```sql
id, driver_id (FK drivers)
went_online_at (timestamp)
went_offline_at (timestamp, nullable)
total_minutes (int, nullable)
created_at
```

### Table 9: services
```sql
id, name, slug (unique)
type (enum: ride, parcel)
icon (varchar, nullable)
description (text, nullable)
is_active (boolean, default true)
sort_order (int, default 0)
created_at, updated_at
```

### Table 10: vehicle_categories
```sql
id, service_id (FK services)
name, icon (nullable)
base_fare (decimal 10,2)
per_km_rate (decimal 10,2)
per_minute_rate (decimal 10,2)
minimum_fare (decimal 10,2)
capacity (tinyint)
is_active (boolean, default true)
sort_order (int, default 0)
created_at, updated_at
```

### Table 11: zones
```sql
id, name
polygon (JSON)
center_lat (decimal 10,8)
center_lng (decimal 11,8)
radius_km (decimal 8,2)
is_active (boolean, default true)
created_at, updated_at
```

### Table 12: zone_services
```sql
id, zone_id (FK zones), service_id (FK services)
operating_hours_start (time, nullable)
operating_hours_end (time, nullable)
created_at, updated_at
```

### Table 13: parcel_pricing
```sql
id, name
min_weight (decimal 8,2), max_weight (decimal 8,2)
base_charge (decimal 10,2)
per_km_charge (decimal 10,2)
parcel_type (enum: normal, fragile, document)
is_active (boolean, default true)
created_at, updated_at
```

### Table 14: orders
```sql
id, order_number (varchar unique)
user_id (FK users), driver_id (FK drivers, nullable)
service_id (FK services)
vehicle_category_id (FK vehicle_categories, nullable)
type (enum: ride, parcel)
status (enum: scheduled,pending,accepted,go_to_pickup,confirm_arrival,picked_up,start_ride,dropped_off,completed,cancelled,rejected)
-- Locations
pickup_address (text), pickup_lat (decimal 10,8), pickup_lng (decimal 11,8)
drop_address (text), drop_lat (decimal 10,8), drop_lng (decimal 11,8)
stops (JSON, nullable)
-- Ride
otp (varchar 6, nullable), otp_verified_at (timestamp, nullable)
-- Parcel
sender_name, sender_phone (varchar, nullable)
receiver_name, receiver_phone (varchar, nullable)
parcel_type (enum: normal,fragile,document, nullable)
parcel_weight (decimal 8,2, nullable)
parcel_size (enum: small,medium,large, nullable)
parcel_photo (varchar, nullable)
parcel_note (text, nullable)
is_cod (boolean, default false)
cod_amount (decimal 15,2, nullable)
payment_timing (enum: before,after, nullable)
proof_type (enum: otp,photo,signature, nullable)
proof_data (varchar, nullable)
proof_collected_at (timestamp, nullable)
-- Pricing
distance_km (decimal 8,2, nullable)
duration_minutes (int, nullable)
base_fare (decimal 10,2, nullable)
distance_charge (decimal 10,2, nullable)
time_charge (decimal 10,2, nullable)
surge_multiplier (decimal 4,2, default 1)
surge_amount (decimal 10,2, default 0)
delivery_charge (decimal 10,2, nullable)
coupon_id (FK coupons, nullable)
coupon_discount (decimal 10,2, default 0)
total_amount (decimal 15,2, nullable)
admin_commission (decimal 10,2, nullable)
driver_earning (decimal 10,2, nullable)
tip_amount (decimal 10,2, default 0)
-- Payment
payment_method (enum: cash,online,wallet,cod)
payment_status (enum: pending,paid,failed, default: pending)
payment_intent_id (varchar, nullable)
-- Schedule
scheduled_at (timestamp, nullable)
driver_assigned_at (timestamp, nullable)
-- Cancel
cancelled_by (enum: user,driver,admin, nullable)
cancellation_reason (text, nullable)
cancellation_fee (decimal 10,2, default 0)
-- Status timestamps
accepted_at, arrived_at, picked_up_at, started_at, dropped_at, completed_at, cancelled_at (timestamp, nullable)
created_at, updated_at
```

### Table 15: order_locations
```sql
id, order_id (FK orders), driver_id (FK drivers)
lat (decimal 10,8), lng (decimal 11,8)
recorded_at (timestamp)
created_at
```

### Table 16: wallet_transactions
```sql
id
owner_type (enum: user, driver)
owner_id (bigint)
order_id (FK orders, nullable)
type (enum: credit, debit)
category (enum: trip_earning,parcel_earning,commission,cod_collection,cod_payout,withdrawal,refund,referral_bonus,top_up,due_payment)
amount (decimal 15,2)
balance_before (decimal 15,2)
balance_after (decimal 15,2)
note (text, nullable)
created_at, updated_at
```

### Table 17: due_transactions
```sql
id, driver_id (FK drivers)
order_id (FK orders, nullable)
type (enum: added, paid)
amount (decimal 15,2)
due_before (decimal 15,2)
due_after (decimal 15,2)
note (text, nullable)
created_at, updated_at
```

### Table 18: withdrawal_requests
```sql
id, driver_id (FK drivers)
amount (decimal 15,2)
method (enum: bank, bkash, nagad)
account_details (JSON)
status (enum: pending, approved, rejected, default: pending)
processed_by (FK admins, nullable)
processed_at (timestamp, nullable)
rejection_reason (text, nullable)
created_at, updated_at
```

### Table 19: surge_pricing_rules
```sql
id, name
zone_id (FK zones, nullable)
vehicle_category_id (FK vehicle_categories, nullable)
day_of_week (JSON, nullable)
start_time (time), end_time (time)
multiplier (decimal 4,2)
is_active (boolean, default true)
created_at, updated_at
```

### Table 20: coupons
```sql
id, code (varchar unique), description (text, nullable)
discount_type (enum: percentage, fixed)
discount_value (decimal 10,2)
max_discount (decimal 10,2, nullable)
min_order_amount (decimal 10,2, default 0)
usage_limit (int, nullable)
used_count (int, default 0)
per_user_limit (int, default 1)
valid_from (date), valid_until (date)
service_type (enum: ride, parcel, all)
is_active (boolean, default true)
created_at, updated_at
```

### Table 21: coupon_usages
```sql
id, coupon_id (FK coupons), user_id (FK users), order_id (FK orders)
discount_amount (decimal 10,2)
created_at
```

### Table 22: referrals
```sql
id, referrer_id (FK users), referee_id (FK users)
referrer_bonus (decimal 10,2), referee_bonus (decimal 10,2)
status (enum: pending, rewarded, default: pending)
rewarded_at (timestamp, nullable)
created_at, updated_at
```

### Table 23: ratings
```sql
id, order_id (FK orders)
rated_by (enum: user, driver)
rater_id (bigint)
ratee_id (bigint)
ratee_type (enum: user, driver)
rating (tinyint)
comment (text, nullable)
tags (JSON, nullable)
created_at, updated_at
```

### Table 24: disputes
```sql
id, order_id (FK orders)
raised_by (enum: user, driver)
raised_by_id (bigint)
category (enum: driver_behavior,route_issue,overcharging,parcel_issue,payment_issue,other)
description (text)
status (enum: open, under_review, resolved, default: open)
admin_note (text, nullable)
resolved_by (FK admins, nullable)
resolved_at (timestamp, nullable)
refund_issued (boolean, default false)
refund_amount (decimal 10,2, nullable)
created_at, updated_at
```

### Table 25: sos_alerts
```sql
id, order_id (FK orders, nullable)
triggered_by (enum: user, driver)
triggered_by_id (bigint)
lat (decimal 10,8), lng (decimal 11,8)
status (enum: active, acknowledged, resolved, default: active)
acknowledged_by (FK admins, nullable)
acknowledged_at (timestamp, nullable)
resolved_at (timestamp, nullable)
note (text, nullable)
created_at, updated_at
```

### Table 26: notifications
```sql
id
notifiable_type (enum: user, driver, admin)
notifiable_id (bigint)
title (varchar), body (text)
type (enum: order_request,order_update,payment,document_expiry,due_warning,withdrawal,sos,dispute,broadcast,promo)
data (JSON, nullable)
read_at (timestamp, nullable)
created_at, updated_at
```

### Table 27: favourite_locations
```sql
id, user_id (FK users)
label (enum: home, office, other)
custom_label (varchar, nullable)
address (text), lat (decimal 10,8), lng (decimal 11,8)
created_at, updated_at
```

### Table 28: system_settings
```sql
id, key (varchar unique), value (text), group (varchar)
created_at, updated_at
```

### Table 29: landing_page_contents
```sql
id
section (enum: hero,features,how_it_works,app_download,testimonials,faq,contact)
key (varchar), value (text)
sort_order (int, default 0)
is_active (boolean, default true)
created_at, updated_at
```

### Table 30: scheduled_notifications
```sql
id, type (varchar)
target_type (varchar), target_id (bigint)
title, body (varchar)
data (JSON)
scheduled_at (timestamp)
sent_at (timestamp, nullable)
status (enum: pending, sent, failed, default: pending)
created_at
```

---

## Seeder — যা seed করবে

### AdminSeeder
```php
// Super Admin তৈরি করবে
name: "Super Admin"
email: "admin@readyride.com"
password: bcrypt("Admin@12345")
role: "super_admin"
is_active: true
```

### SystemSettingsSeeder
```php
// group: general
app_name => "ReadyRide"
currency => "BDT"
timezone => "Asia/Dhaka"
support_email => "support@readyride.com"
support_phone => "+8801700000000"

// group: driver
due_limit_amount => "500"
withdrawal_minimum_amount => "100"
request_timeout_seconds => "30"

// group: ride
ride_share_enabled => "false"
surge_enabled => "false"

// group: parcel
parcel_payment_timing => "both"   // before / after / both
cod_enabled => "true"
proof_of_delivery_enabled => "true"
proof_type => "otp"               // otp / photo / signature

// group: booking
scheduled_booking_enabled => "true"
max_schedule_days => "7"
driver_assign_before_minutes => "30"

// group: cancellation
cancellation_fee_enabled => "false"
cancellation_grace_minutes => "5"
cancellation_fee_amount => "30"

// group: referral
referral_enabled => "true"
referrer_bonus => "50"
referee_bonus => "30"

// group: tip
tip_enabled => "true"
```

### ServiceSeeder
```php
// Ride Service
name: "Ride", slug: "ride", type: "ride", is_active: true

// Parcel Service
name: "Parcel", slug: "parcel", type: "parcel", is_active: true
```

### VehicleCategorySeeder
```php
// Bike
service: ride, name: "Bike", base_fare: 20, per_km: 12, per_minute: 1, minimum: 40, capacity: 1

// Car
service: ride, name: "Car", base_fare: 40, per_km: 18, per_minute: 2, minimum: 80, capacity: 4

// Premium Car
service: ride, name: "Premium", base_fare: 80, per_km: 25, per_minute: 3, minimum: 150, capacity: 4
```

---

## Auth System — যেভাবে বানাবে

### Login
- Route: GET/POST /admin/login
- Blade: resources/views/admin/auth/login.blade.php
- Controller: App\Http\Controllers\Admin\AuthController
- Features:
  - Email + Password validation
  - Max 5 failed attempts → 15 min lockout
  - Login success → log করো admin_login_logs-এ
  - Login fail → log করো admin_login_logs-এ
  - Remember me option

### Middleware
- `AdminAuth` — login না থাকলে /admin/login-এ redirect
- `CheckPermission` — role/permission check
- `SuperAdminOnly` — শুধু super_admin access

### Session
- Auth guard আলাদা রাখবে: `admin` guard
- config/auth.php-এ আলাদা guard define করবে

---

## Base Layout — যেভাবে বানাবে

### Sidebar Menu Structure
```
Dashboard
├── Users (Customers)
├── Drivers
│   ├── All Drivers
│   └── Pending Approvals
├── Orders
│   ├── All Orders
│   └── Disputes
├── Payments
│   ├── Transactions
│   ├── Withdrawals
│   └── Refunds
├── Services & Pricing
│   ├── Services
│   ├── Vehicle Categories
│   ├── Parcel Pricing
│   └── Surge Rules
├── Zones
├── Coupons & Referral
├── Notifications
├── SOS Alerts
├── Reports
├── Settings
│   ├── General
│   ├── Payment (Stripe)
│   ├── Notification (FCM/Pusher)
│   ├── Map
│   └── Advanced
└── Landing Page (CMS)
```

### Design Requirements
- Tailwind CSS ব্যবহার করবে
- Sidebar: Dark (gray-900), collapsible
- Top Navbar: White, Admin name + avatar + logout
- Content area: Light gray background (gray-50)
- Active menu item: highlighted
- Mobile responsive
- Flash messages (success, error, warning) — top-এ দেখাবে

---

## এই পর্যায়ে যা deliver করবে

- [ ] Laravel project (fresh install)
- [ ] .env.example (সব keys সহ)
- [ ] সব 30টি Migration files
- [ ] AdminSeeder, SystemSettingsSeeder, ServiceSeeder, VehicleCategorySeeder
- [ ] DatabaseSeeder (সব seeders call করবে)
- [ ] Admin Auth Guard (config/auth.php)
- [ ] AdminAuth Middleware
- [ ] AuthController (login, logout)
- [ ] Login Blade page (Tailwind, responsive)
- [ ] Admin Master Layout (Blade + Tailwind)
- [ ] Sidebar Component
- [ ] Navbar Component
- [ ] Dashboard placeholder page (just layout, no data yet)
- [ ] Routes (web.php — admin group)

---

## গুরুত্বপূর্ণ নিয়ম

1. **সব code বাংলা comment সহ লিখবে না** — English comment ব্যবহার করবে
2. **Migration order** ঠিক রাখবে — Foreign Key dependency অনুযায়ী
3. **Model relationship** সব define করবে (hasMany, belongsTo, morphTo, etc.)
4. **Soft Delete** ব্যবহার করবে না — is_active flag ব্যবহার করবে
5. **created_at/updated_at** সব table-এ থাকবে
6. **Decimal precision** schema অনুযায়ী রাখবে (15,2 for money)
7. **Enum values** migration-এ exactly schema অনুযায়ী রাখবে
8. এই পর্যায়ে **API routes বানাবে না** — শুধু web.php admin routes

---

## শুরু করো এই order-এ

1. `laravel new readyride --git`
2. প্রয়োজনীয় packages install: `laravel/ui`, `spatie/laravel-permission` (না, নিজেই permission বানাবে — admin_permissions table আছে)
3. Migration files তৈরি (dependency order মেনে)
4. Models তৈরি (Relationships সহ)
5. Seeders তৈরি
6. Auth config + Middleware
7. Controllers
8. Blade layouts + components
9. Routes
