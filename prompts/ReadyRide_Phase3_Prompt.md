# ReadyRide — Claude Code Master Instruction
# পর্যায় ৩: Service Management + Zone Management + Pricing

---

## Context
পর্যায় ১ ও ২ শেষ — Project setup, Migrations, Auth, Dashboard ready।
এই পর্যায়ে Service, Vehicle Category, Parcel Pricing, Zone, Surge Pricing বানাবো।
এগুলো আগে করতে হবে কারণ Orders, Drivers সব এগুলোর উপর depend করে।

---

## এই পর্যায়ে যা করবে

1. Service Management (Ride / Parcel)
2. Vehicle Category Management
3. Parcel Pricing Configuration
4. Zone Management (Map-based)
5. Surge Pricing Rules
6. General Pricing Settings

---

## ১. Service Management

### ১.১ Service List
```
Route: GET /admin/services
Permission: settings, read

Table columns:
- Icon (ছবি thumbnail)
- Name
- Type (Ride / Parcel — colored badge)
- Sort Order
- Status (Active/Inactive — toggle switch)
- Actions: Edit, Delete

Drag to reorder (sort_order update) — optional
"New Service" button top-right
```

### ১.২ Create / Edit Service
```
Route: GET  /admin/services/create
Route: POST /admin/services
Route: GET  /admin/services/{id}/edit
Route: PUT  /admin/services/{id}
Permission: settings, write

Form fields:
- Name (required)
- Slug (auto-generate from name, editable)
- Type: ride / parcel (radio button — edit-এ disabled)
- Icon: image upload (max 2MB, jpg/png/svg)
- Description (textarea, optional)
- Sort Order (number)
- Status: Active / Inactive

Validation:
- name: required, max 100
- slug: required, unique (except self on edit), regex: /^[a-z0-9-]+$/
- type: required, in: ride, parcel
- icon: nullable, image, max 2048
```

### ১.৩ Delete Service
```
Route: DELETE /admin/services/{id}
Permission: settings, delete

Check: যদি এই service-এ active orders থাকে → delete করতে দেবে না
Error: "এই Service-এ active orders আছে, delete করা যাবে না"
```

### ১.৪ Toggle Status
```
Route: POST /admin/services/{id}/toggle-status
Inactive করলে new orders আসবে না এই service-এ
```

---

## ২. Vehicle Category Management

### ২.১ Vehicle Category List
```
Route: GET /admin/vehicle-categories
Permission: settings, read

Filter: Service (dropdown — শুধু ride type services)

Table columns:
- Icon
- Name
- Service Name
- Base Fare (৳)
- Per KM (৳)
- Per Minute (৳)
- Minimum Fare (৳)
- Capacity
- Sort Order
- Status toggle
- Actions: Edit, Delete
```

### ২.২ Create / Edit Vehicle Category
```
Route: GET  /admin/vehicle-categories/create
Route: POST /admin/vehicle-categories
Route: GET  /admin/vehicle-categories/{id}/edit
Route: PUT  /admin/vehicle-categories/{id}
Permission: settings, write

Form fields:
- Service (dropdown — ride services only, required)
- Name (required, e.g.: Bike, Car, Premium)
- Icon: image upload
- Base Fare (decimal, required, min 0)
- Per KM Rate (decimal, required, min 0)
- Per Minute Rate (decimal, required, min 0)
- Minimum Fare (decimal, required, min 0)
- Capacity (integer, required, min 1, max 20)
- Sort Order (integer)
- Status: Active / Inactive

Fare Preview Box (live calculate):
- একটা example trip দেখাবে (e.g., 5 KM, 15 min)
- Calculated fare: Base + (5 × per_km) + (15 × per_min)
- JavaScript দিয়ে live update হবে form-এর values change হলে
```

### ২.৩ Delete Vehicle Category
```
Route: DELETE /admin/vehicle-categories/{id}
Check: active orders আছে কিনা — থাকলে delete করতে দেবে না
```

---

## ৩. Parcel Pricing Configuration

