import 'dart:async';

import 'package:flutter/material.dart';

import '../../../core/network/api_exception.dart';
import '../../location/model/place_model.dart';
import '../../location/repository/location_repository.dart';
import '../model/driver_marker_model.dart';
import '../repository/home_repository.dart';

class HomeProvider extends ChangeNotifier {
  final HomeRepository _homeRepository;
  // Kept for upcoming pickup reverse-geocode features (map pin → address).
  // ignore: unused_field
  final LocationRepository _locationRepository;

  HomeProvider(this._homeRepository, this._locationRepository);

  List<DriverMarker> _nearbyDrivers = [];
  PlaceModel? _pickupLocation;
  String _selectedService = 'ride';
  Timer? _driverRefreshTimer;

  List<DriverMarker> get nearbyDrivers => _nearbyDrivers;
  PlaceModel? get pickupLocation => _pickupLocation;
  String get selectedService => _selectedService;

  Future<void> loadNearbyDrivers(double lat, double lng) async {
    try {
      _nearbyDrivers = await _homeRepository.getNearbyDrivers(
        lat,
        lng,
        serviceType: _selectedService,
      );
      notifyListeners();
    } on ApiException {
      // Keep the last known markers on transient failures.
    }
  }

  /// Refresh nearby drivers every 10 seconds.
  void startDriverRefresh(double lat, double lng) {
    _driverRefreshTimer?.cancel();
    loadNearbyDrivers(lat, lng);
    _driverRefreshTimer = Timer.periodic(
      const Duration(seconds: 10),
      (_) => loadNearbyDrivers(lat, lng),
    );
  }

  void stopDriverRefresh() {
    _driverRefreshTimer?.cancel();
    _driverRefreshTimer = null;
  }

  void selectService(String type) {
    if (_selectedService == type) return;
    _selectedService = type;
    notifyListeners();
  }

  void setPickup(PlaceModel place) {
    _pickupLocation = place;
    notifyListeners();
  }

  @override
  void dispose() {
    _driverRefreshTimer?.cancel();
    super.dispose();
  }
}
