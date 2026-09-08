import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

import '../constants/app_constants.dart';

/// Fetches a road-following route between two points using the Google
/// Directions API and decodes the overview polyline into map points.
///
/// Falls back to a straight line [origin, destination] if the request fails
/// (e.g. Directions API not enabled / no network).
class RouteService {
  final Dio _dio = Dio();

  Future<List<LatLng>> getRoutePolyline(
    LatLng origin,
    LatLng destination, {
    List<LatLng> waypoints = const [],
  }) async {
    try {
      final res = await _dio.get(
        'https://maps.googleapis.com/maps/api/directions/json',
        queryParameters: {
          'origin': '${origin.latitude},${origin.longitude}',
          'destination': '${destination.latitude},${destination.longitude}',
          'mode': 'driving',
          if (waypoints.isNotEmpty)
            'waypoints': waypoints
                .map((w) => '${w.latitude},${w.longitude}')
                .join('|'),
          'key': AppConstants.googleMapsApiKey,
        },
      );

      final routes = res.data['routes'] as List?;
      if (routes != null && routes.isNotEmpty) {
        final encoded =
            routes.first['overview_polyline']?['points'] as String?;
        if (encoded != null && encoded.isNotEmpty) {
          return decodePolyline(encoded);
        }
      }
    } catch (e) {
      if (kDebugMode) debugPrint('RouteService failed, using straight line: $e');
    }
    return [origin, destination];
  }

  /// Decodes a Google encoded polyline string into a list of [LatLng].
  static List<LatLng> decodePolyline(String encoded) {
    final List<LatLng> points = [];
    int index = 0;
    final int len = encoded.length;
    int lat = 0;
    int lng = 0;

    while (index < len) {
      int b;
      int shift = 0;
      int result = 0;
      do {
        b = encoded.codeUnitAt(index++) - 63;
        result |= (b & 0x1f) << shift;
        shift += 5;
      } while (b >= 0x20);
      final int dlat = (result & 1) != 0 ? ~(result >> 1) : (result >> 1);
      lat += dlat;

      shift = 0;
      result = 0;
      do {
        b = encoded.codeUnitAt(index++) - 63;
        result |= (b & 0x1f) << shift;
        shift += 5;
      } while (b >= 0x20);
      final int dlng = (result & 1) != 0 ? ~(result >> 1) : (result >> 1);
      lng += dlng;

      points.add(LatLng(lat / 1e5, lng / 1e5));
    }
    return points;
  }
}
