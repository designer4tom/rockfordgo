# ReadyRide — Claude Code Master Instruction
# পর্যায় ৭: Payment, Wallet ও Commission Management

---

## Context
পর্যায় ১-৬ শেষ। এই পর্যায়ে সম্পূর্ণ Payment system বানাবো।

---

## এই পর্যায়ে যা করবে

1. Transaction Overview
2. Withdrawal Management
3. Refund Management
4. COD Reconciliation
5. Stripe Settings
6. Revenue Summary
7. WalletService (core business logic)

---

## ১. WalletService — Core Business Logic

```php
// app/Services/WalletService.php
// এটা সব wallet operation handle করবে

class WalletService
{
    // Driver wallet-এ credit করা
    public function creditDriver(
        Driver $driver,
        float $amount,
        string $category,
        ?int $orderId = null,
        ?string $note = null
    ): WalletTransaction

    // Driver wallet থেকে debit
    public function debitDriver(
        Driver $driver,
        float $amount,
        string $category,
        ?int $orderId = null,
        ?string $note = null
    ): WalletTransaction

    // Customer wallet-এ credit
    public function creditUser(
        User $user,
        float $amount,
        string $category,
        ?int $orderId = null,
        ?string $note = null
    ): WalletTransaction

    // Customer wallet থেকে debit
    public function debitUser(
        User $user,
        float $amount,
        string $category,
        ?int $orderId = null,
        ?string $note = null
    ): WalletTransaction

    // Driver due add করা
    public function addDriverDue(
        Driver $driver,
        float $amount,
        ?int $orderId = null,
        ?string $note = null
    ): DueTransaction

    // Driver due pay করা
    public function payDriverDue(
        Driver $driver,
        float $amount,
        ?string $note = null
    ): DueTransaction

    // Trip complete হলে commission split
    public function processTripCommission(Order $order): array
    // returns: [admin_amount, driver_amount]
    // Logic:
    //   Online payment: driver wallet credit, admin records
    //   Cash payment: commission due check করো
    //     - wallet balance >= commission → debit wallet
    //     - wallet balance < commission → add to due
    //     - due > due_limit → driver block new orders

    // Parcel delivery commission split
    public function processParcelCommission(Order $order): array

    // COD collection process
    public function processCodCollection(Order $order, float $collectedAmount): array
    // Logic:
    //   product_price → sender wallet credit
    //   delivery_charge → split (admin % + driver %)
    //   driver must have product_price in wallet as guarantee

    // Due limit check
    public function checkDueLimit(Driver $driver): bool
    // returns true if driver can accept orders

    // Refund to customer
    public function refundToCustomer(
        User $user,
        float $amount,
        ?int $orderId = null,
        string $reason = ''
    ): WalletTransaction
}
```

---

## ২. Transaction Overview Page

```
Route: GET /admin/payments/transactions
Permission: payments, read

Summary Cards (top):
- Total Revenue (completed orders sum)
- Today's Revenue
- Total Commission Earned
- Pending Withdrawals Amount
- Total Refunds Issued

Filters:
- Date Range
- Type: All / Credit / Debit
- Category: All / trip_earning / commission / withdrawal / refund / etc.
- Owner Type: All / Driver / Customer
- Amount Range (min-max)

Table:
- ID
- Owner (নাম + type badge: Driver/Customer)
- Order # (link, if any)
- Type (Credit ↑ / Debit ↓ — colored)
- Category (readable label)
- Amount (green for credit, red for debit)
- Balance After
- Note
- Date

Export: CSV

Pagination: 25 per page
```

---

## ৩. Withdrawal Management

```
Route: GET /admin/payments/withdrawals
Permission: payments, read

Tabs:
- Pending (count badge)
- Approved
- Rejected
- All

Table:
- Driver (নাম + ফোন + link)
- Amount (৳)
- Method (Bank/bKash/Nagad badge)
- Account Details (masked)
- Requested At
- Status badge
- Actions (Pending tab): Approve / Reject

Approve Modal:
Route: POST /admin/payments/withdrawals/{id}/approve
Permission: payments, write

- Show: Driver name, Amount, Method, Account
- Confirm button
- On approve:
  → withdrawal.status = approved, processed_by, processed_at
  → driver.wallet_balance -= amount
  → wallet_transaction (debit, category: withdrawal)
  → Driver notification: "Withdrawal of ৳X approved"

Reject Modal:
Route: POST /admin/payments/withdrawals/{id}/reject
Permission: payments, write

- Rejection Reason (required)
- On reject:
  → withdrawal.status = rejected
  → Driver notification: "Withdrawal rejected: [reason]"

Bulk Approve:
- Select multiple pending → Approve All button
```

---

## ৪. Refund Management

```
Route: GET /admin/payments/refunds
Permission: payments, read

Table:
- Order # (link)
- Customer (নাম + ফোন)
- Order Amount
- Refund Amount
- Reason
- Issued By (admin name)
- Date

"নতুন Refund Issue" button:
Route: POST /admin/payments/refunds

Form:
- Customer search (typeahead by phone)
- Order # (optional)
- Amount (decimal, required)
- Reason (required)
- Confirmation

Logic:
→ customer wallet credit
→ wallet_transaction (credit, category: refund)
→ Customer notification
→ Dispute-এর সাথে link থাকলে dispute.refund_issued = true
```

---

## ৫. COD Reconciliation

