# ReadyRide — Driver App 🚗

ReadyRide ড্রাইভারদের জন্য Flutter অ্যাপ — **Ride ও Parcel delivery**। ড্রাইভার online হয়ে real-time order request পায়, navigate করে, OTP/proof/COD নিয়ে trip complete করে, আর earnings/wallet/withdrawal manage করে।

- **Flutter:** 3.41.7 · **Dart:** 3.11.5
- **Architecture:** MVVM + Provider · **API:** Dio · **Routing:** go_router · **Realtime:** Pusher + FCM
- **Live API:** `https://uber.razinsoft.com/api/v1` · **Admin:** https://uber.razinsoft.com/admin/login

---

## 📑 সূচিপত্র
1. [দ্রুত শুরু](#-১-দ্রুত-শুরু-quick-start)
2. [🔴 যা অবশ্যই config করতে হবে](#-২--যা-অবশ্যই-config-করতে-হবে-productionএর-আগে)
3. [🟡 Permissions](#-৩--permissions-configured--verify-করে-নাও)
4. [API & Backend](#-৪--api--backend)
5. [Features (Phase 1–7)](#-৫-features-phase-17)
6. [Project Structure](#-৬-project-structure)
7. [Realtime Architecture](#-৭--realtime-architecture-pusher--fcm)
8. [⚠️ পরিচিত গ্যাপ](#-৮--পরিচিত-গ্যাপ--মনে-রাখার-বিষয়)
9. [💰 Cost Monitoring](#-৯--cost-monitoring-চালু-রাখো)
10. [✅ Production Launch Checklist](#-১০--production-launch-checklist)

---

## 🚀 ১. দ্রুত শুরু (Quick Start)

```bash
# Flutter PATH-এ নেই — প্রতিবার সেট করো
export PATH="$PATH:/Users/abedin/Flutter/flutter/bin"

cd /Users/abedin/Flutter/uber-driver

flutter pub get          # dependencies
flutter devices          # connected device/emulator দেখো
flutter run              # অ্যাপ চালাও (device লাগবে)

flutter build apk --debug      # debug APK
flutter build apk --release    # release APK
flutter analyze                # static analysis
flutter test                   # unit test
```

> ⚠️ `flutter run build` দিও না — `run` আর `build` আলাদা command। একসাথে দিলে **"Target file 'build' not found"** error দেবে।

---

## 🔴 ২.  যা অবশ্যই config করতে হবে (Production-এর আগে)

এই ৪টা না করলে অ্যাপ চলবে কিন্তু পুরো কাজ করবে না।

### ২.১ Google Maps API Key  🗺️
**এখন:** placeholder বসানো — map সাদা/blank দেখাবে (crash করবে না)।

| জায়গা | কী করতে হবে |
|---|---|
| `android/app/src/main/AndroidManifest.xml` | `com.google.android.geo.API_KEY` এর value `YOUR_GOOGLE_MAPS_API_KEY`-এর জায়গায় আসল key |
| `ios/Runner/AppDelegate.swift` | `GMSServices.provideAPIKey("...")` যোগ করতে হবে (এখনো নেই) |
| Backend `/config` | `google_maps_key` field-ও key return করে — চাইলে সেখান থেকে নিতে পারো |

> Google Cloud Console-এ **Maps SDK for Android** + **iOS** enable করো, key-তে package/bundle restriction দাও।

### ২.২ Firebase (Push / FCM)  🔥
**এখন:** `main.dart`-এ `Firebase.initializeApp()` **try/catch দিয়ে guarded** — config না থাকলে crash করে না, শুধু push কাজ করে না।

| জায়গা | কী করতে হবে |
|---|---|
| `android/app/google-services.json` | Firebase Console থেকে download করে রাখো |
| `ios/Runner/GoogleService-Info.plist` | iOS-এর জন্য একইভাবে |
| `android/build.gradle.kts` + `android/app/build.gradle.kts` | google-services Gradle plugin apply (এখন বাদ — config আসার পর enable) |
| `ios/Runner/AppDelegate.swift` | iOS push registration |

> Firebase project-এ **Cloud Messaging** enable করো। FCM token sync **এখনো wire করা হয়নি** (§৮ দেখো)।

### ২.৩ Pusher (Realtime — instant order request)  ⚡
**এখন:** `/config` এর `pusher_key` **খালি**, তাই Pusher off — অ্যাপ FCM backup দিয়ে চলছে। Backend key দিলেই **auto-on**, কোড বদলাতে হবে না।

| জিনিস | অবস্থা / করণীয় |
|---|---|
| `pusher_key`, `pusher_cluster` | Backend `/config` থেকে আসে — Pusher dashboard key বসাতে হবে |
| **`/broadcasting/auth` path** | ⚠️ কোডে call হচ্ছে `{baseUrl}/broadcasting/auth` = `.../api/v1/broadcasting/auth`। Laravel-এ default সাধারণত root-এ: `.../broadcasting/auth`। **Backend কোথায় expose করেছে মিলিয়ে নাও** — না মিললে private channel auth fail করবে। ঠিক করতে: `lib/core/network/pusher_service.dart` |
| Channels | driver: `private-driver.{driverId}` · order: `private-order.{orderId}` |
| Events | `NewOrderRequest`, `OrderRequestCancelled`, `OrderStatusUpdated`, `OrderCancelled` — backend event নামের সাথে হুবহু মিলতে হবে |

### ২.৪ App Icon  🎨
**এখন:** default Flutter icon। `flutter_launcher_icons` + 1024×1024 logo দিয়ে generate করতে হবে।

---

## 🟡 ৩.  Permissions (configured — verify করে নাও)

**Android** (`android/app/src/main/AndroidManifest.xml`): Internet, Network state · Location (FINE, COARSE, **BACKGROUND**, FOREGROUND_SERVICE, FOREGROUND_SERVICE_LOCATION) · Notification (POST_NOTIFICATIONS, WAKE_LOCK, VIBRATE) · Camera

**iOS** (`ios/Runner/Info.plist`): Location (WhenInUse / Always) · Camera · PhotoLibrary · Microphone · `UIBackgroundModes`: location, fetch, remote-notification

**Build:** Android `minSdk` ≥23 · core library desugaring on (flutter_local_notifications)

> ⚠️ Background location-এর জন্য Play Store/App Store review-এ **আলাদা justification** লাগে।

---

## 🌐 ৪.  API & Backend

- **Base URL:** `https://uber.razinsoft.com/api/v1` → `lib/core/constants/app_constants.dart`
- **Response envelope:** `{ "success": true, "message": "...", "data": {...} }`
- **Auth:** Bearer token (key `driver_auth_token`, flutter_secure_storage), 401-এ auto logout
- **Integrated:** **36 / 39 API** — তালিকা `lib/core/constants/api_endpoints.dart`

**এখনো wire করা হয়নি (৩টি):**
| Endpoint | কেন |
|---|---|
| `faqs` | FAQ screen নেই |
| `updateFcmToken` | FCM token পাঠানো হয় না — **push-এর জন্য দরকার** |
| `rideCollectProof` | ride proof OTP দিয়ে `update-status`-এ handle হচ্ছে |

---

## 📱 ৫. Features (Phase 1–7)

| Phase | কী আছে |
|---|---|
| **1 — Core** | Constants, theme (amber), Dio client (token + 401), secure storage, location service (foreground+background, battery-aware), FCM service, Pusher service, routing, shared widgets |
| **2 — Auth** | Splash (status-based redirect), onboarding, OTP login (30s resend), **3-step registration** (personal → documents → vehicle) multipart upload, pending approval. Payout collected later from Profile. Router redirect guard resumes correct screen on restart |
| **3 — Home** | Online/offline toggle (pulse), map, earnings bar, status alerts, drawer, **30s order request popup** (countdown + sound), Pusher foreground + FCM background |
| **4 — Order Exec** | Ride flow (navigate → OTP → start → complete → rate), Parcel flow (proof: OTP/photo/signature + COD), slide-to-confirm, active order resume |
| **5 — Money** | Earnings (fl_chart + period), wallet (balance/due + warning), withdrawal (request + history) |
| **6 — Records** | Documents (status, re-upload, expiry), performance (rating + metrics), history (trips/shifts + detail) |
| **7 — Profile** | Profile/edit/emergency contact/settings, notifications, complaints, **SOS** (ride+parcel screens) |

---

## 🗂️ ৬. Project Structure

```
lib/
├── main.dart                 # entry + guarded Firebase init
├── app.dart                  # MultiProvider + MaterialApp.router
├── core/
│   ├── constants/            # app_constants, api_endpoints, app_colors
│   ├── theme/                # AppTheme (amber)
│   ├── network/              # dio_client, pusher_service, api_response/exception
│   ├── storage/              # secure_storage
│   ├── services/             # location, fcm, sos, config_service
│   ├── routing/              # route_names, app_router
│   ├── models/               # place_info, config_model, vehicle_category
│   ├── providers/            # config_provider
│   ├── utils/                # validators, helpers, snackbar
│   └── widgets/              # button, textfield, loading, empty_state
└── features/                 # প্রতি feature: provider / repository / model / views{screen,widgets}
    ├── splash · onboarding · auth · home · order_request
    ├── ride_order · parcel_order
    └── earnings · wallet · documents · performance · history
        · profile · notification · complaint
```

আরও: `assets/sounds/order_request.wav` (order alert beep)

---

## 🔌 ৭.  Realtime Architecture (Pusher + FCM)

```
App FOREGROUND + driver online  → Pusher (instant, real-time)
App BACKGROUND / KILLED         → FCM (backup notification)
দুটো একই order পাঠালে           → orderId দিয়ে duplicate এড়ানো হয়
```

- **Online** → Pusher connect + `private-driver.{id}` subscribe · **Offline** → unsubscribe
- **Background → Foreground** → auto reconnect + re-subscribe (lifecycle observer)
- **Order accept** → `private-order.{orderId}` subscribe; customer cancel → screen বন্ধ
- **App kill** → FCM backup; tap → request fetch → valid হলে popup, expired হলে message

ফাইল: `lib/core/network/pusher_service.dart`, `lib/core/services/config_service.dart`

---

## ⚠️ ৮.  পরিচিত গ্যাপ / মনে রাখার বিষয়

1. **FCM token sync:** app start-এ token নিয়ে `updateFcmToken` API-তে পাঠানো **হয় না** — push চালুর আগে যোগ করতে হবে।
2. **OTP dev card:** send-otp response-এ `otp` থাকলে OTP screen-এ লাল card-এ দেখায়। Production-এ backend `otp` না পাঠালে card **নিজে থেকেই** উধাও — কোড বদলাতে হবে না।
3. **Pusher off হলেও চলে:** key খালি → FCM backup, crash নেই।
4. **Map key ছাড়া:** map blank, বাকি order flow চলে।
5. **Order sound:** `assets/sounds/order_request.wav` generated beep — custom দিয়ে replace করা যায়।
6. **Release signing:** এখন debug key দিয়ে release হয় (`android/app/build.gradle.kts` TODO) — নিজের keystore লাগবে।
7. **Versions:** 2026-era latest compatible (Dart 3.11 conflict এড়াতে)।
8. **`flutter_background_geolocation` নেই** — `geolocator` background mode (commercial license এড়াতে)।

---

## 💰 ৯.  Cost Monitoring (চালু রাখো)

**Pusher Dashboard:** Concurrent connections (limit-এর কাছে → upgrade) · Messages/day · Peak time

**খরচ বেশি হলে:** location interval বাড়াও (`locationIntervalActive` in `app_constants.dart`, 4s → 5-6s) · পরে **Reverb** (free, self-hosted)-এ migrate সহজ — একই channel/event structure।

---

## ✅ ১০.  Production Launch Checklist

- [ ] Google Maps key (Android + iOS) + SDK enabled
- [ ] `google-services.json` + `GoogleService-Info.plist` + google-services plugin enable
- [ ] iOS push setup (AppDelegate)
- [ ] Backend `/config`-এ Pusher key/cluster
- [ ] `/broadcasting/auth` path frontend ↔ backend মিলানো
- [ ] FCM token sync (`updateFcmToken`) wire করা
- [ ] Backend production-এ send-otp থেকে `otp` সরানো
- [ ] App icon replace
- [ ] Release keystore + signing config
- [ ] Background location store justification
- [ ] Real device full-flow test: Login → Online → Order (Pusher instant) → Complete → Earnings → Withdraw → Documents → Profile

---

<sub>Build status: `flutter analyze` clean · `flutter build apk --debug` passes · `flutter test` passes</sub>
