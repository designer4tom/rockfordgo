import 'dart:math' as math;

import 'package:flutter/scheduler.dart';
import 'package:flutter/widgets.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

import '../../../home/model/driver_marker_model.dart';
import '../../model/ride_status_model.dart';
import '../widgets/ride_marker_factory.dart';

/// One driver's animated state — markers tween from [current] toward [target]
/// every frame instead of jumping, giving smooth on-road movement.
class _AnimatedDriver {
  LatLng current;
  LatLng target;
  double heading;
  _AnimatedDriver(this.current, this.target, this.heading);
}

/// Drives the searching map's driver markers: smooth interpolation between
/// real position updates, the accepted-driver highlight/fade, and the
/// approach-to-pickup animation. All marker logic lives here so the widget
/// stays declarative and only the map layer rebuilds (via [markers]).
class SearchingMapController extends ChangeNotifier {
  SearchingMapController({
    required TickerProvider vsync,
    required this.onAcceptedFocus,
  }) {
    _ticker = vsync.createTicker(_onTick);
  }

  /// Called once when a driver accepts so the screen can fit the camera to
  /// both the driver's start point and the pickup.
  final void Function(LatLng driverStart, LatLng pickup) onAcceptedFocus;

  late final Ticker _ticker;

  /// Only the GoogleMap rebuilds when this changes (ValueListenableBuilder),
  /// so the rest of the screen is never rebuilt per frame.
  final ValueNotifier<Set<Marker>> markers = ValueNotifier<Set<Marker>>({});

  final Map<int, _AnimatedDriver> _drivers = {};
  LatLng? _pickup;
  int? _acceptedId;
  bool _ready = false;
  Duration _lastTick = Duration.zero;

  BitmapDescriptor? _car;
  BitmapDescriptor? _carFaded;
  BitmapDescriptor? _carHighlight;

  bool get isAccepted => _acceptedId != null;

  Future<void> init(LatLng pickup) async {
    _pickup = pickup;
    _car = await RideMarkerFactory.car();
    _carFaded = await RideMarkerFactory.car(opacity: 0.30);
    _carHighlight = await RideMarkerFactory.highlightedCar();
    _ready = true;
    if (!_ticker.isActive) _ticker.start();
    _rebuild();
  }

  /// Feed the latest nearby drivers (REST poll / realtime). New targets are
  /// tweened to; the crowd freezes once a driver has accepted.
  void updateNearby(List<DriverMarker> drivers) {
    if (_acceptedId != null) return;
    final ids = <int>{};
    for (final d in drivers) {
      ids.add(d.id);
      final target = LatLng(d.lat, d.lng);
      final existing = _drivers[d.id];
      if (existing == null) {
        _drivers[d.id] = _AnimatedDriver(target, target, d.heading);
      } else {
        existing.target = target;
        existing.heading = d.heading;
      }
    }
    _drivers.removeWhere((id, _) => !ids.contains(id));
    // Show newly added drivers immediately; the ticker only rebuilds on
    // movement, so a stationary driver would otherwise never be drawn.
    _rebuild();
  }

  /// A driver accepted: highlight it, fade the rest, and animate it toward the
  /// pickup. Returns false when there's no usable location to animate from.
  bool onAccepted(DriverInfo driver) {
    final pickup = _pickup;
    if (pickup == null) return false;

    final hasCoords = driver.currentLat != 0 && driver.currentLng != 0;
    final start = hasCoords
        ? LatLng(driver.currentLat, driver.currentLng)
        : _drivers[driver.id]?.current;
    if (start == null) return false;

    _acceptedId = driver.id;
    final ad = _drivers.putIfAbsent(
      driver.id,
      () => _AnimatedDriver(start, start, 0),
    );
    ad.current = start;
    ad.target = pickup;
    ad.heading = _bearing(start, pickup);

    onAcceptedFocus(start, pickup);
    if (!_ticker.isActive) _ticker.start();
    _rebuild();
    return true;
  }

  void _onTick(Duration elapsed) {
    if (!_ready) return;
    // Throttle to ~12fps — smooth enough for car motion, far less platform-
    // channel traffic than tweening markers every frame.
    if (elapsed - _lastTick < const Duration(milliseconds: 80)) return;
    _lastTick = elapsed;

    var moved = false;
    for (final d in _drivers.values) {
      final next = _lerp(d.current, d.target, 0.18);
      if ((next.latitude - d.current.latitude).abs() > 1e-7 ||
          (next.longitude - d.current.longitude).abs() > 1e-7) {
        d.current = next;
        moved = true;
      }
    }
    if (moved) _rebuild();
  }

  void _rebuild() {
    if (!_ready) return;
    final result = <Marker>{};
    _drivers.forEach((id, d) {
      final accepted = id == _acceptedId;
      final icon = accepted
          ? _carHighlight!
          : (_acceptedId != null ? _carFaded! : _car!);
      result.add(
        Marker(
          markerId: MarkerId('driver_$id'),
          position: d.current,
          rotation: accepted ? 0 : d.heading,
          flat: true,
          anchor: const Offset(0.5, 0.5),
          zIndexInt: accepted ? 2 : 1,
          icon: icon,
        ),
      );
    });
    markers.value = result;
  }

  static LatLng _lerp(LatLng a, LatLng b, double t) => LatLng(
        a.latitude + (b.latitude - a.latitude) * t,
        a.longitude + (b.longitude - a.longitude) * t,
      );

  static double _bearing(LatLng from, LatLng to) {
    const deg2rad = math.pi / 180;
    final dLng = (to.longitude - from.longitude) * deg2rad;
    final lat1 = from.latitude * deg2rad;
    final lat2 = to.latitude * deg2rad;
    final y = math.sin(dLng) * math.cos(lat2);
    final x = math.cos(lat1) * math.sin(lat2) -
        math.sin(lat1) * math.cos(lat2) * math.cos(dLng);
    final brng = math.atan2(y, x) * 180 / math.pi;
    return (brng + 360) % 360;
  }

  @override
  void dispose() {
    _ticker.dispose();
    markers.dispose();
    super.dispose();
  }
}
