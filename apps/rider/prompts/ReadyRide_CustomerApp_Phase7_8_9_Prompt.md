# ReadyRide — Customer App (Flutter)
# Phase 7-8-9: Wallet + History + Profile/Engagement

---

## Context
Phase 1-6 শেষ — Auth, Home, Booking, Tracking ready।
এই শেষ phases-এ Wallet, Payment, History, Notifications, Profile, Referral, SOS।

Architecture: **MVVM + Provider** | Payment: **flutter_stripe**

---

## 🎨 Design Image Instruction
> Design image দিলে হুবহু follow করবে।

---
---

# PHASE 7: Wallet + Payment

## Feature

```
features/wallet/
├── provider/wallet_provider.dart
├── repository/
│   ├── wallet_repository.dart
│   └── wallet_repository_impl.dart
├── model/
│   ├── wallet_model.dart
│   └── transaction_model.dart
└── views/
    ├── screen/
    │   ├── wallet_screen.dart
    │   └── topup_screen.dart
    └── widgets/
        ├── balance_card.dart
        ├── transaction_tile.dart
        └── topup_amount_selector.dart
```

### Models
```dart
class WalletModel {
  final String balance, currency, currencySymbol;
}

class TransactionModel {
  final int id;
  final String type;       // credit/debit
  final String category, categoryLabel;
  final String amount, balanceAfter;
  final String? note, orderNumber;
  final String createdAt;
}
```

### wallet_provider.dart
```dart
class WalletProvider extends ChangeNotifier {
  WalletModel? wallet;
  List<TransactionModel> transactions = [];
  PaginationMeta? meta;

  Future<void> loadBalance();
  Future<void> loadTransactions({bool refresh = false});  // paginated
  Future<String?> initiateTopup(double amount);  // returns client_secret
  Future<bool> confirmTopup(String paymentIntentId);
}
```

### wallet_screen.dart
```
- Balance card (balance_card): large balance + "Top Up" button
- Transaction history (transaction_tile list):
  - Icon (credit ↑ green / debit ↓ red)
  - Category label + note
  - Amount (+/-)
  - Date
- Pagination (infinite scroll)
- Pull to refresh
- Empty state
```

### topup_screen.dart
```
- Amount selector (topup_amount_selector):
  Quick: ৳100, ৳200, ৳500, ৳1000, Custom
- "Proceed to Pay" button
- Stripe flow:
  1. initiateTopup(amount) → client_secret
  2. Stripe.instance.confirmPayment (CardField)
  3. confirmTopup(paymentIntentId)
  4. Success → balance update + back
```

### Stripe Integration
```dart
// flutter_stripe setup
// main.dart: Stripe.publishableKey = config.stripe_publishable_key
// CardField widget for card input
// PaymentSheet বা manual confirmation
```

---
---

# PHASE 8: History + Invoice

## Feature

```
features/history/
├── provider/history_provider.dart
├── repository/...
├── model/order_model.dart
└── views/
    ├── screen/
    │   ├── history_screen.dart
    │   ├── order_detail_screen.dart
    │   └── invoice_screen.dart
    └── widgets/
        ├── order_tile.dart
        └── order_filter.dart
```

### Models
```dart
class OrderModel {
  final int id;
  final String orderNumber, type, status;
  final String pickupAddress, dropAddress;
  final String totalAmount, paymentMethod;
  final String? driverName, driverAvatar;
  final String createdAt;
  final String? completedAt;
}
```

### history_provider.dart
```dart
class HistoryProvider extends ChangeNotifier {
  List<OrderModel> orders = [];
  String filterType = 'all';  // all/ride/parcel
  PaginationMeta? meta;

  Future<void> loadOrders({bool refresh = false});
  void setFilter(String type);
  Future<OrderDetailModel> getOrderDetail(int id);
}
```

### history_screen.dart
```
- Filter tabs (order_filter): All / Ride / Parcel
- Order list (order_tile):
  - Type icon + order number
  - Route (pickup → drop)
  - Amount + status badge
  - Date
  - Driver name
- Tap → /order-detail
- Ongoing order → top, highlighted
- Pagination + pull to refresh
```

### order_detail_screen.dart
```
- Order header (number, status, date)
- Route map (static)
- Driver info (যদি থাকে)
- Fare breakdown
- Payment info
- Timeline (status history)
- Actions:
  - "Rebook" (same route)
  - "Invoice" → /invoice
  - "Complaint" (যদি issue)
```

### invoice_screen.dart
```
- Formatted invoice view
- Invoice number, date
- Customer + Driver
- Route + fare breakdown
- "Download PDF" → API pdf endpoint → save/share
- "Share" → share_plus
```

---
---

# PHASE 9: Notifications + Profile + Referral + SOS + Settings

## Features

```
features/
├── notification/
├── profile/
├── referral/
├── favourite/
└── complaint/
```

### ৯.১ Notification Feature
```
model/notification_model.dart
provider/notification_provider.dart
views/screen/notification_screen.dart
views/widgets/notification_tile.dart
```

```dart
class NotificationProvider extends ChangeNotifier {
  List<NotificationModel> notifications = [];
  int unreadCount = 0;

  Future<void> loadNotifications({bool refresh = false});
  Future<void> markRead(List<int> ids);
  Future<void> markAllRead();
  void handleFcmMessage(RemoteMessage message);  // foreground notification
}
```

```
notification_screen.dart:
- List (notification_tile): title, body, time, unread dot
- Tap → relevant screen (order detail ইত্যাদি from data)
- "Mark all read"
- Pagination
```

