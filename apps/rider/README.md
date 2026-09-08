# ReadyRide — Customer App (Flutter)

ReadyRide-এর কাস্টমার অ্যাপ — **Ride booking** আর **Parcel delivery** এক জায়গায়। Laravel API-এর সাথে যুক্ত, real-time tracking সহ।

- **Architecture:** MVVM + Provider
- **Networking:** Dio
- **Routing:** go_router
- **Maps:** google_maps_flutter • **Real-time:** Pusher • **Payment:** Stripe • **Push:** Firebase

---

## সূচিপত্র
1. [Features](#features)
2. [Architecture & Folder Structure](#architecture--folder-structure)
3. [Getting Started](#getting-started)
4. [⚙️ Setup & Config (গুরুত্বপূর্ণ)](#️-setup--config-গুরুত্বপূর্ণ)
5. [Build / Run সাবধানতা](#build--run-সাবধানতা)
6. [Backend Payload Contract](#backend-payload-contract)
7. [Launch চেকলিস্ট](#launch-চেকলিস্ট)
8. [ফাইল রেফারেন্স](#ফাইল-রেফারেন্স)

---

## Features

| Module | কী করে |
|---|---|
| **Auth** | Splash redirect, onboarding, OTP login, profile সম্পূর্ণ করা |
| **Home** | Google Map, current location, service (ride/parcel) সিলেকশন, drawer |
| **Ride** | gন্তব্য → vehicle select → fare/coupon/payment → book → driver search |
| **Parcel** | ৫-ধাপের flow: sender → receiver → details → COD → confirm |
| **Tracking** | Pusher real-time — live driver map, OTP, SOS, complete, rating |
| **Wallet** | balance, transactions, Stripe top-up |
| **History** | filter, order detail, invoice (PDF/share) |
| **Profile** | edit, settings, favourite locations, referral, complaint, delete account |
| **Notification** | list + FCM push |

---

## Architecture & Folder Structure

প্রতিটা feature **MVVM + Provider + Repository** প্যাটার্ন মেনে চলে। সব API call repository-তে, provider শুধু state রাখে, error `ApiException` দিয়ে handle হয়।

```
lib/
├── main.dart                # entry — Stripe init, runApp
├── app.dart                 # MultiProvider + _AppRoot (startup + lifecycle)
├── core/
│   ├── constants/           # app_constants, api_endpoints, app_colors
│   ├── theme/               # app_theme, app_text_styles
│   ├── network/             # dio_client, pusher_service, api_response, api_exception
│   ├── services/            # config_service, route_service, fcm_service, sos_service
│   ├── storage/             # secure_storage (token)
│   ├── routing/             # app_router, route_names
│   ├── utils/               # validators, helpers, snackbar_helper
│   └── widgets/             # custom_button, custom_textfield, loading, empty_state
└── features/
    └── <feature>/
        ├── provider/
        ├── repository/      # abstract + impl
        ├── model/
        └── views/
            ├── screen/
            └── widgets/
```

Feature modules: `auth · onboarding · splash · home · location · service · ride · parcel · tracking · wallet · history · notification · profile · favourite · referral · complaint · config`

---

## Getting Started

```bash
flutter pub get
flutter run        # real device / emulator-এ চালাও
```

> ভারী native plugin (Maps/Stripe/Firebase) emulator বা real device-এ best চলে।

**এক লাইনে:** কোড রেডি। শুধু **API key + backend settings** ঠিকমতো বসালেই পুরো অ্যাপ কাজ করবে। নিচের সেকশন দেখো।

---

## ⚙️ Setup & Config (গুরুত্বপূর্ণ)

### ০. এক নজরে — কোন key কোথায়, কীসের জন্য

| Key | কোথায় বসাতে হবে | কীসের জন্য | যে Google API enable লাগবে |
|---|---|---|---|
| **Google Maps** | ৩ জায়গায় (নিচে) | ম্যাপ, location, route | Maps SDK + Directions + Geocoding + Places |
| **Pusher** | শুধু **backend** | real-time tracking | — (অ্যাপ `/config` থেকে নেয়) |
| **Stripe publishable** | `app_constants.dart` (বা backend `/config`) | wallet top-up | — |
| **Firebase** | google-services file | push notification | — |

> 💡 চারটার মধ্যে শুধু **Google Maps key** অ্যাপে সরাসরি বসাতে হয়। বাকিগুলো backend `/config` থেকে আসে বা platform file-এ থাকে।

---

### ১. Google Maps Key (সবচেয়ে গুরুত্বপূর্ণ)

**১.১ এক key, ৫টা API enable** — [Google Cloud Console](https://console.cloud.google.com) → তোমার project → **APIs & Services → Library** → enable করো:
- Maps SDK for Android
- Maps SDK for iOS
- **Directions API** ← road-following polyline
- **Geocoding API** ← current location-এর address
- **Places API** ← address search (autocomplete)

**১.২ Restriction (সাবধানতা)** — Credentials → key → **Application restrictions = None** (ডেভেলপমেন্টে)।
- "Android apps" restriction দিলে backend-এর server call (geocode/search) **block হবে**।
- Production-এ চাইলে আলাদা করো: Android-restricted key (ম্যাপ) + unrestricted/IP-restricted server key (backend)।

**১.৩ অ্যাপে কোথায় — ৩ জায়গা:**

| জায়গা | ফাইল |
|---|---|
| Android | `android/app/src/main/AndroidManifest.xml` → `com.google.android.geo.API_KEY` |
| iOS | `ios/Runner/AppDelegate.swift` → `GMSServices.provideAPIKey(...)` |
| Flutter | `lib/core/constants/app_constants.dart` → `googleMapsApiKey` (Directions-এর জন্য) |

> ⚠️ **Google Maps key backend থেকে runtime-এ নেওয়া যায় না** — native SDK অ্যাপ launch-এর সময়েই key পড়ে (তখন `/config` আসেনি)। তাই native config-এ hardcode করতেই হবে। তিন জায়গায় **একই key** চলবে।

---

### ২. Backend Settings (অর্ধেক feature এর উপর নির্ভর)

Admin panel (SystemSetting) বা `.env`-এ বসাও:

| Setting | কীসের জন্য | না থাকলে |
|---|---|---|
| `google_maps_key` | reverse-geocode + search (server-side) | address আসবে না, search 503 |
| `pusher_key` / `pusher_cluster` | অ্যাপ `/config` থেকে নিয়ে socket connect করে | real-time নেই, polling fallback চলবে |
| `pusher_app_id` / `pusher_secret` | server থেকে event broadcast | accept/location broadcast হবে না |

`.env` দিয়ে করলে:
```env
GOOGLE_MAPS_API_KEY=AIza...
GOOGLE_MAPS_SERVER_KEY=AIza...
```
তারপর `php artisan config:clear`।

> server-side Maps key-এ **Geocoding API + Places API** enable, restriction **None/IP** (referer/Android নয়)।

---

### ৩. Real-time (Pusher) — backend-এ ৪টা জিনিস

অ্যাপের দিক রেডি (config থেকে key, Sanctum auth, typed events)। Backend-এ নিশ্চিত করো:
1. `/config` যেন `pusher_key` + `pusher_cluster` রিটার্ন করে।
2. **`/broadcasting/auth`** route আছে, `auth:sanctum` middleware সহ।
3. `routes/channels.php`-এ `private-order.{id}` channel authorize করা।
4. Event class-এ `broadcastAs()` ঠিক এই নাম: `DriverAccepted`, `DriverLocationUpdated`, `OrderStatusUpdated`, `OrderCompleted`, `OrderCancelled` — channel `private-order.{orderId}`।

> socket fail করলে অ্যাপ নিজে থেকে **5s polling fallback**-এ চলে যায় — কখনো আটকাবে না।

---

### ৪. Stripe (wallet top-up)
- Publishable key: `lib/core/constants/app_constants.dart` → `stripePublishableKey`।
- **Secret key কখনো অ্যাপে না** — শুধু backend-এ।

### ৫. Firebase (push) — এখন optional/guarded (config ছাড়া crash করবে না)
1. `flutterfire configure` → `google-services.json` + `GoogleService-Info.plist` + `firebase_options.dart`।
2. `lib/main.dart`-এ uncomment:
   ```dart
   await Firebase.initializeApp();
   FcmService().init();
   ```

### ৬. Base URL / Environment
`lib/core/constants/app_constants.dart` → `baseUrl`:

| পরিবেশ | মান |
|---|---|
| Local (ফোন, একই Wi-Fi) | `http://192.168.x.x:8000/api/v1` (মেশিনের LAN IP) |
| Android emulator | `http://10.0.2.2:8000/api/v1` |
| iOS sim / web | `http://127.0.0.1:8000/api/v1` |
| Production | `https://api.readyride.com/api/v1` |

> ফোন থেকে `localhost` কাজ করবে না — মেশিনের LAN IP দাও।

---

## Build / Run সাবধানতা

- **Android desugaring** — `flutter_local_notifications`-এর জন্য `android/app/build.gradle.kts`-এ enable করা (`isCoreLibraryDesugaringEnabled = true` + `desugar_jdk_libs`)। সরাবে না।
- native config (Manifest/key) বদলালে → **`flutter clean && flutter run`**।
- প্রথম `flutter run` Gradle download-এ কয়েক মিনিট নিতে পারে।

### Dev vs Production — নিজে থেকে হয়
| জিনিস | Dev | Production |
|---|---|---|
| **OTP** | backend response-এ আসে → OTP screen-এ লাল card | response-এ থাকে না → card **নিজে থেকে hide** |

> backend OTP পাঠানো বন্ধ করলেই card চলে যাবে — অ্যাপে কিছু বদলাতে হবে না।

---

## Backend Payload Contract

অ্যাপ backend-এর **flat field** format মেনে পাঠায়:
- ride/parcel: `pickup_lat`, `pickup_lng`, `pickup_address`, `drop_lat`, `drop_lng`, `drop_address`
- parcel: `parcel_photo`, `parcel_note`
- geocode search: `q`

> backend validation নাম বদলালে মেলাতে হবে:
> `ride_repository_impl.dart`, `parcel_repository_impl.dart`, `location_repository_impl.dart`।

**জানা সীমাবদ্ধতা:** backend text-search (`/geocode/search`) শুধু address + place_id দেয়, **lat/lng দেয় না**। তাই —
- ✅ **"Map এ select করুন"** ব্যবহার করো (reverse-geocode coordinate দেয়)।
- অথবা backend-এ **place-details endpoint** (place_id → lat/lng) যোগ করলে text-search-ও পুরো কাজ করবে।

---

## Launch চেকলিস্ট

- [ ] Google key-এর project-এ ৫টা API enable + restriction None
- [ ] Google key বসানো: AndroidManifest + AppDelegate + app_constants
- [ ] Backend admin-এ `google_maps_key` সেট
- [ ] Backend admin-এ `pusher_key/cluster/app_id/secret` সেট
- [ ] Backend-এ `/broadcasting/auth` + `channels.php` + ৫টা broadcast event
- [ ] Stripe publishable key (top-up লাগলে)
- [ ] (optional) Firebase configure + main.dart init uncomment
- [ ] `baseUrl` সঠিক পরিবেশের জন্য
- [ ] `flutter clean && flutter run` real device-এ
- [ ] Production build-এ OTP card আসছে না নিশ্চিত করো

---

## ফাইল রেফারেন্স

| বিষয় | ফাইল |
|---|---|
| সব key/flag fallback | `lib/core/constants/app_constants.dart` |
| API endpoint strings | `lib/core/constants/api_endpoints.dart` |
| remote config fetch | `lib/core/services/config_service.dart` |
| Pusher (real-time) | `lib/core/network/pusher_service.dart` |
| Directions / polyline | `lib/core/services/route_service.dart` |
| Dio + auth/401 | `lib/core/network/dio_client.dart` |
| app startup + lifecycle | `lib/app.dart` (`_AppRoot`) |

---

<sub>Flutter help: [docs.flutter.dev](https://docs.flutter.dev/)</sub>
