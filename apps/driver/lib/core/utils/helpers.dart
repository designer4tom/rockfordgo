import 'dart:math' as math;

import 'package:intl/intl.dart';

import '../constants/app_constants.dart';
import 'currency_helper.dart';

class Helpers {
  /// Resolve a (possibly relative) storage path returned by the API into an
  /// absolute URL usable by NetworkImage. The API serves uploads from the
  /// site root (e.g. `/storage/...`), while [AppConstants.baseUrl] points at
  /// the `/api/v1` prefix, so we strip that prefix to get the asset host.
  static String? imageUrl(String? path) {
    if (path == null || path.isEmpty) return null;
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    final origin = Uri.parse(AppConstants.baseUrl).origin; // scheme + host[:port]
    // Normalise the relative path: strip leading slashes so we can inspect it.
    var rel = path.replaceFirst(RegExp(r'^/+'), '');
    // Uploaded files (avatars, documents) are served from the public storage
    // symlink (`/storage/...`). Some API responses already include that prefix,
    // others return the bare path (e.g. `avatars/x.jpg`) — add it when missing
    // so both resolve to a valid URL instead of a 404 / blank image.
    if (!rel.startsWith('storage/')) {
      rel = 'storage/$rel';
    }
    return '$origin/$rel';
  }

  /// Currency formatting is driven by /config via CurrencyHelper, so every
  /// existing Helpers.money(...) call reflects the admin's currency settings.
  static String money(num? amount) => CurrencyHelper.format(amount);

  // API timestamps are UTC (ISO string with trailing "Z"). DateTime.parse
  // keeps them as UTC internally, so every formatter here must convert to
  // the device's local timezone before formatting — otherwise times show
  // hours off from the driver's actual location/clock.
  static String date(DateTime? dt) =>
      dt == null ? '' : DateFormat('dd MMM yyyy').format(dt.toLocal());

  static String dateTime(DateTime? dt) =>
      dt == null ? '' : DateFormat('dd MMM yyyy, hh:mm a').format(dt.toLocal());

  static String time(DateTime? dt) =>
      dt == null ? '' : DateFormat('hh:mm a').format(dt.toLocal());

  static DateTime? tryParse(dynamic value) {
    if (value == null) return null;
    return DateTime.tryParse(value.toString())?.toLocal();
  }

  /// Distance in km between two lat/lng pairs (Haversine).
  static double distanceKm(
      double lat1, double lon1, double lat2, double lon2) {
    const r = 6371.0;
    final dLat = _deg2rad(lat2 - lat1);
    final dLon = _deg2rad(lon2 - lon1);
    final a = (1 - math.cos(dLat)) / 2 +
        math.cos(_deg2rad(lat1)) *
            math.cos(_deg2rad(lat2)) *
            (1 - math.cos(dLon)) /
            2;
    return 2 * r * math.asin(math.sqrt(a));
  }

  static double _deg2rad(double d) => d * math.pi / 180.0;
}