#### FCM Setup (core)
```dart
// core/services/fcm_service.dart
class FcmService {
  Future<void> init();              // permission, token
  Future<String?> getToken();
  void onMessage();                 // foreground → local notification
  void onMessageOpenedApp();        // tap → navigate
  void onBackgroundMessage();       // handler
}
```

### ৯.২ Profile Feature
```
model/ — UserModel (reuse from auth)
provider/profile_provider.dart
views/screen/
  ├── profile_screen.dart
  ├── edit_profile_screen.dart
  └── settings_screen.dart
```

```dart
class ProfileProvider extends ChangeNotifier {
  UserModel? user;

  Future<void> loadProfile();
  Future<bool> updateProfile({String? name, String? email, File? avatar});
  Future<void> logout();
  Future<bool> deleteAccount(String? reason);
  Future<void> updateEmergencyContact({...});
}
```

```
profile_screen.dart:
- Avatar + name + phone
- Menu items:
  - Edit Profile
  - Emergency Contact
  - My Wallet
  - Trip History
  - Favourite Locations
  - Referral
  - Settings
  - Help & Support
  - About
  - Logout (confirm)
  - Delete Account (confirm + warning)

settings_screen.dart:
- Language (যদি multiple)
- Notification preferences (toggle)
- Privacy Policy (webview/page)
- Terms & Conditions
- App version
```

### ৯.৩ Favourite Locations Feature
```
model/favourite_model.dart
provider/favourite_provider.dart
views/screen/favourite_screen.dart
views/widgets/favourite_tile.dart
```

```dart
class FavouriteProvider extends ChangeNotifier {
  List<FavouriteModel> favourites = [];

  Future<void> loadFavourites();
  Future<bool> addFavourite({required String label, String? customLabel, required PlaceModel place});
  Future<bool> updateFavourite(int id, {...});
  Future<bool> deleteFavourite(int id);
}
```

```
favourite_screen.dart:
- List: Home, Office, custom
- Add new (label + location picker)
- Edit/Delete swipe actions
```

### ৯.৪ Referral Feature
```
model/referral_model.dart
provider/referral_provider.dart
views/screen/referral_screen.dart
```

```
referral_screen.dart:
- Referral code (big, copy button)
- Share button (share_plus)
- Stats: Total referred, Total earned
- Referred users list
- "How it works" info
```

### ৯.৫ Complaint Feature
```
model/complaint_model.dart
provider/complaint_provider.dart
views/screen/
  ├── complaint_list_screen.dart
  └── create_complaint_screen.dart
```

```
create_complaint_screen.dart:
- Order selector (recent orders)
- Category dropdown (driver_behavior/overcharging/...)
- Description textarea
- Submit

complaint_list_screen.dart:
- My complaints + status
```

### ৯.৬ SOS (integrate everywhere)
```
core/services/sos_service.dart
- triggerSos(orderId, lat, lng)
- Used in tracking screens
- Confirm dialog → API call → success message
```

---

## Routing (All remaining)

```dart
GoRoute(path: RouteNames.wallet, builder: (_, __) => const WalletScreen()),
GoRoute(path: '/topup', builder: (_, __) => const TopupScreen()),
GoRoute(path: RouteNames.history, builder: (_, __) => const HistoryScreen()),
GoRoute(path: RouteNames.orderDetail, builder: (_, s) => OrderDetailScreen(orderId: s.extra as int)),
GoRoute(path: '/invoice', builder: (_, s) => InvoiceScreen(orderId: s.extra as int)),
GoRoute(path: RouteNames.notifications, builder: (_, __) => const NotificationScreen()),
GoRoute(path: RouteNames.profile, builder: (_, __) => const ProfileScreen()),
GoRoute(path: '/edit-profile', builder: (_, __) => const EditProfileScreen()),
GoRoute(path: RouteNames.settings, builder: (_, __) => const SettingsScreen()),
GoRoute(path: RouteNames.favourites, builder: (_, __) => const FavouriteScreen()),
GoRoute(path: RouteNames.referral, builder: (_, __) => const ReferralScreen()),
GoRoute(path: '/complaints', builder: (_, __) => const ComplaintListScreen()),
GoRoute(path: '/create-complaint', builder: (_, __) => const CreateComplaintScreen()),
```

---

## গুরুত্বপূর্ণ নিয়ম

1. **Stripe** — publishable key config থেকে, secret কখনো app-এ না
2. FCM **foreground** → local notification দেখাবে
3. FCM **tap** → relevant screen navigate (data payload থেকে)
4. Wallet/History **pagination** — infinite scroll
5. Delete account **double confirm** + warning
6. Notification bell **unread badge** — home + drawer-এ
7. Invoice PDF **download** → temp save → share/open

---

## Final Deliverable (Phase 7-9)

### Phase 7
- [ ] Wallet feature (balance, transactions, topup)
- [ ] Stripe integration

### Phase 8
- [ ] History feature (list, filter, detail)
- [ ] Invoice (view, PDF download)

### Phase 9
- [ ] Notification feature + FCM service
- [ ] Profile feature (edit, settings, delete account)
- [ ] Favourite locations feature
- [ ] Referral feature
- [ ] Complaint feature
- [ ] SOS service (integrated)

---

## শুরু করো এই order-এ

**Phase 7:**
1. Wallet model, repo, provider
2. Wallet screen + topup + Stripe

**Phase 8:**
3. History model, repo, provider
4. History + order detail + invoice screens

**Phase 9:**
5. FCM service (core)
6. Notification feature
7. Profile feature (edit, settings, delete)
8. Favourite locations
9. Referral
10. Complaint
11. SOS service integrate

**Final:**
12. সব routing compile
13. সব provider registration
14. Full app flow test: Login → Book → Track → Complete → History → Wallet → Profile
15. App icon, splash, build config (Android + iOS)
