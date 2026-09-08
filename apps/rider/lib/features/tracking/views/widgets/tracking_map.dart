import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/services/route_service.dart';

/// Map that smoothly animates the driver marker between location updates
/// (no teleporting) and keeps the camera following the driver.
class TrackingMap extends StatefulWidget {
  final LatLng? driverPosition;
  final double driverBearing;
  final LatLng? pickup;
  final LatLng? drop;

  const TrackingMap({
    super.key,
    required this.driverPosition,
    this.driverBearing = 0,
    this.pickup,
    this.drop,
  });

  @override
  State<TrackingMap> createState() => _TrackingMapState();
}

class _TrackingMapState extends State<TrackingMap>
    with SingleTickerProviderStateMixin {
  GoogleMapController? _controller;
  late final AnimationController _anim;
  LatLng? _animatedPosition;
  LatLng? _from;
  // Road-following route between pickup and drop (empty until fetched).
  List<LatLng> _routePoints = [];

  static const LatLng _fallback = LatLng(23.8103, 90.4125);

  @override
  void initState() {
    super.initState();
    _animatedPosition = widget.driverPosition;
    _anim = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    )..addListener(_tick);
    _loadRoute();
  }

  /// Fetch the road-following polyline once pickup & drop are known.
  Future<void> _loadRoute() async {
    final pickup = widget.pickup;
    final drop = widget.drop;
    if (pickup == null || drop == null || _routePoints.isNotEmpty) return;
    final points = await RouteService().getRoutePolyline(pickup, drop);
    if (mounted && points.isNotEmpty) {
      setState(() => _routePoints = points);
    }
  }

  void _tick() {
    final from = _from;
    final to = widget.driverPosition;
    if (from == null || to == null) return;
    final t = _anim.value;
    setState(() {
      _animatedPosition = LatLng(
        from.latitude + (to.latitude - from.latitude) * t,
        from.longitude + (to.longitude - from.longitude) * t,
      );
    });
    _controller?.animateCamera(CameraUpdate.newLatLng(_animatedPosition!));
  }

  @override
  void didUpdateWidget(TrackingMap oldWidget) {
    super.didUpdateWidget(oldWidget);
    final newPos = widget.driverPosition;
    if (newPos != null && newPos != oldWidget.driverPosition) {
      _from = _animatedPosition ?? oldWidget.driverPosition ?? newPos;
      _anim.forward(from: 0);
    }
    // pickup/drop may arrive after the first build (status load) → fetch then.
    if (widget.pickup != oldWidget.pickup || widget.drop != oldWidget.drop) {
      _routePoints = [];
      _loadRoute();
    }
  }

  Set<Marker> _markers() {
    final markers = <Marker>{};
    final pos = _animatedPosition;
    if (pos != null) {
      markers.add(Marker(
        markerId: const MarkerId('driver'),
        position: pos,
        rotation: widget.driverBearing,
        anchor: const Offset(0.5, 0.5),
        flat: true,
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueAzure),
      ));
    }
    if (widget.pickup != null) {
      markers.add(Marker(
        markerId: const MarkerId('pickup'),
        position: widget.pickup!,
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueGreen),
      ));
    }
    if (widget.drop != null) {
      markers.add(Marker(
        markerId: const MarkerId('drop'),
        position: widget.drop!,
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
      ));
    }
    return markers;
  }

  Set<Polyline> _polylines() {
    if (widget.pickup == null || widget.drop == null) return {};
    // Road-following points when available; straight line as a fallback.
    final points = _routePoints.isNotEmpty
        ? _routePoints
        : [widget.pickup!, widget.drop!];
    return {
      Polyline(
        polylineId: const PolylineId('route'),
        points: points,
        color: AppColors.primary,
        width: 4,
      ),
    };
  }

  @override
  Widget build(BuildContext context) {
    return GoogleMap(
      initialCameraPosition: CameraPosition(
        target: widget.driverPosition ?? widget.pickup ?? _fallback,
        zoom: 15,
      ),
      markers: _markers(),
      polylines: _polylines(),
      myLocationEnabled: true,
      myLocationButtonEnabled: false,
      zoomControlsEnabled: false,
      onMapCreated: (c) => _controller = c,
    );
  }

  @override
  void dispose() {
    _anim.dispose();
    _controller?.dispose();
    super.dispose();
  }
}
