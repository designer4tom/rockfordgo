# ReadyRide — Driver App (Flutter)
# Phase 4: Ride + Parcel Order Execution Flow

---

## Context
Phase 1-3 শেষ। Driver order accept করেছে।
এই phase-এ order execution — navigation, status steps, OTP, proof, COD, complete।

Architecture: **MVVM + Provider** | Real-time: **Pusher location broadcast**

---

## 🎨 Design Image Instruction
> Design image দিলে হুবহু follow করবে।

---

## Features

```
features/
├── ride_order/
└── parcel_order/
```

---

## ১. Ride Order Feature

```
features/ride_order/
├── provider/ride_order_provider.dart
├── repository/
│   ├── ride_order_repository.dart
│   └── ride_order_repository_impl.dart
├── model/active_ride_model.dart
└── views/
    ├── screen/
    │   ├── ride_order_screen.dart
    │   └── ride_complete_screen.dart
    └── widgets/
        ├── order_map.dart
        ├── status_action_button.dart
        ├── customer_info_card.dart
        ├── otp_verify_sheet.dart
        └── navigation_button.dart
```

### active_ride_model.dart
```dart
class ActiveRideModel {
  final int orderId;
  final String orderNumber, status, type;
  final CustomerInfo customer;
  final PlaceInfo pickup, drop;
  final String otp;       // driver verifies
  final FareInfo fare;
  final String paymentMethod;
  // fromJson + copyWith
}
```

### ride_order_provider.dart
```dart
class RideOrderProvider extends ChangeNotifier {
  final RideOrderRepository _repository;
  final LocationService _locationService;

  ActiveRideModel? activeRide;
  String currentStatus = 'accepted';

  // Status flow
  // accepted → go_to_pickup → confirm_arrival → picked_up(OTP) → start_ride → dropped_off → completed

  Future<void> loadActiveOrder(int orderId);
  Future<bool> updateStatus(String status, {String? otp});
  Future<bool> verifyOtpAndPickup(String otp);

  // Navigation
  Future<void> openNavigation();  // external Google Maps (url_launcher)

  // Communication
  Future<void> callCustomer();

  Future<bool> completeRide();    // → commission process (backend)
  Future<void> rateCustomer(int rating, String? comment);

  // Location broadcasting (active trip = high frequency)
  void startHighFrequencyTracking();
}
```

### ride_order_screen.dart
```
Layout (Stack):

Map (order_map):
- Driver location (live)
- Current target (pickup বা drop based on status)
- Route polyline
- Navigation button (navigation_button) → external maps

Bottom sheet (status-based):

── Status: accepted / go_to_pickup ──
- Customer info card (name, phone, call button)
- Pickup address
- "Navigate to Pickup" button
- status_action_button: "Arrived at Pickup" → confirm_arrival

── Status: confirm_arrival ──
- "Customer-কে OTP জিজ্ঞাসা করুন"
- status_action_button: "Verify OTP & Start" → otp_verify_sheet

── otp_verify_sheet ──
- OTP input (pinput)
- Verify → picked_up
- Wrong → error

── Status: picked_up / start_ride ──
- Drop address
- "Navigate to Drop" button
- status_action_button: "Start Ride" (if picked_up) → start_ride
- status_action_button: "Complete Ride" (if start_ride) → dropped_off

── Status: dropped_off ──
- Fare summary
- Payment collection:
  - Cash → "Collect ৳XX from customer" + "Cash Received" button
  - Online/Wallet → "Payment auto-processed"
- "Complete" → completed → /ride-complete

SOS button always visible
```

### ride_complete_screen.dart
```
- "Trip সম্পন্ন!"
- Earnings: "আপনি পেলেন ৳XX"
- Fare breakdown (gross, commission, net)
- Customer rating prompt:
  - Star rating
  - Quick tags
  - Submit / Skip
- "Done" → /home
```

### status_action_button.dart
```
Big primary button
Label changes by status
Loading state
Swipe-to-confirm (optional, prevents accidental tap)
```

### navigation_button.dart
```
"Navigate" button → opens Google Maps app
url_launcher: google.navigation:q={lat},{lng}
```

---

## ২. Parcel Order Feature

```
features/parcel_order/
├── provider/parcel_order_provider.dart
├── repository/...
├── model/active_parcel_model.dart
└── views/
    ├── screen/
    │   ├── parcel_order_screen.dart
    │   └── parcel_complete_screen.dart
    └── widgets/
        ├── parcel_info_card.dart
        ├── proof_collection_sheet.dart
        ├── cod_collection_sheet.dart
        └── signature_pad.dart
```

