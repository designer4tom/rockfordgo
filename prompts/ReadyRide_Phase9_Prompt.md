# ReadyRide — Claude Code Master Instruction
# পর্যায় ৯: Reports + System Settings + Landing Page CMS

---

## Context
পর্যায় ১-৮ শেষ। এটি Admin Panel-এর শেষ পর্যায়।
Reports, System Settings (General/Map/Advanced), এবং Landing Page CMS বানাবো।

---

## এই পর্যায়ে যা করবে

1. Reports & Analytics
2. System Settings (General, Map, Advanced)
3. Landing Page CMS
4. Admin Profile Settings
5. Final polish ও cleanup

---

## ১. Reports & Analytics

### ১.১ Revenue Report
```
Route: GET /admin/reports/revenue
Permission: reports, read

Filters:
- Date Range (default: this month)
- Group By: Daily / Weekly / Monthly
- Service Type: All / Ride / Parcel
- Zone: All / specific
- Payment Method: All / Cash / Online / Wallet / COD

Summary Cards:
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Gross Revenue│ │  Commission  │ │Driver Payout │ │ Net Revenue  │
│  ৳ ৪৫,৬০০  │ │  ৳ ৯,১২০   │ │ ৳ ৩৬,৪৮০   │ │  ৳ ৪৩,২০০  │
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘

Chart: Line chart — Revenue trend (Group By অনুযায়ী)
Dual line: Ride Revenue + Parcel Revenue

Breakdown Table:
Date/Week/Month | Rides | Parcels | Gross | Commission | Refunds | Net
Export: CSV / PDF (basic)
```

### ১.২ Driver Report
```
Route: GET /admin/reports/drivers
Permission: reports, read

Filters:
- Date Range
- Zone
- Vehicle Category
- Status: All / Active / Suspended

Table:
Driver | Zone | Total Trips | Total Earned | Commission Paid |
Due Amount | Avg Rating | Completion Rate | Online Hours

Export: CSV

Summary:
- Top 10 Drivers (by earnings)
- Top 10 Drivers (by trips)
- Lowest rated (< 3 stars)
```

### ১.৩ Order Report
```
Route: GET /admin/reports/orders
Permission: reports, read

Filters:
- Date Range
- Type: Ride / Parcel / All
- Status
- Zone

Summary Cards:
Total Orders | Completed | Cancelled | Cancellation Rate %

Charts:
- Orders by Status (Pie)
- Orders by Hour of Day (Bar — peak hours)
- Orders by Day of Week (Bar)

Table:
Date | Total | Completed | Cancelled | Avg Amount | Avg Distance

Cancellation Analysis:
- Cancelled by: Customer X% / Driver X% / Admin X%
- Top cancellation reasons
```

### ১.৪ Customer Report
```
Route: GET /admin/reports/customers
Permission: reports, read

Summary:
- New Customers (this period)
- Active Customers (placed order)
- Retained Customers (2+ orders)
- Retention Rate %

Table:
Customer | Total Orders | Total Spent | Last Order | Avg Order Value

Top Customers (by spending)
```

### ১.৫ Report Navigation
```
Sidebar:
Reports
├── Revenue
├── Drivers
├── Orders
└── Customers
```

---

## ২. System Settings — General

```
Route: GET  /admin/settings/general
Route: POST /admin/settings/general
Permission: settings, write

Section: App Info
- App Name (required)
- App Logo (image upload)
- App Favicon (image upload)
- Support Email
- Support Phone
- App Store Link (Customer)
- Play Store Link (Customer)
- App Store Link (Driver)
- Play Store Link (Driver)

Section: Locale
- Default Currency: BDT / USD / etc. (select)
- Currency Symbol: ৳ / $
- Currency Position: Before / After amount
- Timezone: (dropdown — common timezones)
- Date Format: DD/MM/YYYY / MM/DD/YYYY / YYYY-MM-DD
- Time Format: 12h / 24h

Section: Maintenance Mode
- Maintenance Mode toggle
- Maintenance Message (textarea)
- If enabled: app shows maintenance screen

Save → system_settings update
```

---

## ৩. System Settings — Map

```
Route: GET  /admin/settings/map
Route: POST /admin/settings/map
Permission: settings, write

Section: Google Maps
- Google Maps API Key
  (masked input, show/hide)
  "Test Key" button → verify with simple geocode request
- Default Map Center Lat
- Default Map Center Lng
- Default Map Zoom (8-18)
- Map Type: Roadmap / Satellite / Hybrid

Section: Driver Tracking
- Location Update Interval (seconds): 3 / 5 / 10 (radio)
  Note: কম হলে accurate কিন্তু battery বেশি খরচ
- Track Driver When: Always Online / Only During Trip (radio)

Section: Order Matching
- Initial Search Radius (km): 1-20
- Auto-expand Radius: Yes / No
- Expand by (km) every: X seconds
- Maximum Radius (km)

Save → system_settings update
```

---

## ৪. System Settings — Advanced

