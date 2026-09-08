# ReadyRide — Customer App (Flutter)
# Phase 3: Home + Service Selection + Map

---

## Context
Phase 1-2 শেষ — Core + Auth ready। User login করে /home-এ আসে।
এই phase-এ Home screen, Map, Service selection বানাবো।

Architecture: **MVVM + Provider** | API: **Dio** | Map: **google_maps_flutter**

---

## 🎨 Design Image Instruction
> Design image দিলে হুবহু follow করবে। না দিলে নিচের layout reference।

---

## Features

```
features/
├── home/
├── location/        # location service, geocoding
└── service/         # service & vehicle data
```

---

## ১. Location Feature (Core dependency)

```
features/location/
├── provider/location_provider.dart
├── repository/
│   ├── location_repository.dart
│   └── location_repository_impl.dart
└── model/
    └── place_model.dart
```

### place_model.dart
```dart
class PlaceModel {
  final String address;
  final double lat;
  final double lng;
  final String? placeId;

  PlaceModel({required this.address, required this.lat, required this.lng, this.placeId});

  factory PlaceModel.fromJson(Map<String, dynamic> json) {...}
}
```

### location_provider.dart
```dart
class LocationProvider extends ChangeNotifier {
  // Current device location
  Position? _currentPosition;
  PlaceModel? _currentPlace;
  bool _hasPermission = false;

  // Methods
  Future<bool> requestPermission();       // geolocator permission
  Future<Position?> getCurrentLocation(); // GPS
  Future<PlaceModel?> reverseGeocode(double lat, double lng);  // API call
  Future<List<PlaceModel>> searchPlaces(String query);         // API call
  Future<void> getCurrentPlace();         // GPS + reverse geocode
}
```

### Repository methods
```dart
// API proxy (backend Google Places ব্যবহার করে)
Future<List<PlaceModel>> searchPlaces(String query, {double? lat, double? lng});
Future<PlaceModel> reverseGeocode(double lat, double lng);
```

---

## ২. Service Feature

```
features/service/
├── provider/service_provider.dart
├── repository/
│   ├── service_repository.dart
│   └── service_repository_impl.dart
└── model/
    ├── service_model.dart
    └── vehicle_category_model.dart
```

### Models
```dart
class ServiceModel {
  final int id;
  final String name, slug, type;
  final String? icon, description;
  // fromJson
}

class VehicleCategoryModel {
  final int id;
  final String name;
  final String? icon;
  final int capacity;
  final String baseFare, perKmRate, perMinuteRate, minimumFare;
  final int estimatedArrival, availableDrivers;
  final bool surgeActive;
  final double surgeMultiplier;
  // fromJson
}
```

### service_provider.dart
```dart
class ServiceProvider extends ChangeNotifier {
  List<ServiceModel> _services = [];
  List<VehicleCategoryModel> _vehicleCategories = [];

  Future<void> loadServices();
  Future<void> loadVehicleCategories(double lat, double lng);
}
```

---

## ৩. Home Feature

```
features/home/
├── provider/home_provider.dart
└── views/
    ├── screen/
    │   └── home_screen.dart
    └── widgets/
        ├── home_map.dart
        ├── service_selector.dart
        ├── location_search_bar.dart
        ├── favourite_shortcuts.dart
        └── home_drawer.dart
```

### home_screen.dart
```
Layout (Stack-based):

Bottom layer — Google Map (full screen):
- Current location centered
- Nearby drivers markers (custom icon)
- My location button

Top overlay:
- App bar (transparent):
  - Menu icon (drawer)
  - Wallet balance chip
  - Notification bell (badge)

Bottom sheet (draggable):
- "কোথায় যাবেন?" search bar
- Service tabs: [🚗 Ride] [📦 Parcel]
- Favourite shortcuts: [🏠 বাসা] [🏢 অফিস]
- Recent locations list

Tapping search bar → /set-destination
Tapping Ride → ride flow
Tapping Parcel → /parcel-booking
```

