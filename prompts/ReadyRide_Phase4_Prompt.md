# ReadyRide — Claude Code Master Instruction
# পর্যায় ৪: Driver Management

---

## Context
পর্যায় ১, ২, ৩ শেষ — Foundation, Auth, Dashboard, Service/Zone/Pricing ready।
এই পর্যায়ে সম্পূর্ণ Driver Management বানাবো।

---

## এই পর্যায়ে যা করবে

1. Driver List (filter, search, export)
2. Driver Details Page
3. Driver Onboarding Approval (Document Review)
4. Document Expiry Management
5. Driver Block / Suspend
6. Driver Shift & Online History
7. Driver Performance Stats
8. Driver Wallet & Due Details

---

## ১. Driver List Page

```
Route: GET /admin/drivers
Permission: drivers, read

Filters (top bar):
- Search: নাম বা ফোন নম্বর
- Status: All / Pending / Approved / Rejected / Suspended / Blocked
- Zone: All / specific zone (dropdown)
- Vehicle Category: All / specific
- Online Status: All / Online / Offline
- Date Range: joined from-to

Table columns:
- Avatar + নাম (link to detail)
- ফোন
- Vehicle Category badge
- Zone
- Status badge (color-coded):
    pending    → yellow
    approved   → green
    rejected   → red
    suspended  → orange
    blocked    → dark red
- Online indicator (green dot / gray dot)
- Rating (stars)
- Total Trips
- Due Amount (লাল যদি > 0)
- Joined Date
- Actions: View, Edit, Block/Suspend

Bulk Actions:
- Select multiple → Approve / Reject / Suspend / Block

Export: CSV button (filtered results)

Pagination: 20 per page

Stats row (top):
Total: X | Pending: X | Online: X | Suspended: X | Blocked: X
```

---

## ২. Driver Details Page

```
Route: GET /admin/drivers/{id}
Permission: drivers, read

Layout: Tab-based

── Tab 1: Profile ──────────────────────────────
- Avatar (large), নাম, ফোন, Email
- Status badge + change status button
- Zone, Vehicle Category
- Joined date, Last online
- Quick stats: Total Trips, Rating, Completion Rate, Acceptance Rate
- Edit Profile button

── Tab 2: Documents ────────────────────────────
সব submitted documents:
প্রতিটি document card:
- Document type (label)
- Front ও Back image (lightbox preview)
- Expiry Date
- Status badge
- যদি pending: Approve / Reject buttons
- Reject করলে: reason input (required)
- যদি expired: "নতুন upload করতে বলুন" button (notification পাঠাবে)

── Tab 3: Vehicle ───────────────────────────────
- Vehicle details (Make, Model, Year, Color, Reg Number)
- Front ও Back photo
- Vehicle Category
- Edit option

── Tab 4: Trips & Deliveries ───────────────────
- সব past orders table (paginated)
- Filter: Ride / Parcel, Date range, Status
- Columns: Order#, Type, Date, Customer, Route, Amount, Status
- Click → order detail

── Tab 5: Wallet & Earnings ────────────────────
- Current Wallet Balance
- Total Earned (lifetime)
- Due Amount (লাল highlight)
- Due Limit: [current_due] / [limit] progress bar
- Transaction History (paginated):
  Date | Type | Category | Amount | Balance After
- Withdrawal History

── Tab 6: Performance ──────────────────────────
- Rating breakdown (5★ X, 4★ X, ...)
- Acceptance Rate (progress bar)
- Completion Rate (progress bar)
- Cancellation Rate
- Online Hours: Today / This Week / This Month
- Shift History (last 10 shifts)

── Tab 7: Notifications Sent ───────────────────
- Admin থেকে এই driver-কে পাঠানো notifications
- "নতুন Notification পাঠান" button
```

---

## ৩. Driver Approval Flow

```
Route: GET  /admin/drivers/pending
Permission: drivers, write

Pending Drivers list — same as main list কিন্তু শুধু pending

Quick Approve Page:
Route: GET /admin/drivers/{id}/review
Permission: drivers, write

Layout:
Left side:
- Driver basic info
- সব documents side by side
- Image viewer (click to enlarge)
- Document expiry dates

Right side:
- Approval Decision panel
- "Approve" button (green)
- "Reject" button (red)
  → Reject করলে: textarea (reason, required)
  → Reason driver-এর notification-এ যাবে

After approval:
- Driver status → approved
- Driver-কে FCM notification: "আপনার account approved হয়েছে"
- Admin dashboard pending count update

After rejection:
- Driver status → rejected
- rejection_reason save
- Driver-কে FCM notification: "আপনার application reject হয়েছে: [reason]"
```

---

## ৪. Document Expiry Management

```
Route: GET /admin/drivers/expiring-documents
Permission: drivers, read

Page দেখাবে:

Section 1: Expired Documents
- Driver নাম, Document Type, Expired কতদিন আগে
- "Driver Block করুন" button
- "Notify Driver" button

Section 2: Expiring in 7 Days
- লাল highlight
- "Notify Driver" button

Section 3: Expiring in 30 Days
- Yellow highlight
- "Notify Driver" button

"Notify All" bulk button (section-wise)

Notify করলে:
- notifications table-এ insert
- FCM push পাঠাবে driver-কে
- "আপনার [Document Type] [X] দিনে expire হবে। নতুন document upload করুন।"
```

---

## ৫. Driver Block / Suspend

