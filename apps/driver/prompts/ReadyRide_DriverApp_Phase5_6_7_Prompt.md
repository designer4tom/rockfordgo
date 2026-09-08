# ReadyRide — Driver App (Flutter)
# Phase 5-6-7: Earnings/Wallet + Documents/Performance + Profile/Engagement

---

## Context
Phase 1-4 শেষ — Auth, Home, Order execution ready।
এই শেষ phases-এ Earnings, Wallet, Withdrawal, Documents, Performance, History, Profile।

Architecture: **MVVM + Provider**

---

## 🎨 Design Image Instruction
> Design image দিলে হুবহু follow করবে।

---
---

# PHASE 5: Earnings + Wallet + Withdrawal

## Features

```
features/
├── earnings/
└── wallet/
```

### ৫.১ Earnings Feature
```
features/earnings/
├── provider/earnings_provider.dart
├── repository/...
├── model/earnings_model.dart
└── views/
    ├── screen/earnings_screen.dart
    └── widgets/
        ├── earnings_summary_card.dart
        ├── earnings_chart.dart
        └── period_selector.dart
```

```dart
class EarningsModel {
  final String period;
  final String totalEarning, rideEarning, parcelEarning;
  final String commissionPaid, tipsReceived;
  final int totalTrips;
  final String onlineHours;
}

class EarningsProvider extends ChangeNotifier {
  EarningsModel? earnings;
  Map<String, dynamic>? summary;  // today/week/month/lifetime
  List<ChartData> chartData = [];
  String period = 'today';

  Future<void> loadEarnings(String period);
  Future<void> loadSummary();
  Future<void> loadChart(String period);  // fl_chart data
  void setPeriod(String period);
}
```

```
earnings_screen.dart:
- Period selector (today/week/month/all)
- Summary card: total earning (big), trips, online hours
- Breakdown: ride vs parcel, commission, tips
- Chart (earnings_chart — fl_chart): daily earning bars/line
- Summary cards: today/week/month/lifetime
```

### ৫.২ Wallet Feature
```
features/wallet/
├── provider/wallet_provider.dart
├── repository/...
├── model/
│   ├── wallet_model.dart
│   ├── transaction_model.dart
│   └── withdrawal_model.dart
└── views/
    ├── screen/
    │   ├── wallet_screen.dart
    │   ├── withdrawal_screen.dart
    │   └── withdrawal_history_screen.dart
    └── widgets/
        ├── balance_due_card.dart
        ├── transaction_tile.dart
        └── withdrawal_tile.dart
```

```dart
class WalletProvider extends ChangeNotifier {
  WalletModel? wallet;       // balance, due, due_limit, can_accept
  List<TransactionModel> transactions = [];
  List<WithdrawalModel> withdrawals = [];

  Future<void> loadWallet();
  Future<void> loadTransactions({bool refresh});
  Future<bool> requestWithdrawal(double amount, String method, String account);
  Future<void> loadWithdrawalHistory();
}
```

```
wallet_screen.dart:
- Balance + Due card (balance_due_card):
  - Wallet balance (big)
  - Due amount (red যদি > 0)
  - Due limit progress bar
  - "Withdraw" button
  - Warning যদি due exceeded: "Due limit ছাড়িয়ে গেছে, order পাবেন না"
- Transaction history (transaction_tile)
- Pagination

withdrawal_screen.dart:
- Available balance
- Amount input (min withdrawal amount)
- Method: bKash/Nagad/Bank (saved from registration)
- "Request Withdrawal" → submit
- Note: "Admin approve করলে টাকা পাবেন"

withdrawal_history_screen.dart:
- List: amount, method, status (pending/approved/rejected), date
- Rejected → reason
```

---
---

# PHASE 6: Documents + Performance + History

## Features

```
features/
├── documents/
├── performance/
└── history/
```

### ৬.১ Documents Feature
```
features/documents/
├── provider/documents_provider.dart
├── repository/...
├── model/document_model.dart
└── views/
    ├── screen/documents_screen.dart
    └── widgets/document_status_card.dart
```

