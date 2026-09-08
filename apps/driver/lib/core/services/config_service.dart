import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

import '../constants/api_endpoints.dart';
import '../models/config_model.dart';
import '../network/dio_client.dart';
import '../utils/currency_helper.dart';

/// Loads + caches the dynamic app config from `/config`.
/// - In-memory cache for the session
/// - SharedPreferences cache for offline fallback
/// - Drives CurrencyHelper so all amounts use the admin's currency settings
class ConfigService {
  ConfigService._();

  static const String _prefsKey = 'cached_config';
  static ConfigModel? _cached;

  static ConfigModel? get cachedOrNull => _cached;

  /// The loaded config — call only after [load]/[getCached] has run.
  static ConfigModel get current => _cached!;

  static void setCached(ConfigModel config) {
    _cached = config;
    CurrencyHelper.init(config.currencySymbol, config.currencyPosition);
  }

  /// Fetch fresh from API → cache + persist; on network failure fall back to
  /// the last persisted config (offline). Returns cached instance if present.
  static Future<ConfigModel> getCached() async {
    if (_cached != null) return _cached!;
    return load();
  }

  static Future<ConfigModel> load() async {
    try {
      final res = await DioClient.instance.get(ApiEndpoints.config);
      final data = ((res.data['data'] ?? res.data) as Map)
          .cast<String, dynamic>();
      final config = ConfigModel.fromJson(data);
      setCached(config);
      // Persist raw for offline fallback.
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_prefsKey, jsonEncode(data));
      return config;
    } catch (_) {
      final prefs = await SharedPreferences.getInstance();
      final raw = prefs.getString(_prefsKey);
      if (raw != null) {
        final config =
            ConfigModel.fromJson(jsonDecode(raw) as Map<String, dynamic>);
        setCached(config);
        return config;
      }
      rethrow;
    }
  }
}
