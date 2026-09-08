import 'package:flutter/material.dart';

import '../../../core/network/api_exception.dart';
import '../../history/model/order_model.dart';
import '../../history/provider/history_provider.dart';
import '../../history/repository/history_repository.dart';

/// Derives the rider's performance summary from their order history. Reuses
/// [HistoryRepository] (no dedicated backend endpoint exists) and walks all
/// pages once so the totals are accurate, independent of the History tab's
/// own paging state.
class PerformanceProvider extends ChangeNotifier {
  final HistoryRepository _repository;
  PerformanceProvider(this._repository);

  // Safety cap so a very large history never makes this loop forever.
  static const int _maxPages = 20;

  List<OrderModel> _orders = [];
  bool isLoading = false;
  bool _loaded = false;
  String? error;

  int get totalTrips => _orders.length;

  int get completed => _orders
      .where((o) => HistoryProvider.statusCategory(o.status) == 'completed')
      .length;

  int get cancelled => _orders
      .where((o) => HistoryProvider.statusCategory(o.status) == 'cancelled')
      .length;

  /// Completion rate over finished (completed + cancelled) trips, 0–100.
  int get completionRate {
    final finished = completed + cancelled;
    if (finished == 0) return 0;
    return ((completed / finished) * 100).round();
  }

  /// Total spent across completed trips.
  double get totalSpent => _orders
      .where((o) => HistoryProvider.statusCategory(o.status) == 'completed')
      .fold(0.0, (sum, o) => sum + (double.tryParse(o.totalAmount) ?? 0));

  /// Most recent completed trips, newest first (max 8) for the list.
  List<OrderModel> get recentRides {
    final done = _orders
        .where((o) => HistoryProvider.statusCategory(o.status) == 'completed')
        .toList();
    return done.take(8).toList();
  }

  /// A simple tier label key driven by activity + reliability.
  String get tierKey {
    if (totalTrips == 0) return 'performance.tier_new';
    if (completionRate >= 90 && totalTrips >= 5) return 'performance.tier_reliable';
    if (completionRate >= 70) return 'performance.tier_regular';
    return 'performance.tier_rider';
  }

  Future<void> load({bool force = false}) async {
    if (isLoading) return;
    if (_loaded && !force) return;
    isLoading = true;
    error = null;
    notifyListeners();

    final collected = <OrderModel>[];
    try {
      var page = 1;
      while (page <= _maxPages) {
        final result = await _repository.getOrders(page, 'all');
        collected.addAll(result.items);
        final meta = result.meta;
        if (meta == null || !meta.hasMore) break;
        page++;
      }
      _orders = collected;
      _loaded = true;
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }
}