```dart
class DocumentModel {
  final String type, typeLabel;
  final String status;       // pending/approved/rejected/expired/expiring_soon
  final String? expiryDate;
  final String? rejectionReason;
  final int? daysUntilExpiry;
}

class DocumentsProvider extends ChangeNotifier {
  List<DocumentModel> documents = [];

  Future<void> loadDocuments();
  Future<bool> updateDocument({
    required String type,
    required File file,
    File? backFile,
    DateTime? expiryDate,
  });
}
```

```
documents_screen.dart:
- All documents list (document_status_card):
  - Type + status badge
  - Expiry date (red if expiring/expired)
  - Days until expiry
  - Rejected → reason + "Re-upload"
  - Expired → "Update Now"
- Update flow → image picker → expiry → submit → pending

Expiry alerts prominent (banner if expiring)
```

### ৬.২ Performance Feature
```
features/performance/
├── provider/performance_provider.dart
├── model/performance_model.dart
└── views/screen/performance_screen.dart
```

```dart
class PerformanceModel {
  final String averageRating;
  final int totalRatings;
  final Map<String, int> ratingBreakdown;  // 5:180, 4:50...
  final String acceptanceRate, completionRate, cancellationRate;
  final List<RecentRating> recentRatings;
}
```

```
performance_screen.dart:
- Rating overview (big star + average)
- Rating breakdown (5★ to 1★ bars)
- Metrics: Acceptance, Completion, Cancellation (progress bars)
- Recent ratings list (with comments)
```

### ৬.৩ History Feature
```
features/history/
├── provider/history_provider.dart
├── model/order_model.dart
└── views/
    ├── screen/
    │   ├── history_screen.dart
    │   └── order_detail_screen.dart
    └── widgets/
        ├── order_tile.dart
        └── shift_tile.dart
```

```dart
class HistoryProvider extends ChangeNotifier {
  List<OrderModel> orders = [];
  List<ShiftModel> shifts = [];
  String filterType = 'all';

  Future<void> loadOrders({bool refresh});
  Future<void> loadShifts();   // shift history
  Future<OrderDetailModel> getOrderDetail(int id);
}
```

```
history_screen.dart:
- Tabs: Trips | Shifts
- Trips: order list (filter ride/parcel), earnings per trip
- Shifts: date, online/offline time, hours, trips, earning
- Order detail → full breakdown
```

---
---

# PHASE 7: Profile + Notifications + SOS + Settings

## Features

```
features/
├── profile/
├── notification/
└── complaint/
```

### ৭.১ Profile Feature
```
features/profile/
├── provider/profile_provider.dart
├── repository/...
└── views/screen/
    ├── profile_screen.dart
    ├── edit_profile_screen.dart
    ├── emergency_contact_screen.dart
    └── settings_screen.dart
```

```dart
class ProfileProvider extends ChangeNotifier {
  DriverModel? driver;

  Future<void> loadProfile();
  Future<bool> updateProfile({String? name, String? email, File? avatar});
  Future<bool> updateWithdrawalInfo({...});
  Future<bool> updateEmergencyContact({...});
  Future<void> logout();
  Future<bool> deleteAccount(String? reason);  // due check
}
```

```
profile_screen.dart:
- Header: avatar, name, phone, rating, status badge
- Vehicle info
- Menu:
  - Edit Profile
  - Vehicle & Documents
  - Emergency Contact
  - Withdrawal Account
  - Earnings
  - Performance
  - Help & Support
  - Settings
  - Logout
  - Delete Account (due থাকলে block)

settings_screen.dart:
- Language
- Notification preferences
- Navigation app preference (in-app/Google Maps)
- Location tracking info
- Privacy/Terms
- App version
```

### ৭.২ Notification Feature
```
features/notification/
├── provider/notification_provider.dart
├── model/notification_model.dart
└── views/screen/notification_screen.dart
```

```dart
class NotificationProvider extends ChangeNotifier {
  List<NotificationModel> notifications = [];
  int unreadCount = 0;

  Future<void> loadNotifications({bool refresh});
  Future<void> markRead(List<int> ids);
  void handleFcm(RemoteMessage message);
}
```