```
Route: GET  /admin/settings/advanced
Route: POST /admin/settings/advanced
Permission: settings, write (Super Admin only)

Section: Cache Management
- Clear All Cache button
- Clear Settings Cache button
- Clear Dashboard Cache button
- Last Cleared: [datetime]

Section: Queue Management
- Queue Driver: sync / database / redis
- Failed Jobs count
- "Retry Failed Jobs" button
- "Clear Failed Jobs" button

Section: Log Viewer (simple)
- Show last 50 lines of storage/logs/laravel.log
- "Download Log" button
- "Clear Log" button

Section: Database
- Current DB size (MB)
- Total Records per table (key tables)

Section: App Version
- Current Version (from config)
- Environment: local / production

Section: Danger Zone (Super Admin only, red section)
- "Clear All Orders" — with triple confirmation
- "Reset Statistics" — clears cached stats
```

---

## ৫. Landing Page CMS

```
Route: GET /admin/landing-page
Permission: settings, write

Tab-based CMS editor:

── Tab 1: Hero Section ─────────────────────────
- Headline (input)
- Sub-headline (textarea)
- CTA Button Text
- CTA Button Link
- Background Image (upload)
- Preview: mini preview of how it looks

── Tab 2: Features ──────────────────────────────
List of feature cards (add/edit/delete/reorder):
Each feature:
- Icon (emoji বা icon class)
- Title
- Description
- Sort Order (drag to reorder)

[+ Add Feature] button

── Tab 3: How It Works ──────────────────────────
Steps list (add/edit/delete):
Each step:
- Step Number (auto)
- Title
- Description
- Icon/Image

── Tab 4: App Download ──────────────────────────
- Section Title
- Section Description
- Play Store Link (Customer App)
- App Store Link (Customer App)
- Play Store Link (Driver App)
- App Store Link (Driver App)
- App Screenshot 1 (upload)
- App Screenshot 2 (upload)

── Tab 5: Testimonials ──────────────────────────
List (add/edit/delete):
Each testimonial:
- Customer Name
- Avatar (upload or initials)
- Rating (1-5 stars)
- Review Text
- Date
- Is Active toggle

── Tab 6: FAQ ───────────────────────────────────
List (add/edit/delete/reorder):
Each FAQ:
- Question
- Answer (textarea with basic formatting)
- Category: General / Ride / Parcel / Payment
- Is Active toggle

── Tab 7: Contact ───────────────────────────────
- Office Address
- Phone Number
- Email
- Facebook URL
- Instagram URL
- Twitter/X URL
- LinkedIn URL
- WhatsApp Number
- Google Maps Embed URL (iframe src)

── Tab 8: SEO ───────────────────────────────────
- Page Title (max 60 chars — counter)
- Meta Description (max 160 chars — counter)
- OG Image (upload — 1200x630)
- Google Analytics ID (G-XXXXXXXX)
- Facebook Pixel ID (optional)

Save per tab (individual save buttons)
"Preview Landing Page" button → opens /landing in new tab
```

---

## ৬. Landing Page (Public)

```
Route: GET /
Route: GET /landing

Public route — no auth required
Blade: resources/views/landing/index.blade.php

Layout:
- Navbar (App name/logo, Download links)
- Hero section
- Features section
- How it works
- App download
- Testimonials
- FAQ (accordion)
- Contact
- Footer

Data: LandingPageController@index
- Query landing_page_contents grouped by section
- Pass to view

Tailwind CSS — mobile responsive
```

---

## ৭. Admin Profile Settings

```
Route: GET  /admin/profile
Route: POST /admin/profile
Route: POST /admin/profile/change-password

Form:
- Name
- Email (unique)
- Avatar (upload)
- Current Password (required for changes)

Change Password:
- Current Password
- New Password (min 8)
- Confirm New Password

2FA Management (link to auth/two-factor)
Login History link
```

---

## File Structure

```
app/Http/Controllers/Admin/
  Reports/
    RevenueReportController.php
    DriverReportController.php
    OrderReportController.php
    CustomerReportController.php
  Settings/
    GeneralSettingsController.php
    MapSettingsController.php
    AdvancedSettingsController.php
  LandingPageController.php
  ProfileController.php

app/Http/Controllers/
  LandingController.php  ← public

resources/views/
  admin/
    reports/
      revenue.blade.php
      drivers.blade.php
      orders.blade.php
      customers.blade.php
    settings/
      general.blade.php
      map.blade.php
      advanced.blade.php
      (payment.blade.php — Phase 7 থেকে)
      (notifications.blade.php — Phase 8 থেকে)
      (pricing.blade.php — Phase 3 থেকে)
    landing-page/
      index.blade.php    ← CMS editor (tabbed)
    profile/
      index.blade.php
  landing/
    index.blade.php      ← public page
  components/
    landing/
      hero.blade.php
      features.blade.php
      how-it-works.blade.php
      app-download.blade.php
      testimonials.blade.php
      faq.blade.php
      contact.blade.php
```

---

## Routes

