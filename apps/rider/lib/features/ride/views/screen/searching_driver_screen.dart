import 'dart:async';

import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../home/provider/home_provider.dart';
import '../../../location/provider/location_provider.dart';
import '../../provider/ride_provider.dart';
import '../controllers/searching_map_controller.dart';
import '../widgets/searching_status_card.dart';

/// Default map centre (Dhaka) used only if no pickup/GPS is available yet.
const LatLng _kFallbackCenter = LatLng(23.8103, 90.4125);

/// Premium, full-screen ride-searching experience: a live map with animated
/// nearby-driver markers, a pulsing rider ripple and a floating status card.
///
/// All ride business logic stays in [RideProvider]/[HomeProvider]; the map and
/// marker animation live in [SearchingMapController]. This screen only wires
/// them together (consistent with the app's Provider architecture).
class SearchingDriverScreen extends StatefulWidget {
  const SearchingDriverScreen({super.key});

  @override
  State<SearchingDriverScreen> createState() => _SearchingDriverScreenState();
}

class _SearchingDriverScreenState extends State<SearchingDriverScreen>
    with TickerProviderStateMixin {
  late final RideProvider _ride;
  late final HomeProvider _home;
  late final SearchingMapController _mapController;

  GoogleMapController? _map;
  LatLng _center = _kFallbackCenter;
  late final LatLng _confirmedPickup;
  Timer? _elapsedTimer;
  Duration _elapsed = Duration.zero;
  bool _accepted = false;
  bool _handled = false;

  @override
  void initState() {
    super.initState();
    _ride = context.read<RideProvider>();
    _home = context.read<HomeProvider>();

    final pickup = _ride.pickup;
    if (pickup != null) {
      _center = LatLng(pickup.lat, pickup.lng);
    } else {
      final pos = context.read<LocationProvider>().currentPosition;
      if (pos != null) _center = LatLng(pos.latitude, pos.longitude);
    }
    // This is intentionally captured once. Map camera gestures must never
    // change the booking's confirmed pickup coordinate.
    _confirmedPickup = _center;

    _mapController = SearchingMapController(
      vsync: this,
      onAcceptedFocus: _fitToDriverAndPickup,
    );
    _mapController.init(_confirmedPickup);

    // Live nearby drivers (polled now, instantly upgradeable to realtime).
    _home.addListener(_onDriversChanged);
    _home.startDriverRefresh(
      _confirmedPickup.latitude,
      _confirmedPickup.longitude,
    );
    _onDriversChanged();

    _ride.addListener(_onRideChange);

    _elapsedTimer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (!mounted || _accepted) return;
      setState(() => _elapsed += const Duration(seconds: 1));
    });
  }

  void _onDriversChanged() {
    _mapController.updateNearby(_home.nearbyDrivers);
  }

  void _onRideChange() {
    if (_handled || !mounted) return;
    final status = _ride.rideStatus?.status;
    switch (status) {
      case 'accepted':
        _handled = true;
        _onDriverAccepted();
      case 'no_driver_found':
        _handled = true;
        _showNoDriverDialog();
      case 'cancelled':
        _handled = true;
        context.go(RouteNames.home);
    }
  }

  /// Highlight the accepted driver, fade the rest and animate it toward the
  /// pickup, then continue to the live tracking screen (unchanged flow).
  void _onDriverAccepted() {
    final driver = _ride.rideStatus?.driver;
    final orderId = _ride.activeBooking?.orderId;
    final animating = driver != null && _mapController.onAccepted(driver);

    if (mounted) {
      setState(() => _accepted = true);
      SnackbarHelper.showSuccess(context, 'ride.driver_found'.tr());
    }

    // Give the accept animation a brief moment to play before navigating.
    Future.delayed(
      animating ? const Duration(milliseconds: 2200) : Duration.zero,
      () {
        if (!mounted) return;
        if (orderId != null) {
          context.go(RouteNames.rideTracking, extra: orderId);
        } else {
          context.go(RouteNames.home);
        }
      },
    );
  }

  Future<void> _fitToDriverAndPickup(LatLng driver, LatLng pickup) async {
    final map = _map;
    if (map == null) return;
    final bounds = LatLngBounds(
      southwest: LatLng(
        driver.latitude < pickup.latitude ? driver.latitude : pickup.latitude,
        driver.longitude < pickup.longitude
            ? driver.longitude
            : pickup.longitude,
      ),
      northeast: LatLng(
        driver.latitude > pickup.latitude ? driver.latitude : pickup.latitude,
        driver.longitude > pickup.longitude
            ? driver.longitude
            : pickup.longitude,
      ),
    );
    await map.animateCamera(CameraUpdate.newLatLngBounds(bounds, 90));
  }

  Future<void> _showNoDriverDialog() async {
    final retry = await _showStyledDialog(
      icon: Icons.search_off_rounded,
      message: 'ride.no_driver_msg'.tr(),
      cancelLabel: 'common.cancel'.tr(),
      confirmLabel: 'common.retry'.tr(),
      danger: false,
    );
    if (!mounted) return;
    if (retry == true) {
      _ride.clearBooking();
      context.go(RouteNames.setDestination);
      _handled = false;
      final orderId = _ride.activeBooking?.orderId;
      if (orderId != null) _ride.startStatusPolling(orderId);
    } else {
      _ride.clearBooking();
      context.go(RouteNames.home);
    }
  }

  Future<void> _cancel() async {
    if (_accepted) return;
    final confirm = await _showStyledDialog(
      icon: Icons.close_rounded,
      message: 'ride.cancel_confirm'.tr(),
      cancelLabel: 'common.no'.tr(),
      confirmLabel: 'common.yes'.tr(),
      danger: true,
    );
    if (confirm != true || !mounted) return;
    final ok = await _ride.cancelRide('user_cancelled');
    if (!mounted) return;
    if (ok) {
      _ride.clearBooking();
      context.go(RouteNames.home);
    } else {
      SnackbarHelper.showError(
          context, _ride.error ?? 'ride.cancel_failed'.tr());
    }
  }

  @override
  void dispose() {
    _ride.removeListener(_onRideChange);
    _ride.stopStatusPolling();
    _home.removeListener(_onDriversChanged);
    _home.stopDriverRefresh();
    _elapsedTimer?.cancel();
    _map?.dispose();
    _mapController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final driverCount = context.select<HomeProvider, int>(
        (p) => p.nearbyDrivers.length);

    return Scaffold(
      body: Stack(
        children: [
          // Full-screen map with animated driver markers. Only this subtree
          // rebuilds as markers tween (ValueListenableBuilder).
          Positioned.fill(
            child: ValueListenableBuilder<Set<Marker>>(
              valueListenable: _mapController.markers,
              builder: (context, driverMarkers, _) => GoogleMap(
                initialCameraPosition:
                    CameraPosition(target: _confirmedPickup, zoom: 15.5),
                // Unlike the old centre-screen ripple, this marker stays at
                // the confirmed geographic coordinate while the user pans or
                // zooms the camera.
                markers: {
                  ...driverMarkers,
                  Marker(
                    markerId: const MarkerId('confirmed_pickup'),
                    position: _confirmedPickup,
                    icon: BitmapDescriptor.defaultMarkerWithHue(
                      BitmapDescriptor.hueGreen,
                    ),
                    zIndexInt: 3,
                  ),
                },
                myLocationEnabled: true,
                myLocationButtonEnabled: false,
                zoomControlsEnabled: false,
                compassEnabled: false,
                mapToolbarEnabled: false,
                onMapCreated: (c) => _map = c,
              ),
            ),
          ),

          // Back / close.
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(8),
              child: Align(
                alignment: Alignment.topLeft,
                child: _RoundIconButton(
                  icon: Icons.arrow_back,
                  onTap: _accepted ? null : _cancel,
                ),
              ),
            ),
          ),

          // Floating status card — slides up on entry.
          Align(
            alignment: Alignment.bottomCenter,
            child: TweenAnimationBuilder<double>(
              tween: Tween(begin: 1, end: 0),
              duration: const Duration(milliseconds: 450),
              curve: Curves.easeOutCubic,
              builder: (context, t, child) => FractionalTranslation(
                translation: Offset(0, t),
                child: child,
              ),
              child: SearchingStatusCard(
                driverCount: driverCount,
                elapsed: _elapsed,
                accepted: _accepted,
                onCancel: _cancel,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<bool?> _showStyledDialog({
    required IconData icon,
    required String message,
    required String cancelLabel,
    required String confirmLabel,
    bool danger = false,
    bool barrierDismissible = true,
  }) {
    final theme = Theme.of(context);
    final accent = danger ? AppColors.danger : AppColors.primary;

    return showDialog<bool>(
      context: context,
      barrierDismissible: barrierDismissible,
      barrierColor: Colors.black.withValues(alpha: 0.45),
      builder: (ctx) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.symmetric(horizontal: 28),
        child: Container(
          padding: const EdgeInsets.fromLTRB(20, 24, 20, 20),
          decoration: BoxDecoration(
            color: theme.cardColor,
            borderRadius: BorderRadius.circular(22),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.08),
                blurRadius: 24,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(
                  color: accent.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                alignment: Alignment.center,
                child: Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: accent,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(icon, color: Colors.white, size: 26),
                ),
              ),
              const SizedBox(height: 16),
              Text(
                message,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 13.5,
                  height: 1.4,
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 22),
              Row(
                children: [
                  Expanded(
                    child: SizedBox(
                      height: 48,
                      child: OutlinedButton(
                        onPressed: () => Navigator.pop(ctx, false),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: theme.colorScheme.onSurface,
                          side: BorderSide(color: theme.dividerColor),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: Text(
                          cancelLabel,
                          style: const TextStyle(
                              fontSize: 14, fontWeight: FontWeight.w600),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: SizedBox(
                      height: 48,
                      child: ElevatedButton(
                        onPressed: () => Navigator.pop(ctx, true),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: accent,
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: Text(
                          confirmLabel,
                          style: const TextStyle(
                              fontSize: 14, fontWeight: FontWeight.w600),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _RoundIconButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback? onTap;
  const _RoundIconButton({required this.icon, this.onTap});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Theme.of(context).cardColor,
      shape: const CircleBorder(),
      elevation: 3,
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(10),
          child: Icon(icon, color: Theme.of(context).colorScheme.onSurface),
        ),
      ),
    );
  }
}
