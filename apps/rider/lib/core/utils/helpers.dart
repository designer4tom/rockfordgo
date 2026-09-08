import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../constants/app_constants.dart';
import '../services/config_service.dart';

class Helpers {
  Helpers._();

  static final NumberFormat _numberFormat = NumberFormat('#,##0', 'en');

  /// Resolves a possibly-relative image path (e.g. `/storage/drivers/x.jpg`)
  /// to an absolute URL against the API host. Returns null when [path] is
  /// empty so callers can show a fallback. Avoids the "No host specified in
  /// URI file://…" crash from feeding a bare path to [NetworkImage].
  static String? imageUrl(String? path) {
    if (path == null || path.trim().isEmpty) return null;
    if (path.startsWith('http')) return path;
    final host = AppConstants.baseUrl.replaceFirst('/api/v1', '');
    return '$host/${path.startsWith('/') ? path.substring(1) : path}';
  }

  /// Currency symbol from `/config` (e.g. ৳, $, ₹). Falls back to ৳.
  static String get currencySymbol {
    final s = ConfigService.getCached().currencySymbol;
    return s.isNotEmpty ? s : '৳';
  }

  /// Format an amount using the currency symbol + position from `/config`
  /// (e.g. ৳120, $120, or 120﷼). One chokepoint → every screen is dynamic.
  static String currency(num amount) {
    final value = _numberFormat.format(amount);
    return ConfigService.getCached().currencyPosition == 'after'
        ? '$value$currencySymbol'
        : '$currencySymbol$value';
  }

  /// e.g. 18 Jun 2026
  static String formatDate(DateTime date) =>
      DateFormat('dd MMM yyyy').format(date);

  /// e.g. 18 Jun 2026, 2:40 PM
  static String formatDateTime(DateTime date) =>
      DateFormat('dd MMM yyyy, h:mm a').format(date);

  /// e.g. 2:40 PM
  static String formatTime(DateTime date) =>
      DateFormat('h:mm a').format(date);

  /// Distance in km, kept to one decimal, e.g. 3.4 km
  static String formatDistance(double meters) {
    if (meters < 1000) return '${meters.toStringAsFixed(0)} m';
    return '${(meters / 1000).toStringAsFixed(1)} km';
  }

  /// Duration in human form, e.g. 12 min / 1 hr 5 min
  static String formatDuration(int seconds) {
    final minutes = (seconds / 60).round();
    if (minutes < 60) return '$minutes min';
    final hrs = minutes ~/ 60;
    final mins = minutes % 60;
    return mins == 0 ? '$hrs hr' : '$hrs hr $mins min';
  }

  static void dismissKeyboard(BuildContext context) {
    FocusScope.of(context).unfocus();
  }
}