```php
// Reports
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('revenue', [RevenueReportController::class, 'index'])->name('revenue');
    Route::get('revenue/export', [RevenueReportController::class, 'export'])
         ->name('revenue.export');
    Route::get('drivers', [DriverReportController::class, 'index'])->name('drivers');
    Route::get('drivers/export', [DriverReportController::class, 'export'])
         ->name('drivers.export');
    Route::get('orders', [OrderReportController::class, 'index'])->name('orders');
    Route::get('customers', [CustomerReportController::class, 'index'])->name('customers');
});

// Settings
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('general', [GeneralSettingsController::class, 'index'])->name('general');
    Route::post('general', [GeneralSettingsController::class, 'update'])
         ->name('general.update');
    Route::get('map', [MapSettingsController::class, 'index'])->name('map');
    Route::post('map', [MapSettingsController::class, 'update'])->name('map.update');
    Route::post('map/test-key', [MapSettingsController::class, 'testKey'])
         ->name('map.test-key');
    Route::get('advanced', [AdvancedSettingsController::class, 'index'])->name('advanced');
    Route::post('advanced/clear-cache', [AdvancedSettingsController::class, 'clearCache'])
         ->name('advanced.clear-cache');
    Route::post('advanced/retry-jobs', [AdvancedSettingsController::class, 'retryJobs'])
         ->name('advanced.retry-jobs');
});

// Landing Page CMS
Route::get('landing-page', [LandingPageController::class, 'index'])
     ->name('landing-page.index');
Route::post('landing-page/{section}', [LandingPageController::class, 'update'])
     ->name('landing-page.update');
Route::post('landing-page/items/store', [LandingPageController::class, 'storeItem'])
     ->name('landing-page.items.store');
Route::put('landing-page/items/{id}', [LandingPageController::class, 'updateItem'])
     ->name('landing-page.items.update');
Route::delete('landing-page/items/{id}', [LandingPageController::class, 'deleteItem'])
     ->name('landing-page.items.delete');
Route::post('landing-page/items/reorder', [LandingPageController::class, 'reorder'])
     ->name('landing-page.items.reorder');

// Profile
Route::get('profile', [ProfileController::class, 'index'])->name('profile');
Route::post('profile', [ProfileController::class, 'update'])->name('profile.update');
Route::post('profile/change-password', [ProfileController::class, 'changePassword'])
     ->name('profile.change-password');

// Public Landing Page
Route::get('/', [LandingController::class, 'index'])->name('landing');
```

---

## Sidebar Final Structure

```
সম্পূর্ণ Sidebar:

📊 Dashboard

👥 Users
  ├── All Customers
  └── Referrals

🚗 Drivers
  ├── All Drivers
  ├── Pending Approvals
  └── Expiring Documents

📋 Orders
  ├── All Orders
  ├── Scheduled Orders
  └── Disputes

💰 Payments
  ├── Transactions
  ├── Withdrawals
  ├── Refunds
  ├── COD Reconciliation
  ├── Driver Dues
  └── Revenue Summary

🔖 Coupons

🔔 Notifications
  ├── Broadcast
  └── History

🆘 SOS Alerts

📈 Reports
  ├── Revenue
  ├── Drivers
  ├── Orders
  └── Customers

⚙️ Settings
  ├── General
  ├── Map
  ├── Payment (Stripe)
  ├── Notifications (FCM/Pusher)
  ├── Pricing
  └── Advanced

🛠️ Services
  ├── Services
  ├── Vehicle Categories
  ├── Parcel Pricing
  ├── Zones
  └── Surge Pricing

🌐 Landing Page

👤 Profile
```

---

## Final Polish

### ১. 404 Page
```
resources/views/errors/404.blade.php
- Admin layout
- "Page not found" with go back button
```

### ২. Breadcrumbs Component
```
resources/views/components/breadcrumb.blade.php
All pages-এ breadcrumb দেখাবে:
Dashboard > Drivers > John Doe > Edit
```

### ৩. Confirm Delete Component
```
resources/views/components/confirm-delete.blade.php
Reusable delete confirmation modal
All delete buttons এটা use করবে
```

### ৪. DataTable Enhancement
```
All list tables-এ:
- Column sorting (click header)
- Rows per page selector: 10 / 20 / 50 / 100
- Page info: "Showing 1-20 of 156"
```

### ৫. Toast Notifications
```
Success/Error messages toast হিসেবে দেখাবে (top-right)
Auto-dismiss: 4 seconds
Laravel session flash message → JS toast
```

---

## গুরুত্বপূর্ণ নিয়ম

1. Reports page-এ heavy query → **Cache করবে** (5 মিনিট)
2. Landing page public — **auth middleware নেই**
3. CMS save AJAX দিয়ে করবে — page reload ছাড়া
4. Advanced settings Danger Zone-এ **triple confirmation** (type "CONFIRM" in input)
5. App Logo/Favicon storage-এ save, public URL দিয়ে serve

---

## শুরু করো এই order-এ

1. Report Controllers (Revenue, Driver, Order, Customer)
2. Report Blade views (charts সহ)
3. Settings Controllers (General, Map, Advanced)
4. Settings Blade views
5. LandingPageController (CMS) + Public LandingController
6. Landing Page Blade (public page)
7. ProfileController
8. 404, Breadcrumbs, Toast, Confirm Delete components
9. Final sidebar update
10. সব routes এক জায়গায় review করো
```