### ৩.১ Parcel Pricing List
```
Route: GET /admin/parcel-pricing
Permission: settings, read

Table:
- Name (e.g., "Normal Small 0-1kg")
- Parcel Type (Normal/Fragile/Document — badge)
- Weight Range (min - max kg)
- Base Charge (৳)
- Per KM Charge (৳)
- Status toggle
- Actions: Edit, Delete

"নতুন Pricing Rule" button
```

### ৩.২ Create / Edit Parcel Pricing
```
Route: GET  /admin/parcel-pricing/create
Route: POST /admin/parcel-pricing
Route: GET  /admin/parcel-pricing/{id}/edit
Route: PUT  /admin/parcel-pricing/{id}
Permission: settings, write

Form:
- Name (required)
- Parcel Type: normal / fragile / document (select)
- Min Weight (decimal, kg, required)
- Max Weight (decimal, kg, required, > min)
- Base Charge (decimal, required)
- Per KM Charge (decimal, required)
- Status: Active / Inactive

Charge Preview:
- Example: 0.5 KG parcel, 5 KM distance
- Calculated: Base Charge + (5 × per_km)
- Live update with JS
```

### ৩.৩ Commission Settings (এই page-এর নিচে separate section)
```
এই section-এ Admin commission % set করবে:

Parcel Commission Settings:
- Admin Commission %: (input, 0-100, default 20)
  → Delivery Charge-এর এই % Admin নেবে
  → বাকি % Driver পাবে

Example preview:
"Delivery Charge ১০০ টাকা হলে:
 Admin পাবে: ২০ টাকা (২০%)
 Driver পাবে: ৮০ টাকা (৮০%)"

Ride Commission Settings:
- Ride Commission %: (input, 0-100, default 15)
  → Total Fare-এর এই % Admin নেবে
  → বাকি % Driver পাবে

Save করলে system_settings table-এ save হবে:
key: parcel_admin_commission_percent → value: "20"
key: ride_admin_commission_percent → value: "15"
```

---

## ৪. Zone Management

### ৪.১ Zone List
```
Route: GET /admin/zones
Permission: settings, read

Table:
- Name
- Active Services (badges)
- Total Drivers assigned
- Status toggle
- Actions: Edit, View on Map, Delete
```

### ৪.২ Create / Edit Zone
```
Route: GET  /admin/zones/create
Route: POST /admin/zones
Route: GET  /admin/zones/{id}/edit
Route: PUT  /admin/zones/{id}
Permission: settings, write

Form:
Section 1 — Basic Info:
- Zone Name (required)
- Status: Active / Inactive

Section 2 — Map (Google Maps):
- Full-width map (height: 500px)
- Drawing tools:
  Option A: Draw Polygon (click to add points, close polygon)
  Option B: Draw Circle (center point + radius in KM)
- Edit existing polygon/circle
- Clear and redraw button
- polygon data → JSON field (hidden input)
- center_lat, center_lng, radius_km (hidden inputs)

Google Maps Drawing Library:
```html
<script src="https://maps.googleapis.com/maps/api/js?key={API_KEY}&libraries=drawing"></script>
```

Section 3 — Services in this Zone:
- Available Services checkboxes
- প্রতিটি Service-এর জন্য:
  - Operating Hours Start (time input, optional)
  - Operating Hours End (time input, optional)
  - "সব সময়" checkbox → time inputs hide হবে

Section 4 — Pricing Override (optional):
- এই Zone-এ কি আলাদা pricing থাকবে? (toggle)
- যদি হ্যাঁ: Base Fare modifier (%, + বা -)

Validation:
- name: required
- polygon: required (map-এ না আঁকলে error)
```

### ৪.৩ Zone Detail / Map View
```
Route: GET /admin/zones/{id}

- Full page map এ zone এর polygon/circle দেখাবে
- Online drivers যারা এই zone-এ আছে তাদের pin দেখাবে
- Zone-এর stats: Total Trips today, Active Drivers
```

### ৪.৪ Delete Zone
```
Route: DELETE /admin/zones/{id}
Check: এই zone-এ active drivers বা pending orders আছে কিনা
Warning দেখাবে — confirm করলে delete (drivers থেকে zone_id null হবে)
```

