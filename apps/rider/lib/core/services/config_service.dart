import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../constants/api_endpoints.dart';
import '../../features/config/model/config_model.dart';

/// Fetches `GET /config` once at startup and caches it. Other services
/// (Pusher, Maps) read keys/flags from the cached config instead of
/// hard-coding them.
///
/// Extends [ChangeNotifier] so widgets built before `load()` resolves (e.g.
/// the phone entry screen, which renders on the first frame while `/config`
/// is still in flight) can listen via `context.watch<ConfigService>()` and
/// rebuild with the real backend values instead of staying on the fallback.
class ConfigService extends ChangeNotifier {
  final Dio _dio;

  ConfigService(this._dio);

  static ConfigModel? _cached;

  /// The last loaded config, or null if it hasn't loaded yet.
  static ConfigModel? get cached => _cached;

  /// Cached config if available, otherwise the compile-time fallback.
  static ConfigModel getCached() => _cached ?? ConfigModel.fallback();

  Future<ConfigModel> load() async {
    try {
      final res = await _dio.get(ApiEndpoints.config);
      _cached = ConfigModel.fromJson(Map<String, dynamic>.from(res.data['data']));
    } catch (e) {
      if (kDebugMode) debugPrint('ConfigService load failed, using fallback: $e');
      _cached = ConfigModel.fallback();
    }
    notifyListeners();
    return _cached!;
  }
}
