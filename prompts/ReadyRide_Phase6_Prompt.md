# ReadyRide — Claude Code Master Instruction
# পর্যায় ৬: Order Management

---

## Context
পর্যায় ১-৫ শেষ। এই পর্যায়ে সম্পূর্ণ Order Management বানাবো।

---

## এই পর্যায়ে যা করবে

1. Order List (filter, search, export)
2. Order Details Page (full lifecycle)
3. Order Timeline Tracking
4. Order Map View
5. Manual Order Intervention
6. Coupon Management
7. Scheduled Orders

---

## ১. Order List Page

```
Route: GET /admin/orders
Permission: orders, read

Filters:
- Search: Order# বা Customer ফোন বা Driver ফোন
- Type: All / Ride / Parcel
- Status: All / pending / accepted / ongoing / completed / cancelled / rejected
  (ongoing = accepted + go_to_pickup + confirm_arrival + picked_up + start_ride + dropped_off)
- Payment Method: All / Cash / Online / Wallet / COD
- Payment Status: All / Paid / Pending / Failed
- Zone: All / specific
- Date Range
- Is COD: All / Yes / No
- Is Scheduled: All / Yes / No

Table columns:
- Order # (link to detail)
- Type badge (Ride 🚗 / Parcel 📦)
- Customer (নাম + ফোন)
- Driver (নাম + ফোন / "Searching...")
- Pickup → Drop (truncated)
- Amount (৳)
- Payment (method badge)
- Status (colored badge)
- Created At
- Actions: View, (Cancel if active)

Color-coded status badges:
scheduled    → purple
pending      → yellow (pulsing)
accepted     → blue
go_to_pickup → blue
confirm_arrival → blue
picked_up    → indigo
start_ride   → indigo
dropped_off  → teal
completed    → green
cancelled    → red
rejected     → gray

Stats row:
Today: X orders | Completed: X | Cancelled: X | Revenue: ৳X

Export: CSV

Pagination: 20 per page
```

---

## ২. Order Details Page

```
Route: GET /admin/orders/{id}
Permission: orders, read

Layout: Single page, sections

── Section 1: Order Header ─────────────────────
Order # | Type badge | Status badge
Created: [datetime] | Last updated: [datetime]
[Cancel Order] button (if active) | [Reassign Driver] button (if active)

── Section 2: People ───────────────────────────
Left card — Customer:
- Avatar, নাম, ফোন
- Link to customer detail

Right card — Driver:
- Avatar, নাম, ফোন
- Vehicle: Make Model (Reg#)
- Rating
- Link to driver detail
- "Reassign" button

── Section 3: Locations & Map ──────────────────
- Full-width Google Map (400px height)
- Pickup pin (green)
- Drop pin (red)
- Route line (if available from order_locations)
- Driver's current location (blue, if ongoing)
- Multi-stops (if any)

Below map:
Pickup: [full address]
Drop: [full address]
Distance: X km | Duration: X min

── Section 4: Parcel Info (if parcel) ──────────
Sender: নাম, ফোন
Receiver: নাম, ফোন
Parcel: Type, Weight, Size
Photo: thumbnail (lightbox)
Note: [note]
COD: Yes/No | COD Amount: ৳X
Payment Timing: Before/After
Proof of Delivery: [proof data or "Not collected"]

── Section 5: Fare Breakdown ───────────────────
Table:
Item                    | Amount
------------------------|--------
Base Fare               | ৳XX
Distance Charge (X km)  | ৳XX
Time Charge (X min)     | ৳XX
Surge (1.5x)            | ৳XX
Delivery Charge         | ৳XX
Tip                     | ৳XX
Subtotal                | ৳XX
Coupon Discount         | -৳XX
─────────────────────────|────────
Total                   | ৳XX
Admin Commission (X%)   | ৳XX
Driver Earning          | ৳XX

Payment: [method] | Status: [status badge]
Payment Intent ID: [stripe id if online]

── Section 6: Order Timeline ───────────────────
Vertical timeline:
● scheduled      [datetime] — "Scheduled for [scheduled_at]"
● pending        [datetime] — "Looking for driver"
● accepted       [datetime] — "Driver [name] accepted"
● go_to_pickup   [datetime] — "Driver heading to pickup"
● confirm_arrival [datetime] — "Driver arrived at pickup"
● picked_up      [datetime] — "OTP verified / Parcel picked up"
● start_ride     [datetime] — "Ride/Delivery started"
● dropped_off    [datetime] — "Reached destination"
● completed      [datetime] — "Order completed"
  (or)
● cancelled      [datetime] — "Cancelled by [user/driver/admin]: [reason]"

Current status highlighted, future steps grayed out.

── Section 7: Rating & Review ──────────────────
Customer's rating for driver: ★★★★☆ [comment]
Driver's rating for customer: ★★★★★

── Section 8: Dispute (if any) ─────────────────
Dispute status, category, description
Admin note
Refund issued: Yes/No
[View Full Dispute] link
```

---

## ৩. Manual Order Intervention

