# ReadyRide — Driver App (Flutter)
# Phase 3: Home + Online Toggle + Order Request (30s Popup)

---

## Context
Phase 1-2 শেষ। Driver approved, home-এ আসে।
এই phase-এ Home, Online/Offline, Location tracking, **Order Request 30s popup** — সবচেয়ে গুরুত্বপূর্ণ।

Architecture: **MVVM + Provider** | Real-time: **Pusher + FCM**

---

## 🎨 Design Image Instruction
> Design image দিলে হুবহু follow করবে।

---

## Features

```
features/
├── home/
└── order_request/
```

---

## ১. Home Feature

```
features/home/
├── provider/home_provider.dart
└── views/
    ├── screen/home_screen.dart
    └── widgets/
        ├── online_toggle.dart
        ├── home_map.dart
        ├── earnings_summary_bar.dart
        ├── status_alerts.dart
        └── home_drawer.dart
```

### home_provider.dart
```dart
class HomeProvider extends ChangeNotifier {
  final HomeRepository _repository;
  final LocationService _locationService;
  final PusherService _pusher;

  bool isOnline = false;
  Position? currentPosition;
  DriverModel? driver;

  // Today summary
  String todayEarning = '0.00';
  int todayTrips = 0;

  // Alerts
  List<String> blockReasons = [];   // online হতে না পারার কারণ
  List<DocumentAlert> expiringDocs = [];
  String dueAmount = '0.00';
  bool dueExceeded = false;

  // Active order (যদি থাকে)
  bool hasActiveOrder = false;

  // Methods
  Future<void> loadDriverData();
  Future<bool> toggleOnline(bool online);   // API + location start
  void _startLocationTracking();            // background, update API + pusher
  void _stopLocationTracking();
  void _subscribeToOrderRequests();         // Pusher driver channel
  Future<void> checkActiveOrder();          // app restart-এ
}
```

### home_screen.dart
```
Layout (Stack):

Map (full) — home_map:
- Driver current location
- Map follows driver

Top:
- Drawer menu icon
- Earnings summary (earnings_summary_bar): "আজ: ৳450 | 8 trips"
- Notification bell

Center/Bottom:
- BIG Online/Offline toggle (online_toggle):
  - Offline → gray "Offline - Tap to go Online"
  - Online → green "Online - Searching for orders"
  - Animated pulse when online

Alerts (status_alerts) — যদি থাকে:
- Document expiring warning
- Due amount warning (লাল যদি exceeded)
- "Online হতে পারবেন না: [reasons]"

If has active order:
- "Active order আছে" banner → tap → resume order
```

### online_toggle.dart
```
Big toggle button/switch
States: offline (gray) / going-online (loading) / online (green pulse)
onToggle → provider.toggleOnline()

Online হতে না পারলে:
- Block reasons dialog দেখাবে
  (document expired / due exceeded)
```

### earnings_summary_bar.dart
```
Compact bar: Today earning + trips
Tap → /earnings (full)
```

### status_alerts.dart
```
Dismissible alert cards:
- Document expiry (yellow/red)
- Due warning (red if exceeded)
```

### home_drawer.dart
```
- Driver profile header (avatar, name, rating, status)
- Earnings
- Wallet & Withdrawal
- Trip History
- Documents
- Performance
- Notifications
- Settings
- Help
- Logout
```

---

## ২. Order Request Feature (CRITICAL — 30s Popup)

```
features/order_request/
├── provider/order_request_provider.dart
├── model/order_request_model.dart
└── views/
    ├── screen/order_request_screen.dart
    └── widgets/
        ├── countdown_timer.dart
        └── request_details_card.dart
```

### order_request_model.dart
```dart
class OrderRequestModel {
  final int orderId;
  final String orderNumber, type;  // ride/parcel
  final PlaceInfo pickup, drop;
  final double distanceKm;
  final String estimatedEarning;
  final String paymentMethod;
  final String? codAmount;         // parcel COD হলে
  final int timeoutSeconds;        // 30
  // fromJson
}
```

