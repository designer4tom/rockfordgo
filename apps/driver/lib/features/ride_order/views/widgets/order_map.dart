import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

import '../../../../core/models/place_info.dart';
import '../../../../core/services/route_service.dart';

/// Map showing the driver and the current target (pickup or drop) with a
/// road-following route from the Directions API. Falls back to a straight line
/// only if the route can't be fetched.
class OrderMap extends StatefulWidget {
  final Position? driver;
  final PlaceInfo target;
  final String targetLabel;

  const OrderMap({
    super.key,
    required this.driver,
    required this.target,
    required this.targetLabel,
  });

  @override
  State<OrderMap> createState() => _OrderMapState();
}

class _OrderMapState extends State<OrderMap> {
  GoogleMapController? _controller;

  List<LatLng> _routePoints = [];
  LatLng? _routeTarget; // target the current route was fetched for
  LatLng? _routeOrigin; // origin the current route was fetched for
  bool _fetching = false;

  LatLng get _targetLatLng => LatLng(widget.target.lat, widget.target.lng);
  LatLng? get _driverLatLng => widget.driver == null
      ? null
      : LatLng(widget.driver!.latitude, widget.driver!.longitude);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _maybeFetchRoute());
  }

  @override
  void didUpdateWidget(covariant OrderMap oldWidget) {
    super.didUpdateWidget(oldWidget);
    final d = _driverLatLng;
    if (d != null && _controller != null) {
      _controller!.animateCamera(CameraUpdate.newLatLng(d));
    }
    _maybeFetchRoute();
  }

  /// Fetch the road route when the target changes or the driver has moved a
  /// meaningful distance — avoids hitting the Directions API on every ping.
  Future<void> _maybeFetchRoute() async {
    final origin = _driverLatLng;
    if (origin == null || _fetching) return;

    final targetChanged = _routeTarget == null ||
        _routeTarget!.latitude != _targetLatLng.latitude ||
        _routeTarget!.longitude != _targetLatLng.longitude;

    final movedFar = _routeOrigin == null ||
        Geolocator.distanceBetween(
              _routeOrigin!.latitude,
              _routeOrigin!.longitude,
              origin.latitude,
              origin.longitude,
            ) >
            80; // metres

    if (!targetChanged && !movedFar && _routePoints.isNotEmpty) return;

    _fetching = true;
    final points = await RouteService.getRoute(origin, _targetLatLng);
    _fetching = false;
    if (!mounted) return;
    if (points.isNotEmpty) {
      setState(() {
        _routePoints = points;
        _routeOrigin = origin;
        _routeTarget = _targetLatLng;
      });
    }
  }

  Set<Polyline> get _polylines {
    final d = _driverLatLng;
    if (_routePoints.isNotEmpty) {
      return {
        Polyline(
          polylineId: const PolylineId('route'),
          points: _routePoints,
          color: Colors.blue,
          width: 5,
          geodesic: true,
        ),
      };
    }
    // Fallback: straight line until the road route arrives.
    if (d == null) return {};
    return {
      Polyline(
        polylineId: const PolylineId('route_fallback'),
        points: [d, _targetLatLng],
        color: Colors.blue.withValues(alpha: 0.4),
        width: 3,
      ),
    };
  }

  Set<Marker> get _markers {
    final m = <Marker>{
      Marker(
        markerId: const MarkerId('target'),
        position: _targetLatLng,
        infoWindow: InfoWindow(title: widget.targetLabel),
      ),
    };
    final d = _driverLatLng;
    if (d != null) {
      m.add(Marker(
        markerId: const MarkerId('driver'),
        position: d,
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueOrange),
      ));
    }
    return m;
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GoogleMap(
      initialCameraPosition:
          CameraPosition(target: _driverLatLng ?? _targetLatLng, zoom: 14),
      myLocationEnabled: true,
      myLocationButtonEnabled: false,
      zoomControlsEnabled: false,
      compassEnabled: false,
      markers: _markers,
      polylines: _polylines,
      onMapCreated: (c) => _controller = c,
    );
  }
}
