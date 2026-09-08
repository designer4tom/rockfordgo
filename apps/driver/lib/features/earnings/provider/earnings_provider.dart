import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../model/earnings_model.dart';
import '../repository/earnings_repository.dart';

class EarningsProvider extends ChangeNotifier {
  final EarningsRepository _repository;
  EarningsProvider(this._repository);

  EarningsModel? earnings;
  Map<String, dynamic>? summary; // today/week/month/lifetime
  List<ChartData> chartData = [];
  double averageRating = 0; // from /performance
  String period = 'today';

  bool loading = false;
  String? error;

  Future<void> init() async {
    // 3 sources in parallel: earnings, week chart, performance rating.
    await Future.wait([
      loadEarnings(period),
      loadChart('week'),
      loadSummary(),
      loadAverageRating(),
    ]);
  }

  Future<void> loadAverageRating() async {
    try {
      averageRating = await _repository.getAverageRating();
      notifyListeners();
    } catch (_) {}
  }

  Future<void> loadEarnings(String p) async {
    loading = true;
    notifyListeners();
    try {
      earnings = await _repository.getEarnings(p);
      error = null;
    } on ApiException catch (e) {
      error = e.message;
    }
    loading = false;
    notifyListeners();
  }

  Future<void> loadSummary() async {
    try {
      summary = await _repository.getSummary();
      notifyListeners();
    } catch (_) {}
  }

  Future<void> loadChart(String p) async {
    try {
      chartData = await _repository.getChart(p);
      notifyListeners();
    } catch (_) {}
  }

  void setPeriod(String p) {
    if (period == p) return;
    period = p;
    notifyListeners();
    loadEarnings(p);
    loadChart(p);
  }
}