```
notification_screen.dart:
- List: title, body, time, unread dot
- Types: order updates, payment, document expiry, due warning, withdrawal, broadcast
- Expired order requests show here (Phase 3 reference)
- Mark all read
```

### ৭.৩ Complaint Feature
```
features/complaint/
├── provider/complaint_provider.dart
└── views/screen/
    ├── complaint_list_screen.dart
    └── create_complaint_screen.dart
```

```
create_complaint_screen.dart:
- Order selector
- Category
- Description
- Submit

complaint_list_screen.dart:
- My complaints + status
```

### ৭.৪ SOS (integrate in order screens)
```
core/services/sos_service.dart
- triggerSos(orderId, lat, lng)
- Used in ride_order, parcel_order screens
- Confirm → API → success
```

---

## Routing (All remaining)

```dart
GoRoute(path: RouteNames.earnings, builder: (_, __) => const EarningsScreen()),
GoRoute(path: RouteNames.wallet, builder: (_, __) => const WalletScreen()),
GoRoute(path: RouteNames.withdrawal, builder: (_, __) => const WithdrawalScreen()),
GoRoute(path: '/withdrawal-history', builder: (_, __) => const WithdrawalHistoryScreen()),
GoRoute(path: RouteNames.documents, builder: (_, __) => const DocumentsScreen()),
GoRoute(path: RouteNames.performance, builder: (_, __) => const PerformanceScreen()),
GoRoute(path: RouteNames.history, builder: (_, __) => const HistoryScreen()),
GoRoute(path: RouteNames.orderDetail, builder: (_, s) => OrderDetailScreen(orderId: s.extra as int)),
GoRoute(path: RouteNames.notifications, builder: (_, __) => const NotificationScreen()),
GoRoute(path: RouteNames.profile, builder: (_, __) => const ProfileScreen()),
GoRoute(path: '/edit-profile', builder: (_, __) => const EditProfileScreen()),
GoRoute(path: '/emergency-contact', builder: (_, __) => const EmergencyContactScreen()),
GoRoute(path: RouteNames.settings, builder: (_, __) => const SettingsScreen()),
GoRoute(path: '/complaints', builder: (_, __) => const ComplaintListScreen()),
GoRoute(path: '/create-complaint', builder: (_, __) => const CreateComplaintScreen()),
```

---

## গুরুত্বপূর্ণ নিয়ম

1. Due exceeded → wallet-এ **prominent warning** + online block
2. Document expiry → **banner alert** + auto-block যদি expired
3. Earnings chart → **fl_chart** দিয়ে clean visualization
4. Withdrawal → saved account info pre-fill
5. Delete account → **pending due check** (থাকলে block)
6. Notification → expired order requests-ও দেখাবে
7. SOS → সব order screen-এ accessible

---

## Final Deliverable (Phase 5-7)

### Phase 5
- [ ] Earnings feature (summary, chart, period)
- [ ] Wallet feature (balance, due, transactions)
- [ ] Withdrawal (request, history)

### Phase 6
- [ ] Documents feature (status, re-upload, expiry)
- [ ] Performance feature (ratings, metrics)
- [ ] History feature (trips, shifts)

### Phase 7
- [ ] Profile feature (edit, emergency, settings, delete)
- [ ] Notification feature + FCM
- [ ] Complaint feature
- [ ] SOS service

---

## শুরু করো এই order-এ

**Phase 5:**
1. Earnings model, repo, provider, screen + chart
2. Wallet model, repo, provider, screen
3. Withdrawal screens

**Phase 6:**
4. Documents feature (status, re-upload)
5. Performance feature
6. History feature (trips + shifts)

**Phase 7:**
7. Profile feature (edit, emergency, settings, delete)
8. Notification feature
9. Complaint feature
10. SOS service integrate

**Final:**
11. সব routing compile
12. সব provider registration
13. Full flow test: Login → Online → Order → Complete → Earnings → Withdraw → Documents → Profile
14. App icon, splash, build config (Android + iOS)
15. Background location permission setup (Android manifest + iOS Info.plist)
