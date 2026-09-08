import 'package:flutter_polyline_points/flutter_polyline_points.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

import '../constants/app_constants.dart';

/// Fetches road-following routes from the Google Directions API and returns
/// decoded polyline points. Returns an empty list on failure (callers fall
/// back to a straight line).
class RouteService {
  RouteService._();

  static final PolylinePoints _points =
      PolylinePoints(apiKey: AppConstants.googleMapsKey);

  static Future<List<LatLng>> getRoute(
    LatLng origin,
    LatLng destination,
  ) async {
    if (AppConstants.googleMapsKey.isEmpty) return const [];
    try {
      final result = await _points.getRouteBetweenCoordinates(
        // Legacy Directions API (requires "Directions API" enabled on the key).
        // ignore: deprecated_member_use
        request: PolylineRequest(
          origin: PointLatLng(origin.latitude, origin.longitude),
          destination: PointLatLng(destination.latitude, destination.longitude),
          mode: TravelMode.driving,
        ),
      );
      if (result.points.isEmpty) return const [];
      return result.points
          .map((p) => LatLng(p.latitude, p.longitude))
          .toList();
    } catch (_) {
      return const [];
    }
  }
}