### order_request_provider.dart
```dart
class OrderRequestProvider extends ChangeNotifier {
  final OrderRequestRepository _repository;

  OrderRequestModel? currentRequest;
  int remainingSeconds = 30;
  Timer? _countdownTimer;
  AudioPlayer? _player;

  // Pusher থেকে নতুন request এলে
  void showRequest(OrderRequestModel request);
  void _startCountdown();           // 30 → 0
  void _playSound();                // order request sound

  Future<bool> acceptOrder();       // API respond accept
  Future<void> rejectOrder();       // API respond reject
  void _timeout();                  // 30s শেষ → auto dismiss

  void dismiss();
}
```

### Flow — App Foreground (Pusher)
```
1. Driver online, Pusher subscribed
2. NewOrderRequest event আসে
3. order_request_provider.showRequest()
4. Full-screen popup দেখায় (order_request_screen)
5. Sound বাজে
6. 30s countdown শুরু
7. Driver:
   - Accept → API call → success → navigate to order (ride/parcel)
   - Reject → API call → dismiss → back to home
   - কিছু না করলে → 30s পরে auto dismiss (next driver পাবে)
```

### Flow — App Background (FCM)
```
1. FCM high-priority notification আসে
2. Notification-এ click করলে app open
3. order_id দিয়ে request details fetch
4. যদি এখনও valid (30s পার হয়নি):
   → popup দেখাও (remaining time সহ)
5. যদি expired (30s পার):
   → popup দেখাবে না
   → "এই request-এর সময় শেষ" message
   → notification history-তে থাকবে
```

### order_request_screen.dart (Full-screen Popup)
```
Layout (urgent feel):
- Top: countdown_timer (big, circular, 30→0)
  - Color: green → yellow → red as time decreases
- Order type badge (Ride/Parcel)
- request_details_card:
  - Pickup location + distance to pickup
  - Drop location + trip distance
  - Estimated earning (BIG)
  - Payment method
  - COD amount (যদি parcel COD)
- Bottom:
  - "Reject" button (left, outlined)
  - "Accept" button (right, filled, big)

Accept → navigate /ride-order বা /parcel-order
Timeout → auto close
```

### countdown_timer.dart
```
Circular countdown (30s)
Animated progress ring
Color transitions: green→yellow→red
Number in center
```

---

## ৩. Location Tracking Integration

```
Driver online হলে:
- LocationService.startBackgroundTracking()
- প্রতি update-এ:
  → API: POST /driver/update-location
  → (active order থাকলে backend Pusher broadcast করবে customer-কে)

Update frequency:
- Online, no trip: 10s
- Active trip: 3-5s

Driver offline:
- stopBackgroundTracking()
```

---

## ৪. Routing

```dart
GoRoute(path: RouteNames.home, builder: (_, __) => const HomeScreen()),
GoRoute(path: RouteNames.orderRequest, builder: (_, s) =>
    OrderRequestScreen(request: s.extra as OrderRequestModel)),
```

Order request popup — better as **overlay/dialog** than route (instant show)
Use a global navigator key বা overlay entry.

---

## গুরুত্বপূর্ণ নিয়ম

1. **30s timer** precise হতে হবে — accept/reject/timeout সঠিকভাবে
2. Order request **sound** — driver যেন miss না করে
3. App background → FCM → tap → valid হলে popup, expired হলে message
4. Expired request → **notification history**-তে থাকবে
5. Background location — **battery optimized**
6. Online toggle → block reasons (document/due) clear দেখাবে
7. App kill হলেও FCM notification আসবে (high priority)
8. Active order → app restart-এ **resume** হবে

---

## Deliverable

- [ ] Home feature (online toggle, map, earnings, alerts, drawer)
- [ ] Location tracking integration
- [ ] Order Request feature (30s popup, countdown, sound)
- [ ] Foreground (Pusher) + Background (FCM) request handling
- [ ] Expired request → history logic
- [ ] Routing + Providers

---

## শুরু করো এই order-এ

1. Home provider + repository
2. Home screen (map, online toggle, earnings, alerts)
3. Home drawer
4. Online toggle + location tracking integration
5. Order request model, provider
6. Countdown timer widget
7. Order request screen (popup)
8. Pusher foreground handling
9. FCM background handling (valid/expired logic)
10. Routing + Providers
11. Test: Go online → receive request (Pusher) → accept/reject/timeout
        Background → FCM → tap → popup/expired
