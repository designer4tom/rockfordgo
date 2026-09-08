# ReadyRide — Claude Code Master Instruction
# পর্যায় ২: Admin Auth + Role/Permission + Dashboard

---

## Context
তুমি ReadyRide Admin Panel বানাচ্ছো।
পর্যায় ১ শেষ হয়েছে — Laravel project setup, সব Migrations, Seeders, Base Layout, Login page ready আছে।

এই পর্যায়ে শুধু নিচের কাজগুলো করবে।

---

## এই পর্যায়ে যা করবে

1. Admin Auth সম্পূর্ণ করা (2FA, Login History, Session)
2. Role ও Permission System
3. Sub-admin Management (CRUD)
4. Dashboard (Real-time Stats + Charts)

---

## ১. Admin Auth — সম্পূর্ণ করা

### ১.১ Login (আগের থেকে যা missing থাকতে পারে)
```
Route: GET  /admin/login  → login form
Route: POST /admin/login  → authenticate

Validation:
- email: required, email
- password: required, min 8
- remember: optional boolean

Logic:
- Auth::guard('admin')->attempt()
- Failed attempt count session-এ রাখো
- 5 বার fail → 15 মিনিট lockout (cache দিয়ে)
- Success হলে:
    → admin_login_logs এ insert (ip, user_agent, status: success)
    → last_login_at, last_login_ip update করো admins table-এ
    → /admin/dashboard redirect
- Fail হলে:
    → admin_login_logs এ insert (status: failed)
    → back with error
```

### ১.২ Two-Factor Authentication (2FA)
```
Admin Profile থেকে 2FA enable করতে পারবে।

Enable flow:
- Google Authenticator-এর জন্য QR Code generate করো (pragmarx/google2fa-laravel package)
- Admin QR scan করে code দিলে verify করো
- Verified হলে two_factor_enabled = true, secret save

Login flow (2FA enabled হলে):
- Email/Password correct হলে → session-এ pending_2fa_admin_id রাখো
- 2FA code entry page দেখাও
- Code verify হলে → actual login complete
- Code ভুল হলে → error, আবার চেষ্টা

Disable flow:
- Current password confirm করে disable করা যাবে
```

### ১.৩ Logout
```
Route: POST /admin/logout
- Auth::guard('admin')->logout()
- Session clear
- /admin/login redirect
```

### ১.৪ Login History Page
```
Route: GET /admin/login-history

Table দেখাবে:
- IP Address
- Browser/Device (user_agent parse করে)
- Status (success/failed) — badge দিয়ে
- Date & Time
- Pagination (20 per page)
```

---

## ২. Role ও Permission System

### ২.১ Middleware — CheckPermission
```php
// app/Http/Middleware/CheckPermission.php

// Usage: route-এ ->middleware('permission:drivers,write')
// Format: permission:{module},{action}
// action: read / write / delete

// Super Admin সব কিছু access করতে পারবে — permission check লাগবে না
// Sub Admin ও Fleet Manager-এর জন্য admin_permissions table check করবে

// Permission নেই → abort(403) with custom blade page
```

### ২.২ Helper — adminCan()
```php
// app/Helpers/AdminHelper.php

function adminCan(string $module, string $action): bool
// Blade-এ ব্যবহার: @if(adminCan('drivers', 'write'))
// Controller-এ: if(!adminCan('drivers', 'delete')) abort(403)
```

### ২.৩ 403 Page
```
resources/views/errors/403.blade.php
- Admin layout use করবে
- "আপনার এই section-এ access নেই" message
- Dashboard-এ ফিরে যাওয়ার button
```

---

## ৩. Sub-Admin Management

### ৩.১ List Page
```
Route: GET /admin/sub-admins
Permission: settings, read

Table columns:
- নাম
- Email
- Role (badge: sub_admin / fleet_manager)
- Status (Active/Inactive toggle)
- Last Login
- Actions: Edit, Delete (Super Admin only)

Search: নাম বা email দিয়ে
```

### ৩.২ Create Sub-Admin
```
Route: GET  /admin/sub-admins/create
Route: POST /admin/sub-admins

Permission: settings, write (Super Admin only)

Form fields:
- Name (required)
- Email (required, unique)
- Password (required, min 8, confirm)
- Role: sub_admin / fleet_manager
- Status: Active/Inactive

Permission Matrix (checkbox grid):
Module          | Read | Write | Delete
----------------|------|-------|-------
Dashboard       |  ✓   |       |
Users           |  ✓   |   ✓   |   ✓
Drivers         |  ✓   |   ✓   |   ✓
Orders          |  ✓   |   ✓   |
Payments        |  ✓   |   ✓   |
Settings        |  ✓   |   ✓   |
Reports         |  ✓   |       |
SOS             |  ✓   |   ✓   |
Disputes        |  ✓   |   ✓   |

Default: সব unchecked
Super Admin নিজে check করে দেবে
```

