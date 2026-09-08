import 'package:flutter/foundation.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/dio_client.dart';
import '../model/performance_model.dart';

class PerformanceProvider extends ChangeNotifier {
  final DioClient _client;
  PerformanceProvider({DioClient? client})
      : _client = client ?? DioClient.instance;

  PerformanceModel? performance;
  bool loading = false;
  String? error;

  Future<void> loadPerformance() async {
    loading = true;
    notifyListeners();
    try {
      final res = await _client.get(ApiEndpoints.performance);
      final data = (res.data['data'] ?? res.data) as Map;
      performance = PerformanceModel.fromJson(data.cast<String, dynamic>());
      error = null;
    } catch (e) {
      error = DioClient.toApiException(e).message;
    }
    loading = false;
    notifyListeners();
  }
}
