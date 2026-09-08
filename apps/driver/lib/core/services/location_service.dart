import 'dart:async';

import 'package:geolocator/geolocator.dart';

import '../constants/app_constants.dart';

/// Foreground + background location tracking for the driver.
///
/// Battery strategy:
///  - Online, no trip  -> low frequency  (locationIntervalIdle)
///  - Active trip       -> high frequency (locationIntervalActive)
class LocationService {
  LocationService._();
  static final LocationService instance = LocationService._();

  StreamSubscription<Position>? _subscription;
  void Function(Position)? _onLocation;
  int _intervalSeconds = AppConstants.locationUpdateInterval;

  /// Request foreground + background ("always") permission.
  Future<bool> requestPermissions() async {
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      // Prompt the user to enable location services.
      serviceEnabled = await Geolocator.openLocationSettings();
      if (!serviceEnabled) return false;
    }

    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.deniedForever ||
        permission == LocationPermission.denied) {
      return false;
    }
    return true;
  }

  Future<Position> getCurrentLocation() {
    return Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.high,
      ),
    );
  }

  /// Continuous stream honoring the current [_intervalSeconds] distance filter.
  Stream<Position> getLocationStream() {
    return Geolocator.getPositionStream(
      locationSettings: _settings(),
    );
  }

  LocationSettings _settings() {
    // distanceFilter scaled with interval keeps updates meaningful while idle.
    final distance = _intervalSeconds >= AppConstants.locationIntervalIdle ? 25 : 10;
    return AndroidSettings(
      accuracy: LocationAccuracy.high,
      distanceFilter: distance,
      intervalDuration: Duration(seconds: _intervalSeconds),
      foregroundNotificationConfig: const ForegroundNotificationConfig(
        notificationTitle: 'ReadyRide Driver',
        notificationText: 'Sharing your location while online',
        enableWakeLock: true,
      ),
    );
  }

  /// Start background tracking (call when the driver goes online).
  Future<void> startBackgroundTracking({
    required void Function(Position) onLocation,
  }) async {
    await _subscription?.cancel();
    _onLocation = onLocation;
    _subscription = getLocationStream().listen(onLocation);
  }

  Future<void> stopBackgroundTracking() async {
    await _subscription?.cancel();
    _subscription = null;
    _onLocation = null;
  }

  bool get isTracking => _subscription != null;

  /// Change update frequency (idle vs active trip). Re-subscribes if running.
  Future<void> setUpdateInterval(int seconds) async {
    _intervalSeconds = seconds;
    if (_subscription != null && _onLocation != null) {
      await _subscription!.cancel();
      _subscription = getLocationStream().listen(_onLocation);
    }
  }
}
