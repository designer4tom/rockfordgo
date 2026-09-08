import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../model/order_model.dart';
import '../model/shift_model.dart';
import '../repository/history_repository.dart';

class HistoryProvider extends ChangeNotifier {
  final HistoryRepository _repository;
  HistoryProvider(this._repository);

  List<OrderModel> orders = [];
  List<ShiftModel> shifts = [];
  String filterType = 'all';

  bool loadingOrders = false;
  bool loadingShifts = false;
  bool _loadingMore = false;
  bool _hasMore = true;
  int _page = 1;
  String? error;

  Future<void> loadOrders({bool refresh = false}) async {
    if (refresh) {
      _page = 1;
      _hasMore = true;
      orders = [];
      loadingOrders = true;
      notifyListeners();
    }
    if (_loadingMore || !_hasMore) return;
    _loadingMore = true;
    try {
      final list =
          await _repository.getOrders(page: _page, type: filterType);
      if (list.isEmpty) {
        _hasMore = false;
      } else {
        orders.addAll(list);
        _page++;
      }
      error = null;
    } on ApiException catch (e) {
      error = e.message;
    }
    loadingOrders = false;
    _loadingMore = false;
    notifyListeners();
  }

  void setFilter(String type) {
    if (filterType == type) return;
    filterType = type;
    loadOrders(refresh: true);
  }

  Future<void> loadShifts() async {
    loadingShifts = true;
    notifyListeners();
    try {
      shifts = await _repository.getShifts();
    } on ApiException catch (e) {
      error = e.message;
    }
    loadingShifts = false;
    notifyListeners();
  }

  Future<OrderDetailModel?> getOrderDetail(int id) async {
    try {
      return await _repository.getOrderDetail(id);
    } on ApiException catch (e) {
      error = e.message;
      notifyListeners();
      return null;
    }
  }
}