### ৩.৩ Edit Sub-Admin
```
Route: GET  /admin/sub-admins/{id}/edit
Route: PUT  /admin/sub-admins/{id}

Same form — permission matrix pre-filled
Password change optional (blank রাখলে change হবে না)
```

### ৩.৪ Delete Sub-Admin
```
Route: DELETE /admin/sub-admins/{id}
Super Admin only
Soft delete না — hard delete (admin_permissions cascade delete)
```

### ৩.৫ Toggle Status
```
Route: POST /admin/sub-admins/{id}/toggle-status
Active → Inactive করলে সেই admin আর login করতে পারবে না
```

---

## ৪. Dashboard

### ৪.১ Stats Cards (Real-time)
```
Controller: Admin\DashboardController@index

নিচের data query করবে:

// Today stats
today_rides        → orders where type=ride, date=today, status=completed
today_parcels      → orders where type=parcel, date=today, status=completed
today_revenue      → orders sum(total_amount) where date=today, status=completed
today_new_users    → users where date=today

// Overall stats
total_drivers      → drivers count
active_drivers     → drivers where is_online=true
total_users        → users count
total_orders       → orders count where status=completed

// Alerts
pending_approvals  → drivers where status=pending
pending_disputes   → disputes where status=open OR under_review
pending_withdrawals → withdrawal_requests where status=pending
active_sos         → sos_alerts where status=active OR acknowledged
expiring_docs      → driver_documents where expiry_date <= now()+30days AND status=approved
```

### ৪.২ Stats Cards UI
```
Row 1 (4 cards):
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│  আজকের Ride  │ │ আজকের Parcel │ │ আজকের Revenue│ │ Online Driver│
│     ২৩       │ │      ১২      │ │   ৳ ৪,৫৬০   │ │      ৮       │
│  ↑ vs. কাল  │ │  ↑ vs. কাল  │ │  ↑ vs. কাল  │ │  মোট: ১৫৬   │
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘

Row 2 (4 cards — Alert cards):
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Pending      │ │ Open         │ │ Pending      │ │ Active SOS   │
│ Approvals    │ │ Disputes     │ │ Withdrawals  │ │              │
│     ৩        │ │      ২       │ │      ৫       │ │      ১       │
│ [View All]   │ │  [View All]  │ │  [View All]  │ │  [View All]  │
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘

Alert cards-এ count > 0 হলে লাল/হলুদ highlight দেখাবে
```

### ৪.৩ Revenue Chart (Line Chart)
```
Package: Chart.js (CDN দিয়ে)

Type: Line Chart
X-axis: শেষ ৭ দিনের তারিখ
Y-axis: Revenue (টাকা)
দুইটা line:
  - Ride Revenue (blue)
  - Parcel Revenue (green)

Data query:
SELECT DATE(created_at) as date,
       SUM(CASE WHEN type='ride' THEN total_amount ELSE 0 END) as ride_revenue,
       SUM(CASE WHEN type='parcel' THEN total_amount ELSE 0 END) as parcel_revenue
FROM orders
WHERE status = 'completed'
AND created_at >= NOW() - INTERVAL 7 DAY
GROUP BY DATE(created_at)
```

### ৪.৪ Orders Chart (Bar Chart)
```
Type: Bar Chart
X-axis: শেষ ৭ দিন
Y-axis: Order count
দুইটা bar:
  - Ride (blue)
  - Parcel (green)
```

### ৪.৫ Driver Status (Doughnut Chart)
```
Type: Doughnut/Pie
Segments:
  - Online (green)
  - Offline (gray)
  - Suspended (red)
  - Pending Approval (yellow)
```

### ৪.৬ Recent Orders Table
```
শেষ ১০টা Order দেখাবে:

Columns:
- Order # (link to detail)
- Type (Ride/Parcel badge)
- Customer নাম
- Driver নাম (বা "Searching...")
- Amount
- Status (colored badge)
- সময়

"সব Orders দেখুন" link
```

### ৪.৭ Recent Driver Registrations
```
শেষ ৫ জন নতুন Driver:
- Avatar + নাম
- ফোন
- Status badge
- কতক্ষণ আগে
- "Approve" quick button (pending হলে)
```

### ৪.৮ Expiring Documents Alert
```
৩০ দিনের মধ্যে expire হবে এমন Documents:

Table:
- Driver নাম
- Document type
- Expiry date
- দিন বাকি (লাল হবে যদি ৭ দিনের কম)
- "Notify Driver" button
```