---

## ৫. Surge Pricing Rules

### ৫.১ Surge Overview
```
Route: GET /admin/surge-pricing
Permission: settings, read

Page top-এ:
- Surge Enabled/Disabled master toggle
  (system_settings: surge_enabled)
- "Surge বন্ধ থাকলে সব rules inactive থাকবে" note

Rules Table:
- Name
- Zone (বা "সব Zone")
- Vehicle Category (বা "সব")
- Days (Mon-Sun icons)
- Time Range
- Multiplier (e.g., 1.5x — badge)
- Status toggle
- Actions: Edit, Delete

"নতুন Rule যোগ করুন" button
```

### ৫.২ Create / Edit Surge Rule
```
Route: GET  /admin/surge-pricing/create
Route: POST /admin/surge-pricing
Route: GET  /admin/surge-pricing/{id}/edit
Route: PUT  /admin/surge-pricing/{id}
Permission: settings, write

Form:
- Rule Name (required, e.g., "Evening Rush Hour")
- Zone: All Zones / specific zone (select)
- Vehicle Category: All / specific (select)
- Days of Week: checkboxes (Sat, Sun, Mon, Tue, Wed, Thu, Fri)
  "সব দিন" select-all checkbox
- Start Time (time, required)
- End Time (time, required)
- Multiplier (decimal, min 1.1, max 5.0, step 0.1)
- Status: Active / Inactive

Preview:
"এই rule active থাকলে: ১০০ টাকার fare হবে ১৫০ টাকা (1.5x)"

Conflict Check:
Same zone + same time range-এ আরেকটি rule আছে কিনা check করবে
Warning দেখাবে (block করবে না)
```

### ৫.৩ Manual Surge Activate
```
Page-এ একটা "Emergency Surge" section:
- Select Zone (required)
- Select Vehicle Category (all / specific)
- Multiplier (1.1 - 5.0)
- Duration: শেষ হওয়ার সময় (datetime picker)
- "Activate Now" button

এটা temporary surge — system_settings-এ save হবে না
আলাদা table লাগবে না — surge_pricing_rules-এ is_manual=true, ends_at timestamp add করো

অথবা সহজে: temporary একটা surge rule তৈরি করবে auto-expire সহ
```

---

## ৬. General Settings Page (এই পর্যায়ে শুধু Pricing-related)

```
Route: GET  /admin/settings/pricing
Route: POST /admin/settings/pricing
Permission: settings, write

এই page-এ থাকবে:

Section: Ride Settings
- Ride-share Enable/Disable toggle
- Max Pool Passengers (2-4, shown if ride-share enabled)
- Pool Discount % for passengers

Section: Parcel Settings  
- Payment Timing: Before / After / Both (radio)
- COD Enable/Disable toggle
- Proof of Delivery Enable/Disable
- Proof Type: OTP / Photo / Signature (shown if enabled)

Section: Booking Settings
- Scheduled Booking Enable/Disable
- Max Schedule Days (1-30)
- Driver Assign Before Minutes (15-120)

Section: Cancellation Settings
- Cancellation Fee Enable/Disable
- Grace Period (minutes) — এই সময়ের পরে cancel করলে fee লাগবে
- Cancellation Fee Amount (fixed, টাকা)

Section: Driver Settings
- Due Limit Amount (টাকা) — সব driver-এর জন্য same
- Withdrawal Minimum Amount (টাকা)
- Request Timeout Seconds (default 30)

Section: Tip Settings
- Tip Enable/Disable toggle
- Tip Amounts (comma separated, e.g.: 10,20,50,100)

Save করলে system_settings table update হবে।
```

---

## File Structure