```
Cancel Order:
Route: POST /admin/orders/{id}/cancel
Permission: orders, write

Modal:
- Reason (required)
- Refund Customer? (toggle — if paid online/wallet)
- Refund Amount (auto-fill from total_amount, editable)

Logic:
- order.status = cancelled, cancelled_by = admin
- cancelled_at = now()
- If refund: wallet_balance += amount, transaction record
- Driver-কে notification
- Customer-কে notification

Reassign Driver:
Route: POST /admin/orders/{id}/reassign
Permission: orders, write

Modal:
- Current Driver info
- New Driver search (typeahead — approved, online, same zone)
- Reason

Logic:
- order.driver_id = new driver
- Old driver notification: "Trip reassigned"
- New driver notification: "New trip assigned"

Force Complete:
Route: POST /admin/orders/{id}/force-complete
Permission: orders, write

- Stuck order-কে complete করা
- Reason required
- order.status = completed, completed_at = now()
```

---

## ৪. Coupon Management

```
Route: GET /admin/coupons
Permission: settings, read

Table:
- Code (copy button)
- Discount Type (% বা Fixed ৳)
- Discount Value
- Max Discount
- Valid: from - until
- Service Type (Ride/Parcel/All)
- Used: X / Limit
- Status toggle
- Actions: Edit, Delete

"নতুন Coupon" button

Create/Edit Coupon:
Route: GET  /admin/coupons/create
Route: POST /admin/coupons
Route: GET  /admin/coupons/{id}/edit
Route: PUT  /admin/coupons/{id}
Permission: settings, write

Form:
- Code (required, uppercase, auto-generate button)
- Description
- Discount Type: Percentage / Fixed Amount (radio)
- Discount Value (decimal, required)
- Max Discount (decimal, shown only if percentage — optional)
- Min Order Amount (decimal, optional)
- Usage Limit (integer, optional — blank = unlimited)
- Per User Limit (integer, default 1)
- Valid From (date, required)
- Valid Until (date, required)
- Service Type: Ride / Parcel / All
- Status: Active / Inactive

Coupon Usage History:
Route: GET /admin/coupons/{id}/usages

Table:
- Customer (নাম + ফোন)
- Order #
- Discount Given
- Date
```

---

## ৫. Scheduled Orders

```
Route: GET /admin/orders/scheduled
Permission: orders, read

Table (upcoming scheduled orders):
- Order #
- Customer
- Service Type
- Scheduled For (datetime — highlighted if < 1 hour)
- Pickup → Drop
- Status (scheduled / driver_assigned)
- Amount
- Actions: View, Cancel

Auto-assignment:
- Scheduled time এর `driver_assign_before_minutes` আগে background job চলবে
- Job: ScheduleOrderAssignment (Laravel Scheduler)
- Nearby drivers-কে request পাঠাবে

Laravel Scheduler (app/Console/Kernel.php):
$schedule->job(new AssignScheduledOrders)->everyMinute();
```

---

## ৬. Order Export

```
CSV export fields:
Order#, Type, Status, Customer Name, Customer Phone,
Driver Name, Driver Phone, Pickup, Drop, Distance,
Total Amount, Admin Commission, Driver Earning,
Payment Method, Payment Status, Created At, Completed At

Route: GET /admin/orders/export
Permission: orders, read
Uses Laravel Excel or manual CSV response
```

---

## File Structure

```
app/
  Http/Controllers/Admin/
    OrderController.php
    OrderInterventionController.php
    CouponController.php
    ScheduledOrderController.php
  Jobs/
    AssignScheduledOrders.php
  Console/
    Kernel.php (update)

resources/views/admin/
  orders/
    index.blade.php
    show.blade.php
    scheduled.blade.php
  coupons/
    index.blade.php
    create.blade.php
    edit.blade.php
    usages.blade.php
  components/
    order-status-badge.blade.php
    order-timeline.blade.php
    fare-breakdown.blade.php
```

---

## Routes

```php
Route::prefix('orders')->name('orders.')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/scheduled', [ScheduledOrderController::class, 'index'])
         ->name('scheduled');
    Route::get('/export', [OrderController::class, 'export'])->name('export');
    Route::get('/{id}', [OrderController::class, 'show'])->name('show');
    Route::post('/{id}/cancel', [OrderInterventionController::class, 'cancel'])
         ->name('cancel');
    Route::post('/{id}/reassign', [OrderInterventionController::class, 'reassign'])
         ->name('reassign');
    Route::post('/{id}/force-complete', [OrderInterventionController::class, 'forceComplete'])
         ->name('force-complete');
});

Route::resource('coupons', CouponController::class);
Route::get('coupons/{id}/usages', [CouponController::class, 'usages'])
     ->name('coupons.usages');
```

---

## গুরুত্বপূর্ণ নিয়ম

1. Order Map-এ Google Maps Key না থাকলে static address দেখাবে
2. Timeline-এ শুধু completed steps দেখাবে (future steps gray)
3. Cancel করার আগে driver assignment check
4. COD order cancel করলে: COD amount reconciliation note দেখাবে
5. Ongoing order-এ "Force Complete" শুধু Super Admin করতে পারবে

---

## শুরু করো এই order-এ

1. Order model relations/scopes update
2. OrderController (index, show, export)
3. OrderInterventionController
4. CouponController
5. ScheduledOrderController + AssignScheduledOrders Job
6. Blade views
7. Routes + Sidebar update (Orders → All Orders, Scheduled, Disputes)