### home_map.dart
```
- GoogleMap widget
- Initial camera: current location
- Markers: nearby drivers (from API, refresh every 10s)
- Custom driver marker icon (bike/car)
- My location enabled
- onCameraMove → center pin (pickup location)
```

### home_drawer.dart
```
Drawer menu:
- User profile header (avatar, name, phone)
- Wallet (balance)
- My Trips (history)
- Favourite Locations
- Referral
- Notifications
- Settings
- Help & Support
- Logout
```

### service_selector.dart
```
Two tabs: Ride | Parcel
Selected tab highlighted
onChanged callback
```

### location_search_bar.dart
```
Tappable bar (not actual input on home)
"কোথায় যাবেন?" placeholder
Tap → opens /set-destination (full search screen)
```

### home_provider.dart
```dart
class HomeProvider extends ChangeNotifier {
  List<DriverMarker> _nearbyDrivers = [];
  PlaceModel? _pickupLocation;
  String _selectedService = 'ride';
  Timer? _driverRefreshTimer;

  Future<void> loadNearbyDrivers(double lat, double lng);
  void startDriverRefresh(double lat, double lng);  // every 10s
  void selectService(String type);
  void setPickup(PlaceModel place);
}
```

---

## ৪. Set Destination Screen

```
features/home/views/screen/set_destination_screen.dart
features/home/views/widgets/place_search_result.dart
```

### Layout
```
- AppBar: back + "গন্তব্য নির্বাচন করুন"
- Pickup field (editable, default current location)
- Drop field (focused, search input)
- "Map-এ select করুন" button → map picker
- Search results list (live as typing):
  - Place name + address
  - Tap → select
- Favourite locations shortcuts
- Recent locations

Both pickup ও drop set হলে → /vehicle-select (ride)
```

### Map Picker (alternate)
```
features/home/views/screen/map_picker_screen.dart
- Full screen map
- Center fixed pin
- Move map → pin stays center, address updates (reverse geocode)
- "এই location confirm করুন" button
```

---

## ৫. Routing Update

```dart
GoRoute(path: RouteNames.home, builder: (_, __) => const HomeScreen()),
GoRoute(path: RouteNames.setDestination, builder: (_, __) => const SetDestinationScreen()),
GoRoute(path: '/map-picker', builder: (_, __) => const MapPickerScreen()),
```

---

## ৬. Provider Registration

```dart
// Location
Provider<LocationRepository>(create: (ctx) => LocationRepositoryImpl(ctx.read())),
ChangeNotifierProvider(create: (ctx) => LocationProvider(ctx.read())),

// Service
Provider<ServiceRepository>(create: (ctx) => ServiceRepositoryImpl(ctx.read())),
ChangeNotifierProvider(create: (ctx) => ServiceProvider(ctx.read())),

// Home
ChangeNotifierProvider(create: (ctx) => HomeProvider(ctx.read(), ctx.read())),
```

---

## গুরুত্বপূর্ণ নিয়ম

1. **Location permission** না দিলে — manual address search fallback
2. Google Maps Key **config থেকে** নেবে (AndroidManifest + Info.plist-এ inject)
3. Nearby drivers **10 সেকেন্ডে** refresh, screen leave করলে timer cancel
4. Map marker **custom icon** — bike/car আলাদা
5. Search **debounce** (300ms) — প্রতি keystroke-এ API call না
6. Current location **cache** — বারবার GPS call না

---

## Deliverable

- [ ] Location feature (permission, GPS, geocode, search)
- [ ] Service feature (services, vehicle categories)
- [ ] Home screen (map + bottom sheet + drawer)
- [ ] Set Destination screen (search + results)
- [ ] Map Picker screen
- [ ] Nearby drivers on map
- [ ] Routing + Provider registration

---

## শুরু করো এই order-এ

1. Location feature (model, repo, provider)
2. Service feature
3. Home map widget
4. Home screen + bottom sheet
5. Home drawer
6. Set Destination screen + search
7. Map Picker screen
8. Routing + Providers
9. Test: Home map shows, search works, select destination → vehicle select placeholder
