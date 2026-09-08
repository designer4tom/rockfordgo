import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';

import '../../../core/network/api_exception.dart';
import '../model/place_model.dart';
import '../repository/location_repository.dart';

class LocationProvider extends ChangeNotifier {
  final LocationRepository _repository;

  LocationProvider(this._repository);

  Position? _currentPosition;
  PlaceModel? _currentPlace;
  bool _hasPermission = false;
  bool _isLoading = false;

  Position? get currentPosition => _currentPosition;
  PlaceModel? get currentPlace => _currentPlace;
  bool get hasPermission => _hasPermission;
  bool get isLoading => _isLoading;

  /// Ask for location permission via geolocator.
  Future<bool> requestPermission() async {
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      _hasPermission = false;
      notifyListeners();
      return false;
    }

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }

    _hasPermission =
        permission == LocationPermission.always ||
        permission == LocationPermission.whileInUse;
    notifyListeners();
    return _hasPermission;
  }

  /// Get a fresh GPS fix (cached in [_currentPosition]).
  Future<Position?> getCurrentLocation() async {
    if (!_hasPermission) {
      final granted = await requestPermission();
      if (!granted) return null;
    }
    try {
      _currentPosition = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 10),
        ),
      );
    } catch (_) {
      // Fresh fix failed or timed out — fall back to the last known
      // position so the home pickup card still resolves an address.
      try {
        _currentPosition =
            await Geolocator.getLastKnownPosition() ?? _currentPosition;
      } catch (_) {}
    }
    if (_currentPosition != null) notifyListeners();
    return _currentPosition;
  }

  /// GPS + reverse geocode into [_currentPlace]. Uses cached position
  /// when available so we don't hit the GPS repeatedly.
  Future<void> getCurrentPlace() async {
    _isLoading = true;
    notifyListeners();

    final position = _currentPosition ?? await getCurrentLocation();
    if (position != null) {
      final place = await reverseGeocode(position.latitude, position.longitude);
      // Always set a place from the GPS fix — even if reverse geocode
      // failed (e.g. backend has no maps key) or returned an empty
      // address, so pickup still has coords.
      _currentPlace = (place != null && place.address.trim().isNotEmpty)
          ? place
          : PlaceModel(
              address: 'destination.current_location'.tr(),
              lat: position.latitude,
              lng: position.longitude,
            );
    }

    _isLoading = false;
    notifyListeners();
  }

  /// Resolve a search prediction's real coordinates from its place_id.
  Future<PlaceModel?> placeDetails(String placeId) async {
    try {
      return await _repository.placeDetails(placeId);
    } on ApiException {
      return null;
    }
  }

  Future<PlaceModel?> reverseGeocode(double lat, double lng) async {
    try {
      return await _repository.reverseGeocode(lat, lng);
    } on ApiException {
      return null;
    }
  }

  Future<List<PlaceModel>> searchPlaces(String query) async {
    if (query.trim().isEmpty) return [];
    try {
      return await _repository.searchPlaces(
        query,
        lat: _currentPosition?.latitude,
        lng: _currentPosition?.longitude,
      );
    } on ApiException {
      return [];
    }
  }
}
