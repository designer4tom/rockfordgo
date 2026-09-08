import 'package:flutter/material.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/network/api_response.dart';
import '../model/order_model.dart';
import '../repository/history_repository.dart';

class HistoryProvider extends ChangeNotifier {
  final HistoryRepository _repository;

  HistoryProvider(this._repository);

  List<OrderModel> orders = [];
  String filterType = 'all'; // all/ride/parcel — drives the API (type filter)
  String statusFilter = 'all'; // all/upcoming/completed/cancelled — client side
  PaginationMeta? meta;
  bool isLoading = false;
  bool isLoadingMore = false;
  String? error;

  /// Bucket a raw order status into one of the three UI tabs.
  static String statusCategory(String status) {
    if (['completed', 'delivered'].contains(status)) return 'completed';
    if (['cancelled', 'rejected', 'declined'].contains(status)) {
      return 'cancelled';
    }
    return 'upcoming';
  }

  /// Orders after applying the status tab (type filter is applied by the API).
  List<OrderModel> get filteredOrders {
    if (statusFilter == 'all') return orders;
    return orders
        .where((o) => statusCategory(o.status) == statusFilter)
        .toList();
  }

  void setStatusFilter(String status) {
    if (statusFilter == status) return;
    statusFilter = status;
    notifyListeners();
  }

  Future<void> loadOrders({bool refresh = false}) async {
    if (refresh) {
      orders = [];
      meta = null;
    }
    final nextPage = (meta?.currentPage ?? 0) + 1;
    if (meta != null && !meta!.hasMore && !refresh) return;

    if (nextPage == 1) {
      isLoading = true;
    } else {
      isLoadingMore = true;
    }
    notifyListeners();

    try {
      final result = await _repository.getOrders(nextPage, filterType);
      orders.addAll(result.items);
      meta = result.meta;
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      isLoading = false;
      isLoadingMore = false;
      notifyListeners();
    }
  }

  void setFilter(String type) {
    if (filterType == type) return;
    filterType = type;
    notifyListeners();
    loadOrders(refresh: true);
  }

  Future<OrderDetailModel> getOrderDetail(int id) =>
      _repository.getOrderDetail(id);
}
