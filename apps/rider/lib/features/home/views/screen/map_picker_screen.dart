import 'dart:async';
import 'dart:ui' as ui;

import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show rootBundle;
import 'package:go_router/go_router.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../location/model/place_model.dart';
import '../../../location/provider/location_provider.dart';
import '../../provider/home_provider.dart';

const LatLng _kDefaultCenter = LatLng(23.8103, 90.4125);

class MapPickerScreen extends StatefulWidget {
  const MapPickerScreen({super.key});

  @override
  State<MapPickerScreen> createState() => _MapPickerScreenState();
}

class _MapPickerScreenState extends State<MapPickerScreen> {
  LatLng _center = _kDefaultCenter;
  PlaceModel? _resolved;
  bool _resolving = false;
  Timer? _debounce;
  // Cached so we can reliably stop polling in dispose/deactivate without
  // touching `context` (which can throw once the element is deactivated).
  HomeProvider? _home;
  // 🚗 marker, rendered once and reused for every nearby driver.
  BitmapDescriptor? _carIcon;

  @override
  void initState() {
    super.initState();
    _loadCarIcon();
    final pos = context.read<LocationProvider>().currentPosition;
    if (pos != null) _center = LatLng(pos.latitude, pos.longitude);
    // Nearby-driver polling lives here (a map is shown), not on home.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context
          .read<HomeProvider>()
          .startDriverRefresh(_center.latitude, _center.longitude);
    });
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _home = context.read<HomeProvider>();
  }

  @override
  void deactivate() {
    // Leaving this screen (popped or pushed over) → stop polling so it never
    // keeps hitting /nearby-drivers from underneath the home screen.
    _home?.stopDriverRefresh();
    super.deactivate();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _home?.stopDriverRefresh();
    super.dispose();
  }

  // Load the steering-wheel image into a marker bitmap once and reuse it for
  // every nearby online driver.
  Future<void> _loadCarIcon() async {
    final icon = await _buildCarBitmap();
    if (!mounted) return;
    setState(() => _carIcon = icon);
  }

  Future<BitmapDescriptor> _buildCarBitmap() async {
    // Keep the marker small: ~40 logical px, supersampled for crispness.
    const scale = 3.0;
    final data = await rootBundle.load('assets/images/steering_wheel.png');
    final codec = await ui.instantiateImageCodec(
      data.buffer.asUint8List(),
      targetWidth: (40 * scale).round(),
    );
    final frame = await codec.getNextFrame();
    final bytes = await frame.image.toByteData(format: ui.ImageByteFormat.png);
    return BitmapDescriptor.bytes(
      bytes!.buffer.asUint8List(),
      imagePixelRatio: scale,
    );
  }

  Set<Marker> _driverMarkers(List drivers) {
    final carIcon = _carIcon;
    // Until the 🚗 bitmap finishes rendering, fall back to the default marker so
    // drivers are never missing from the map.
    final icon = carIcon ??
        BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueAzure);
    return {
      for (final d in drivers)
        Marker(
          markerId: MarkerId('driver_${d.id}'),
          position: LatLng(d.lat, d.lng),
          rotation: d.heading,
          flat: true,
          anchor: const Offset(0.5, 0.5),
          icon: icon,
        ),
    };
  }

  void _onCameraMove(CameraPosition position) {
    _center = position.target;
  }

  void _onCameraIdle() {
    // Reverse geocode the centred pin, debounced.
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), _resolveCenter);
  }

  Future<void> _resolveCenter() async {
    setState(() => _resolving = true);
    final place = await context
        .read<LocationProvider>()
        .reverseGeocode(_center.latitude, _center.longitude);
    if (!mounted) return;
    setState(() {
      _resolved = place;
      _resolving = false;
    });
  }

  void _confirm() {
    final place = _resolved ??
        PlaceModel(
          address: 'destination.selected_location'.tr(),
          lat: _center.latitude,
          lng: _center.longitude,
        );
    context.pop(place);
  }

  @override
  Widget build(BuildContext context) {
    final drivers = context.watch<HomeProvider>().nearbyDrivers;
    return Scaffold(
      appBar: AppBar(title: Text('destination.select_on_map'.tr())),
      body: Stack(
        alignment: Alignment.center,
        children: [
          GoogleMap(
            initialCameraPosition: CameraPosition(target: _center, zoom: 16),
            onCameraMove: _onCameraMove,
            onCameraIdle: _onCameraIdle,
            myLocationEnabled: true,
            myLocationButtonEnabled: true,
            zoomControlsEnabled: false,
            markers: _driverMarkers(drivers),
          ),
          // Fixed centre pin.
          const Padding(
            padding: EdgeInsets.only(bottom: 36),
            child: Icon(Icons.location_on, size: 48, color: AppColors.danger),
          ),
          Positioned(
            left: 16,
            right: 16,
            bottom: 24,
            child: SafeArea(
              top: false,
              child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Theme.of(context).cardColor,
                borderRadius: BorderRadius.circular(16),
                boxShadow: const [
                  BoxShadow(color: Colors.black26, blurRadius: 12),
                ],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.place, color: AppColors.primary),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _resolving
                            ? Text('destination.resolving_address'.tr())
                            : Text(
                                _resolved?.address ?? 'destination.move_map_hint'.tr(),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  CustomButton(
                    text: 'destination.confirm_location'.tr(),
                    onPressed: _confirm,
                  ),
                ],
              ),
            ),
            ),
          ),
        ],
      ),
    );
  }
}
