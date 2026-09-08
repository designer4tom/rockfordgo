import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:provider/provider.dart';

import '../../provider/home_provider.dart';

/// Default camera target (Dhaka) used until a GPS fix is available.
const LatLng _kDefaultCenter = LatLng(23.8103, 90.4125);

class HomeMap extends StatefulWidget {
  final LatLng? initialPosition;
  final ValueChanged<GoogleMapController>? onMapCreated;

  const HomeMap({super.key, this.initialPosition, this.onMapCreated});

  @override
  State<HomeMap> createState() => _HomeMapState();
}

class _HomeMapState extends State<HomeMap> {
  GoogleMapController? _controller;

  Set<Marker> _buildDriverMarkers(HomeProvider provider) {
    return provider.nearbyDrivers
        .map(
          (d) => Marker(
            markerId: MarkerId('driver_${d.id}'),
            position: LatLng(d.lat, d.lng),
            rotation: d.heading,
            flat: true,
            anchor: const Offset(0.5, 0.5),
            icon: BitmapDescriptor.defaultMarkerWithHue(
              d.type == 'bike'
                  ? BitmapDescriptor.hueOrange
                  : BitmapDescriptor.hueAzure,
            ),
          ),
        )
        .toSet();
  }

  @override
  Widget build(BuildContext context) {
    final markers =
        context.select<HomeProvider, Set<Marker>>(_buildDriverMarkers);

    return GoogleMap(
      initialCameraPosition: CameraPosition(
        target: widget.initialPosition ?? _kDefaultCenter,
        zoom: 15,
      ),
      markers: markers,
      myLocationEnabled: true,
      myLocationButtonEnabled: true,
      zoomControlsEnabled: false,
      compassEnabled: false,
      onMapCreated: (controller) {
        _controller = controller;
        widget.onMapCreated?.call(controller);
      },
    );
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }
}