```
Block/Suspend Modal (যেকোনো জায়গা থেকে):

Status Options:
- Suspend (Temporary)
  → Duration: X days (input) বা Manual unblock
  → Reason: required textarea
- Block (Permanent)
  → Reason: required textarea
- Unblock/Unsuspend
  → Confirmation only

Actions:
- Status update → drivers table
- Driver-কে notification পাঠানো
- Admin action log রাখা (admin_login_logs না, আলাদা):

driver_status_logs table (migration add করো):
id, driver_id, changed_by (FK admins), old_status, new_status,
reason, created_at
```

---

## ৬. Wallet ও Due Management (Admin side)

```
Due Management:
Route: POST /admin/drivers/{id}/clear-due
Permission: payments, write

- Admin manually driver-এর due clear করতে পারবে
- Reason লিখতে হবে
- due_transactions-এ record রাখবে (type: paid, note: "Admin cleared")
- driver.due_amount = 0

Manual Wallet Adjustment:
Route: POST /admin/drivers/{id}/wallet-adjust
Permission: payments, write

- Add বা Deduct amount
- Reason required
- wallet_transactions-এ record
```

---

## ৭. Driver Edit

```
Route: GET  /admin/drivers/{id}/edit
Route: PUT  /admin/drivers/{id}
Permission: drivers, write

Editable fields:
- নাম
- Email
- Zone (dropdown)
- Vehicle Category (dropdown)
- Avatar (re-upload)

Not editable by admin:
- Phone (sensitive)
- Password (driver app থেকে)
- Documents (driver upload করে, admin approve করে)
```

---

## Models Update

```php
// Driver.php — new methods/relations
hasMany(DriverDocument::class)
hasMany(DriverVehicle::class)
hasMany(DriverShiftLog::class)
hasMany(Order::class)
hasMany(WalletTransaction::class, 'owner_id')
    ->where('owner_type', 'driver')
hasMany(DueTransaction::class)
hasMany(WithdrawalRequest::class)
belongsTo(Zone::class)

// Scopes
scopeOnline($query) → where is_online = true
scopePending($query) → where status = pending
scopeApproved($query) → where status = approved
scopeExpiringDocuments($query, $days) → join documents where expiry <=

// Computed attributes
getIsDocumentExpiredAttribute()
getCanGoOnlineAttribute() → checks all conditions
```

---

## New Migration

```php
// driver_status_logs table
Schema::create('driver_status_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
    $table->foreignId('changed_by')->constrained('admins');
    $table->string('old_status');
    $table->string('new_status');
    $table->text('reason')->nullable();
    $table->timestamps();
});
```

---

## File Structure

```
app/Http/Controllers/Admin/
  DriverController.php
  DriverDocumentController.php
  DriverWalletController.php

resources/views/admin/
  drivers/
    index.blade.php
    show.blade.php        ← tab-based detail page
    edit.blade.php
    review.blade.php      ← approval page
    pending.blade.php
    expiring-documents.blade.php
  components/
    driver-status-badge.blade.php
    document-card.blade.php
    tab-nav.blade.php
```

---

## Routes

```php
// Driver routes
Route::prefix('drivers')->name('drivers.')->group(function () {
    Route::get('/', [DriverController::class, 'index'])->name('index');
    Route::get('/pending', [DriverController::class, 'pending'])->name('pending');
    Route::get('/expiring-documents', [DriverController::class, 'expiringDocuments'])
         ->name('expiring-documents');
    Route::get('/{id}', [DriverController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [DriverController::class, 'edit'])->name('edit');
    Route::put('/{id}', [DriverController::class, 'update'])->name('update');
    Route::get('/{id}/review', [DriverController::class, 'review'])->name('review');
    Route::post('/{id}/approve', [DriverController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [DriverController::class, 'reject'])->name('reject');
    Route::post('/{id}/change-status', [DriverController::class, 'changeStatus'])
         ->name('change-status');
    Route::post('/{id}/notify', [DriverController::class, 'notify'])->name('notify');

    // Documents
    Route::post('/{id}/documents/{docId}/approve',
         [DriverDocumentController::class, 'approve'])->name('documents.approve');
    Route::post('/{id}/documents/{docId}/reject',
         [DriverDocumentController::class, 'reject'])->name('documents.reject');

    // Wallet
    Route::post('/{id}/clear-due', [DriverWalletController::class, 'clearDue'])
         ->name('clear-due');
    Route::post('/{id}/wallet-adjust', [DriverWalletController::class, 'adjust'])
         ->name('wallet-adjust');

    // Export
    Route::get('/export/csv', [DriverController::class, 'export'])->name('export');
});
```

---

## গুরুত্বপূর্ণ নিয়ম

1. Document image preview-এ **lightbox** ব্যবহার করবে (SimpleLightbox বা Fancybox CDN)
2. Bulk action-এ **confirm dialog** দেখাবে ("X জন driver-কে block করতে চান?")
3. Driver detail page tab state URL-এ রাখবে (?tab=documents)
4. Export CSV-এ sensitive data (FCM token) include করবে না
5. Document approve/reject AJAX দিয়ে করবে — page reload ছাড়া

---

## শুরু করো এই order-এ

1. `driver_status_logs` migration add করো
2. Driver model update (relations, scopes, computed attrs)
3. DriverController (index, show, pending, review, approve, reject, changeStatus)
4. DriverDocumentController
5. DriverWalletController
6. Blade views (index → show → review)
7. Routes update
8. Sidebar update (Drivers → All Drivers, Pending Approvals, Expiring Docs)