### active_parcel_model.dart
```dart
class ActiveParcelModel {
  final int orderId;
  final String orderNumber, status;
  final PersonInfo sender, receiver;
  final PlaceInfo pickup, drop;
  final ParcelDetails parcel;
  final bool isCod;
  final String? codAmount;
  final String deliveryCharge;
  final String paymentTiming;     // before/after
  final String? proofType;        // otp/photo/signature
  // fromJson
}
```

### parcel_order_provider.dart
```dart
class ParcelOrderProvider extends ChangeNotifier {
  ActiveParcelModel? activeParcel;
  String currentStatus = 'accepted';

  Future<void> loadActiveOrder(int orderId);
  Future<bool> updateStatus(String status);

  // Proof of delivery
  Future<bool> collectProof({
    required String type,
    String? otp,
    File? photo,
    String? signature,  // base64
  });

  // COD
  Future<bool> collectCod(double amount);

  Future<bool> completeDelivery({
    required proof params,
  });

  Future<void> openNavigation();
  Future<void> callSender();
  Future<void> callReceiver();
}
```

### parcel_order_screen.dart
```
Similar to ride কিন্তু:

── go_to_pickup ──
- Sender info card (name, phone, call)
- Pickup address + navigate
- "Arrived" → confirm_arrival

── confirm_arrival / picked_up ──
- Parcel details (parcel_info_card): type, weight, size, note, photo
- "Parcel Picked Up" → picked_up

── start_ride ──
- Receiver info card
- Drop address + navigate
- COD reminder (যদি COD): "Collect ৳XX"
- "Reached Destination" → dropped_off

── dropped_off ──
- Proof collection (proof_collection_sheet):
  - OTP: receiver OTP input
  - Photo: take delivery photo
  - Signature: signature_pad
- COD collection (cod_collection_sheet) — যদি COD:
  - "Collect ৳XX (product + delivery)"
  - "COD Collected" confirm
- After Pay: collect delivery charge
- "Complete Delivery" → completed → /parcel-complete
```

### proof_collection_sheet.dart
```
Based on proofType:
- otp → pinput input
- photo → camera → preview
- signature → signature_pad (draw)
Validate → submit
```

### signature_pad.dart
```
signature package
Draw area + clear + save (base64)
```

### parcel_complete_screen.dart
```
- "Delivery সম্পন্ন!"
- Earning: "৳XX"
- COD info (যদি): collected, sender payout
- "Done" → /home
```

---

## ৩. Active Order Persistence

```
App restart বা crash হলে:
- GET /driver/active-order
- যদি active order থাকে → resume সেই screen-এ
- Status অনুযায়ী সঠিক step দেখাবে
```

---

## ৪. Routing

```dart
GoRoute(path: RouteNames.rideOrder, builder: (_, s) =>
    RideOrderScreen(orderId: s.extra as int)),
GoRoute(path: '/ride-complete', builder: (_, s) =>
    RideCompleteScreen(orderId: s.extra as int)),
GoRoute(path: RouteNames.parcelOrder, builder: (_, s) =>
    ParcelOrderScreen(orderId: s.extra as int)),
GoRoute(path: '/parcel-complete', builder: (_, s) =>
    ParcelCompleteScreen(orderId: s.extra as int)),
```

---

## গুরুত্বপূর্ণ নিয়ম

1. Active trip → **high frequency** location (3-5s) → backend → customer দেখে
2. OTP verify **picked_up**-এর জন্য — admin disable করলে skip
3. Proof of delivery — admin setting অনুযায়ী (otp/photo/signature)
4. COD collect → confirm করার পরে complete
5. Status update **sequential** — skip করা যাবে না
6. External navigation — Google Maps app
7. Complete → earning দেখাবে (backend commission process করে)
8. App restart → active order resume
9. **Swipe-to-confirm** important actions (accidental tap এড়াতে)

---

## Deliverable

- [ ] Ride order feature (model, repo, provider)
- [ ] Ride order screen (status-based flow, OTP, payment)
- [ ] Ride complete + rating
- [ ] Parcel order feature
- [ ] Parcel order screen (proof, COD)
- [ ] Signature pad, proof/COD sheets
- [ ] Active order resume
- [ ] High-frequency location tracking
- [ ] Routing + Providers

---

## শুরু করো এই order-এ

1. Ride order model, repository, provider
2. Order map + navigation button
3. Customer info card, OTP sheet
4. Status action button
5. Ride order screen (all statuses)
6. Ride complete + rating
7. Parcel order model, provider
8. Parcel info card, proof sheet, COD sheet, signature pad
9. Parcel order screen
10. Parcel complete
11. Active order resume logic
12. Routing + Providers
13. Test: Accept ride → navigate → OTP → start → complete → earning → rate
        Accept parcel → pickup → deliver → proof → COD → complete
