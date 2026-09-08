# ReadyRide — Claude Code Master Instruction
# পর্যায় ৫: Customer Management

---

## Context
পর্যায় ১-৪ শেষ। এই পর্যায়ে Customer Management বানাবো।

---

## এই পর্যায়ে যা করবে

1. Customer List (filter, search, export)
2. Customer Details Page
3. Customer Wallet Management
4. Customer Block / Unblock
5. Referral Management

---

## ১. Customer List Page

```
Route: GET /admin/customers
Permission: users, read

Filters:
- Search: নাম বা ফোন নম্বর
- Status: All / Active / Blocked
- Date Range: joined from-to
- Has Wallet Balance: Yes / No

Table columns:
- Avatar + নাম (link to detail)
- ফোন
- Email (nullable)
- Wallet Balance (৳)
- Total Trips
- Total Parcels
- Referral Code
- Status badge (Active/Blocked)
- Joined Date
- Actions: View, Block/Unblock

Stats row:
Total: X | Active: X | Blocked: X | Total Wallet Balance: ৳X

Export: CSV button

Pagination: 20 per page
```

---

## ২. Customer Details Page

```
Route: GET /admin/customers/{id}
Permission: users, read

Tab-based layout:

── Tab 1: Profile ──────────────────────────────
- Avatar, নাম, ফোন, Email
- Status badge + Block/Unblock button
- Wallet Balance (large display)
- Referral Code
- Referred By (যদি থাকে — link to referrer)
- Joined date, Last active

── Tab 2: Trip & Parcel History ────────────────
Table (paginated, 10 per page):
- Order#, Type (Ride/Parcel), Date
- Pickup → Drop (short)
- Amount, Payment Method
- Status badge
- Driver নাম
- Click → order detail

Filter: All / Ride / Parcel / Date range

── Tab 3: Wallet ────────────────────────────────
- Current Balance (large)
- "Manual Adjustment" button (admin add/deduct)
- Transaction History table:
  Date | Category | Description | Amount (+/-) | Balance After

Categories displayed nicely:
trip_earning → "Trip Payment"
refund → "Refund"
referral_bonus → "Referral Bonus"
top_up → "Wallet Top-up"

── Tab 4: Complaints & Disputes ────────────────
- Customer-এর সব disputes list
- Status, Order#, Category, Date
- Click → dispute detail

── Tab 5: Referrals ─────────────────────────────
- Referral Code (with copy button)
- Total Referred: X জন
- Total Bonus Earned: ৳X
- Referred users list:
  নাম, ফোন, Joined Date, Bonus Status (pending/rewarded)
```

---

## ৩. Customer Wallet Management

```
Manual Wallet Adjustment:
Route: POST /admin/customers/{id}/wallet-adjust
Permission: payments, write

Modal form:
- Type: Add / Deduct (radio)
- Amount (decimal, required, min 1)
- Reason (textarea, required)
- Confirmation checkbox

Logic:
- Add: wallet_balance += amount, credit transaction
- Deduct: wallet_balance -= amount (min 0), debit transaction
- wallet_transactions-এ record
- Customer-কে notification

Manual Refund:
Route: POST /admin/customers/{id}/refund
Permission: payments, write

Form:
- Order ID (optional — refund কোন order-এর জন্য)
- Amount
- Reason
→ wallet_balance += amount
→ wallet_transactions (category: refund)
→ Customer notification
```

---

## ৪. Customer Block / Unblock

```
Route: POST /admin/customers/{id}/toggle-block
Permission: users, write

Block করলে:
- users.is_active = false
- Customer app-এ login করতে পারবে না
- Ongoing trip থাকলে warning দেখাবে

Unblock করলে:
- users.is_active = true

Log রাখবে (customer_status_logs — নতুন migration):
id, user_id, changed_by (FK admins), old_status, new_status, reason, created_at
```

---

## ৫. Referral Overview Page

```
Route: GET /admin/referrals
Permission: users, read

Summary Cards:
- Total Referrals: X
- Rewarded: X
- Pending: X
- Total Bonus Paid: ৳X

Table:
- Referrer (নাম + ফোন)
- Referee (নাম + ফোন)
- Referrer Bonus
- Referee Bonus
- Status (Pending/Rewarded)
- Date

Filter: Status, Date range
```

---

## New Migration

```php
// customer_status_logs
Schema::create('customer_status_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('changed_by')->constrained('admins');
    $table->boolean('old_status');
    $table->boolean('new_status');
    $table->text('reason')->nullable();
    $table->timestamps();
});
```

---

## File Structure

```
app/Http/Controllers/Admin/
  CustomerController.php
  CustomerWalletController.php
  ReferralController.php

resources/views/admin/
  customers/
    index.blade.php
    show.blade.php
    edit.blade.php
  referrals/
    index.blade.php
```

---

## Routes

```php
Route::prefix('customers')->name('customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
    Route::post('/{id}/toggle-block', [CustomerController::class, 'toggleBlock'])
         ->name('toggle-block');
    Route::post('/{id}/wallet-adjust', [CustomerWalletController::class, 'adjust'])
         ->name('wallet-adjust');
    Route::post('/{id}/refund', [CustomerWalletController::class, 'refund'])
         ->name('refund');
    Route::get('/export/csv', [CustomerController::class, 'export'])->name('export');
});

Route::get('referrals', [ReferralController::class, 'index'])->name('referrals.index');
```

---

## গুরুত্বপূর্ণ নিয়ম

1. Customer-এর ফোন নম্বর list-এ masked দেখাবে: 017****890 (privacy)
2. Wallet deduct করার সময় balance check — 0-এর নিচে যাবে না
3. Block করার আগে ongoing order check — থাকলে warning

---

## শুরু করো এই order-এ

1. `customer_status_logs` migration
2. User model update (relations, scopes)
3. CustomerController
4. CustomerWalletController
5. ReferralController
6. Blade views
7. Routes + Sidebar update