```
app/
  Http/
    Controllers/
      Admin/
        ServiceController.php
        VehicleCategoryController.php
        ParcelPricingController.php
        ZoneController.php
        SurgePricingController.php
        Settings/
          PricingSettingsController.php
  Models/
    Service.php
    VehicleCategory.php
    ParcelPricing.php
    Zone.php
    ZoneService.php
    SurgePricingRule.php

resources/
  views/
    admin/
      services/
        index.blade.php
        create.blade.php
        edit.blade.php
      vehicle-categories/
        index.blade.php
        create.blade.php
        edit.blade.php
      parcel-pricing/
        index.blade.php
        create.blade.php
        edit.blade.php
      zones/
        index.blade.php
        create.blade.php
        edit.blade.php
        show.blade.php
      surge-pricing/
        index.blade.php
        create.blade.php
        edit.blade.php
      settings/
        pricing.blade.php
```

---

## Models ও Relationships

```php
// Service.php
hasMany(VehicleCategory::class)
hasMany(ZoneService::class)
hasMany(Order::class)

// VehicleCategory.php
belongsTo(Service::class)
hasMany(Order::class)
hasMany(DriverVehicle::class)

// Zone.php
hasMany(ZoneService::class)
hasMany(Driver::class, 'zone_id')
belongsToMany(Service::class, 'zone_services')

// ZoneService.php
belongsTo(Zone::class)
belongsTo(Service::class)

// ParcelPricing.php
// standalone — no major relations

// SurgePricingRule.php
belongsTo(Zone::class, 'zone_id')->nullable()
belongsTo(VehicleCategory::class, 'vehicle_category_id')->nullable()
```

---

## Helper Service Class — PricingService.php

```php
// app/Services/PricingService.php

class PricingService
{
    // Ride fare calculate করবে
    public function calculateRideFare(
        VehicleCategory $category,
        float $distanceKm,
        int $durationMinutes,
        ?Zone $zone = null
    ): array
    // returns: [base, distance, time, surge_multiplier, surge_amount, total]

    // Active surge multiplier check করবে
    public function getActiveSurgeMultiplier(
        int $vehicleCategoryId,
        ?int $zoneId = null
    ): float
    // returns multiplier (1.0 if no surge)

    // Parcel delivery charge calculate করবে
    public function calculateParcelCharge(
        string $parcelType,
        float $weightKg,
        float $distanceKm
    ): float
    // returns total delivery charge

    // Commission split করবে
    public function splitCommission(
        float $totalAmount,
        string $type  // 'ride' or 'parcel'
    ): array
    // returns: [admin_amount, driver_amount]
}
```

---

## Routes

```php
// routes/web.php — এই পর্যায়ে add করবে

Route::prefix('admin')->name('admin.')->middleware(['admin.auth'])->group(function () {

    // Services
    Route::resource('services', ServiceController::class);
    Route::post('services/{id}/toggle-status', [ServiceController::class, 'toggleStatus'])
         ->name('services.toggle-status');

    // Vehicle Categories
    Route::resource('vehicle-categories', VehicleCategoryController::class);
    Route::post('vehicle-categories/{id}/toggle-status', [VehicleCategoryController::class, 'toggleStatus'])
         ->name('vehicle-categories.toggle-status');

    // Parcel Pricing
    Route::resource('parcel-pricing', ParcelPricingController::class);
    Route::post('parcel-pricing/commission', [ParcelPricingController::class, 'saveCommission'])
         ->name('parcel-pricing.commission');

    // Zones
    Route::resource('zones', ZoneController::class);
    Route::post('zones/{id}/toggle-status', [ZoneController::class, 'toggleStatus'])
         ->name('zones.toggle-status');

    // Surge Pricing
    Route::resource('surge-pricing', SurgePricingController::class);
    Route::post('surge-pricing/manual-activate', [SurgePricingController::class, 'manualActivate'])
         ->name('surge-pricing.manual-activate');
    Route::post('surge-pricing/toggle-master', [SurgePricingController::class, 'toggleMaster'])
         ->name('surge-pricing.toggle-master');

    // Settings — Pricing
    Route::get('settings/pricing', [PricingSettingsController::class, 'index'])
         ->name('settings.pricing');
    Route::post('settings/pricing', [PricingSettingsController::class, 'update'])
         ->name('settings.pricing.update');
});
```

---

## Sidebar Update

