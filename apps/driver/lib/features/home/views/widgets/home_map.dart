import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

/// Map centered on the driver. Requires a Google Maps API key configured in
/// the native manifests; without it the map area renders blank but won't crash.
class HomeMap extends StatefulWidget {
  final Position? position;
  const HomeMap({super.key, this.position});

  @override
  State<HomeMap> createState() => HomeMapState();
}

class HomeMapState extends State<HomeMap> {
  GoogleMapController? _controller;

  Future<void> recenter() async {
    if (_controller == null) return;
    await _controller!.animateCamera(
      CameraUpdate.newCameraPosition(
        CameraPosition(target: _latLng, zoom: 16),
      ),
    );
  }

  static const _fallback = LatLng(23.8103, 90.4125); // Dhaka

  LatLng get _latLng => widget.position == null
      ? _fallback
      : LatLng(widget.position!.latitude, widget.position!.longitude);

  @override
  void didUpdateWidget(covariant HomeMap oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.position != null && _controller != null) {
      _controller!.animateCamera(CameraUpdate.newLatLng(_latLng));
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GoogleMap(
      initialCameraPosition: CameraPosition(target: _latLng, zoom: 15),
      myLocationEnabled: true,
      myLocationButtonEnabled: false,
      zoomControlsEnabled: false,
      compassEnabled: false,
      onMapCreated: (c) => _controller = c,
      markers: widget.position == null
          ? {}
          : {
              Marker(
                markerId: const MarkerId('me'),
                position: _latLng,
                icon: BitmapDescriptor.defaultMarkerWithHue(
                    BitmapDescriptor.hueOrange),
              ),
            },
    );
  }
}

/// Shown when location is unavailable.
class MapPlaceholder extends StatelessWidget {
  const MapPlaceholder({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Theme.of(context).scaffoldBackgroundColor,
      child: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.map_outlined,
                size: 48, color: Theme.of(context).hintColor),
            const SizedBox(height: 8),
            Text('home.locating_you'.tr(),
                style: TextStyle(color: Theme.of(context).hintColor)),
          ],
        ),
      ),
    );
  }
}
