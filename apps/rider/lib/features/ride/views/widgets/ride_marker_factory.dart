import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show rootBundle;
import 'package:google_maps_flutter/google_maps_flutter.dart';

import '../../../../core/constants/app_colors.dart';

/// Builds map-marker bitmaps from the steering-wheel asset. Markers are
/// pre-rendered once and reused.
///
/// The wheel is drawn pointing "up" (north), so a marker's `rotation: heading`
/// makes it face the driving direction.
class RideMarkerFactory {
  RideMarkerFactory._();

  // Supersample factor: render large, display small, so the marker stays crisp.
  static const double _scale = 3.0;
  static const String _asset = 'assets/images/steering_wheel.png';

  // Decoded once and reused for every marker bitmap.
  static ui.Image? _wheel;

  static Future<ui.Image> _wheelImage() async {
    if (_wheel != null) return _wheel!;
    final data = await rootBundle.load(_asset);
    final codec = await ui.instantiateImageCodec(data.buffer.asUint8List());
    final frame = await codec.getNextFrame();
    return _wheel = frame.image;
  }

  /// A plain steering wheel (used for idle nearby drivers). [opacity] is baked
  /// into the bitmap because Google markers have no runtime opacity.
  static Future<BitmapDescriptor> car({
    double size = 44,
    double opacity = 1,
  }) async {
    final s = size * _scale;
    final recorder = ui.PictureRecorder();
    final canvas = Canvas(recorder);
    final wheel = await _wheelImage();
    _paintWheel(canvas, wheel, Offset(s / 2, s / 2), s * 0.9, opacity);
    return _finish(recorder, s);
  }

  /// The accepted driver: the wheel inside a white badge with a primary ring
  /// and soft shadow so it stands out from the faded crowd.
  static Future<BitmapDescriptor> highlightedCar({double size = 60}) async {
    final s = size * _scale;
    final center = Offset(s / 2, s / 2);
    final radius = s / 2 - 6 * _scale;
    final recorder = ui.PictureRecorder();
    final canvas = Canvas(recorder);
    final wheel = await _wheelImage();

    // Shadow.
    canvas.drawCircle(
      center + Offset(0, 2 * _scale),
      radius,
      Paint()
        ..color = Colors.black.withValues(alpha: 0.25)
        ..maskFilter = MaskFilter.blur(BlurStyle.normal, 5 * _scale),
    );
    // White badge.
    canvas.drawCircle(center, radius, Paint()..color = Colors.white);
    // Primary ring.
    canvas.drawCircle(
      center,
      radius,
      Paint()
        ..color = AppColors.primary
        ..style = PaintingStyle.stroke
        ..strokeWidth = 2.5 * _scale,
    );

    _paintWheel(canvas, wheel, center, radius * 1.5, 1);
    return _finish(recorder, s);
  }

  // Draws the steering-wheel image centred at [center], fitted to a [box]-sized
  // square. [opacity] is baked in so faded markers need no runtime alpha.
  static void _paintWheel(
    Canvas canvas,
    ui.Image wheel,
    Offset center,
    double box,
    double opacity,
  ) {
    final src = Rect.fromLTWH(
        0, 0, wheel.width.toDouble(), wheel.height.toDouble());
    final dst = Rect.fromCenter(center: center, width: box, height: box);
    canvas.drawImageRect(
      wheel,
      src,
      dst,
      Paint()
        ..filterQuality = FilterQuality.high
        ..color = Color.fromRGBO(255, 255, 255, opacity),
    );
  }

  static Future<BitmapDescriptor> _finish(
      ui.PictureRecorder recorder, double s) async {
    final dim = s.toInt();
    final image = await recorder.endRecording().toImage(dim, dim);
    final data = await image.toByteData(format: ui.ImageByteFormat.png);
    return BitmapDescriptor.bytes(
      data!.buffer.asUint8List(),
      imagePixelRatio: _scale,
    );
  }
}