```
Settings menu-এ নতুন items যোগ করবে:
├── Settings
│   ├── Services
│   ├── Vehicle Categories
│   ├── Parcel Pricing
│   ├── Zones
│   ├── Surge Pricing
│   └── Pricing Settings   ← নতুন
│   (বাকিগুলো পরের পর্যায়ে)
```

---

## UI Notes

### Map Integration (Zone Create/Edit)
```javascript
// Google Maps Drawing Manager
const drawingManager = new google.maps.DrawingManager({
    drawingMode: google.maps.drawing.OverlayType.POLYGON,
    drawingControl: true,
    drawingControlOptions: {
        position: google.maps.ControlPosition.TOP_CENTER,
        drawingModes: [
            google.maps.drawing.OverlayType.POLYGON,
            google.maps.drawing.OverlayType.CIRCLE,
        ],
    },
});

// Polygon complete হলে coordinates JSON-এ convert করো
google.maps.event.addListener(drawingManager, 'polygoncomplete', function(polygon) {
    const path = polygon.getPath();
    const coordinates = [];
    path.forEach(latLng => coordinates.push({lat: latLng.lat(), lng: latLng.lng()}));
    document.getElementById('polygon_data').value = JSON.stringify(coordinates);
    document.getElementById('center_lat').value = /* calculate center */;
    document.getElementById('center_lng').value = /* calculate center */;
});
```

### Surge Rule — Day Checkboxes UI
```html
<!-- সাত দিনের pill-style checkbox -->
<div class="flex gap-2">
    @foreach(['শনি','রবি','সোম','মঙ্গল','বুধ','বৃহ','শুক্র'] as $i => $day)
    <label class="cursor-pointer">
        <input type="checkbox" name="day_of_week[]" value="{{ $i }}" class="hidden peer">
        <span class="px-3 py-1 rounded-full border text-sm
                     peer-checked:bg-blue-600 peer-checked:text-white
                     peer-checked:border-blue-600">
            {{ $day }}
        </span>
    </label>
    @endforeach
</div>
```

### Toggle Switch Component
```html
<!-- Reusable toggle — x-toggle.blade.php -->
<!-- Props: name, checked, route -->
<button
    onclick="toggleStatus('{{ $route }}')"
    class="relative inline-flex h-6 w-11 items-center rounded-full
           {{ $checked ? 'bg-blue-600' : 'bg-gray-200' }} transition-colors">
    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition
                 {{ $checked ? 'translate-x-6' : 'translate-x-1' }}">
    </span>
</button>
```

---

## গুরুত্বপূর্ণ নিয়ম

1. **Google Maps Key** না থাকলে Zone form-এ Map-এর বদলে warning দেখাবে:
   "Google Maps API Key configure করুন Settings → Map থেকে"
   তারপরও form submit করা যাবে — polygon ছাড়া (validation skip)

2. **System Settings** সব সময় `SystemSettingService` বা helper দিয়ে read/write করবে:
```php
// app/Services/SystemSettingService.php
public function get(string $key, $default = null)
public function set(string $key, string $value, string $group)
public function getGroup(string $group): array
```
Cache করবে — `Cache::remember("setting_{$key}", 3600, ...)`
Setting update হলে cache clear করবে।

3. **File Upload** — `storage/app/public` এ save করবে, symlink দিয়ে public access।
   Path format: `services/{slug}.png`, `vehicle-categories/{id}.png`

4. **Delete confirmation** — সব delete button-এ JavaScript confirm dialog দেখাবে

5. **Pagination** — সব list page-এ 20 items per page

6. **Empty State** — কোনো data না থাকলে friendly empty state দেখাবে (icon + message + create button)

---

## শুরু করো এই order-এ

1. `SystemSettingService.php` বানাও (সব settings read/write এখান দিয়ে হবে)
2. `PricingService.php` বানাও
3. Models তৈরি (Service, VehicleCategory, ParcelPricing, Zone, ZoneService, SurgePricingRule)
4. Controllers তৈরি
5. Blade views তৈরি
6. Routes update
7. Sidebar update
8. Test: Service create → Vehicle Category create → Zone create → Surge rule create → Settings save
