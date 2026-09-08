import 'package:flutter/foundation.dart';

import '../models/config_model.dart';
import '../services/config_service.dart';

/// Holds the dynamic app config (currency, pusher keys, amounts, flags).
/// Loading + caching + offline fallback live in [ConfigService].
class ConfigProvider extends ChangeNotifier {
  ConfigModel? _config;
  ConfigModel? get config => _config;

  Future<void> load() async {
    try {
      _config = await ConfigService.load();
      notifyListeners();
    } catch (_) {
      // Non-fatal — splash continues even if config fails (no cache yet).
    }
  }
}