```
Route: GET /admin/payments/cod-reconciliation
Permission: payments, read

Summary:
- Total COD Collected (by drivers)
- Total Settled (sent to senders)
- Pending Settlement

Table:
- Order # (link)
- Driver (নাম)
- Sender (নাম)
- COD Amount (receiver দিয়েছে)
- Delivery Charge
- Sender Payout (COD - Delivery Charge)
- Status: Settled / Pending
- Date

Filter: Date range, Driver, Status

Export CSV
```

---

## ৬. Due Management Overview

```
Route: GET /admin/payments/driver-dues
Permission: payments, read

Summary Cards:
- Total Due Amount (সব drivers)
- Drivers with Due (count)
- Drivers at Limit (count) — danger

Table:
- Driver (নাম + ফোন)
- Current Due (৳)
- Due Limit (৳)
- Progress bar (due/limit %)
- Last Due Added (date)
- Actions: Clear Due, View History

Filter: Due > 0 only (default), All

Clear Due Modal:
Route: POST /admin/drivers/{id}/clear-due (already in Phase 4)
- Amount to clear (default: full due, editable)
- Reason required
```

---

## ৭. Stripe Settings Page

```
Route: GET  /admin/settings/payment
Route: POST /admin/settings/payment
Permission: settings, write

Form sections:

Section: Stripe Configuration
- Stripe Publishable Key (input, masked display)
- Stripe Secret Key (input, masked display, show/hide toggle)
- Stripe Webhook Secret (input, masked)
- Test Mode toggle (test vs live keys)
- "Test Connection" button → API call to verify key
- Webhook URL (read-only, auto-generated): https://domain.com/webhook/stripe

Section: Payment Methods
- Online Payment (Card) Enable/Disable
- Wallet Payment Enable/Disable
- Cash Payment Enable/Disable

Section: Wallet Settings
- Allow Wallet Top-up: Yes/No
- Minimum Top-up Amount
- Maximum Top-up Amount

Save → system_settings update + cache clear

"Test Connection" AJAX:
Route: POST /admin/settings/payment/test-stripe
→ Simple Stripe API call (list payment methods)
→ Return success/fail JSON
```

---

## ৮. Revenue Summary / Report

```
Route: GET /admin/payments/revenue
Permission: reports, read

Filters:
- Date Range (default: this month)
- Group By: Day / Week / Month

Summary Cards:
- Gross Revenue (total order amounts)
- Admin Commission Earned
- Driver Payouts
- Refunds Issued
- Net Revenue (Gross - Refunds)

Chart: Revenue over time (Line chart)

Breakdown Table:
Date | Rides | Parcels | Gross | Commission | Refunds | Net
```

---

## File Structure

```
app/
  Services/
    WalletService.php
    StripeService.php
  Http/Controllers/Admin/
    Payments/
      TransactionController.php
      WithdrawalController.php
      RefundController.php
      CodReconciliationController.php
      DueController.php
      RevenueController.php
    Settings/
      PaymentSettingsController.php

resources/views/admin/
  payments/
    transactions/index.blade.php
    withdrawals/index.blade.php
    refunds/index.blade.php
    cod-reconciliation/index.blade.php
    driver-dues/index.blade.php
    revenue/index.blade.php
  settings/
    payment.blade.php
```

---

## Routes

```php
Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('transactions', [TransactionController::class, 'index'])
         ->name('transactions');
    Route::get('transactions/export', [TransactionController::class, 'export'])
         ->name('transactions.export');

    Route::get('withdrawals', [WithdrawalController::class, 'index'])
         ->name('withdrawals');
    Route::post('withdrawals/{id}/approve', [WithdrawalController::class, 'approve'])
         ->name('withdrawals.approve');
    Route::post('withdrawals/{id}/reject', [WithdrawalController::class, 'reject'])
         ->name('withdrawals.reject');
    Route::post('withdrawals/bulk-approve', [WithdrawalController::class, 'bulkApprove'])
         ->name('withdrawals.bulk-approve');

    Route::get('refunds', [RefundController::class, 'index'])->name('refunds');
    Route::post('refunds', [RefundController::class, 'store'])->name('refunds.store');

    Route::get('cod-reconciliation', [CodReconciliationController::class, 'index'])
         ->name('cod-reconciliation');

    Route::get('driver-dues', [DueController::class, 'index'])->name('driver-dues');

    Route::get('revenue', [RevenueController::class, 'index'])->name('revenue');
});

Route::get('settings/payment', [PaymentSettingsController::class, 'index'])
     ->name('settings.payment');
Route::post('settings/payment', [PaymentSettingsController::class, 'update'])
     ->name('settings.payment.update');
Route::post('settings/payment/test-stripe', [PaymentSettingsController::class, 'testStripe'])
     ->name('settings.payment.test-stripe');
```

---

## গুরুত্বপূর্ণ নিয়ম

1. **WalletService** সব জায়গায় ব্যবহার করবে — direct DB update করবে না
2. Wallet operations **Database Transaction** দিয়ে wrap করবে (`DB::transaction`)
3. Stripe Secret Key encrypted store করবে (`encrypt()/decrypt()`)
4. Withdrawal approve করার আগে driver wallet balance check
5. Due limit exceed হলে driver-এর `can_accept_orders` flag আপডেট করবে

---

## শুরু করো এই order-এ

1. WalletService বানাও (সবচেয়ে important)
2. StripeService বানাও
3. Controllers (Transaction, Withdrawal, Refund, Due, Revenue)
4. PaymentSettingsController
5. Blade views
6. Routes + Sidebar update