### ৪.৯ Real-time Driver Map (Optional — যদি Google Maps Key থাকে)
```
একটা Map widget
Online Driver-দের location pin দেখাবে
Settings-এ Google Maps Key না থাকলে এই widget skip করবে
```

---

## File Structure যা তৈরি করবে

```
app/
  Http/
    Controllers/
      Admin/
        AuthController.php
        DashboardController.php
        SubAdminController.php
    Middleware/
      AdminAuthenticated.php
      CheckPermission.php
  Helpers/
    AdminHelper.php

resources/
  views/
    admin/
      auth/
        login.blade.php        (আগের থেকে update)
        two-factor.blade.php
      layouts/
        admin.blade.php        (আগের থেকে update)
      components/
        sidebar.blade.php
        navbar.blade.php
        stats-card.blade.php
        alert-card.blade.php
      dashboard/
        index.blade.php
      sub-admins/
        index.blade.php
        create.blade.php
        edit.blade.php
      login-history/
        index.blade.php
    errors/
      403.blade.php

routes/
  web.php                      (update — নতুন routes যোগ)
```

---

## Routes Structure

```php
// routes/web.php

// Public routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');
    Route::get('two-factor', [AuthController::class, 'showTwoFactor'])->name('two-factor');
    Route::post('two-factor', [AuthController::class, 'verifyTwoFactor'])->name('two-factor.post');
});

// Protected routes
Route::prefix('admin')->name('admin.')->middleware(['admin.auth'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('login-history', [AuthController::class, 'loginHistory'])->name('login-history');

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/', fn() => redirect()->route('admin.dashboard'));

    // Sub Admins (Super Admin only)
    Route::resource('sub-admins', SubAdminController::class);
    Route::post('sub-admins/{id}/toggle-status', [SubAdminController::class, 'toggleStatus'])->name('sub-admins.toggle-status');

    // Profile (পরে বানাবো)
    // Settings (পরে বানাবো)
});
```

---

## UI Design Requirements

### Color Scheme
```
Primary:   #1a56db (blue)
Success:   #057a55 (green)
Warning:   #c27803 (yellow)
Danger:    #c81e1e (red)
Dark:      #111928 (sidebar)
Light:     #f9fafb (content bg)
```

### Stats Card Component (stats-card.blade.php)
```
Props: title, value, subtitle, icon, color, link
- White background, rounded-xl, shadow-sm
- Icon left side (colored bg circle)
- Value বড় font (3xl, bold)
- Subtitle ছোট (gray)
- Hover: subtle shadow increase
```

### Alert Card Component (alert-card.blade.php)
```
Props: title, count, color, link
- count > 0 হলে: colored border-left (4px)
- count = 0 হলে: normal gray
- Click করলে সেই section-এ যাবে
```

### Chart Container
```
- White bg, rounded-xl, shadow-sm, p-6
- Chart title top-left
- Date range selector top-right (7 days / 30 days / 90 days)
- Chart.js canvas
```

---

## Dashboard-এ AJAX Refresh (optional but good)
```javascript
// প্রতি ৬০ সেকেন্ডে Stats Cards refresh হবে
// Route: GET /admin/dashboard/stats (JSON response)
// শুধু card values update হবে — page reload না

setInterval(() => {
    fetch('/admin/dashboard/stats')
        .then(r => r.json())
        .then(data => {
            // update DOM
        });
}, 60000);
```

---

## গুরুত্বপূর্ণ নিয়ম

1. **Super Admin** সব কিছু করতে পারবে — permission check bypass
2. **Fleet Manager** শুধু Drivers ও Orders দেখতে পারবে (default)
3. **Sub Admin** permission অনুযায়ী access পাবে
4. Dashboard-এ সব query **eager loading** দিয়ে করবে — N+1 problem যেন না হয়
5. Chart data **JSON** হিসেবে Blade-এ pass করবে (`json_encode`)
6. **Cache** ব্যবহার করবে Dashboard stats-এর জন্য — `Cache::remember('dashboard_stats', 60, fn() => ...)`
7. Sidebar-এ active menu highlight করবে current route অনুযায়ী
8. Permission matrix UI-তে **"Select All" row toggle** যোগ করবে

---

## শুরু করো এই order-এ

1. `pragmarx/google2fa-laravel` package install (2FA এর জন্য)
2. `AdminHelper.php` তৈরি + `AppServiceProvider`-এ register
3. Middleware update (`AdminAuthenticated`, `CheckPermission`)
4. `AuthController` — login, 2FA, logout, history
5. `SubAdminController` — CRUD + toggle
6. `DashboardController` — stats query + chart data
7. Blade files — auth pages, sub-admin pages
8. Dashboard blade — cards + charts + tables
9. Routes update
10. Test করো: login → dashboard → sub-admin create → permission check
```
