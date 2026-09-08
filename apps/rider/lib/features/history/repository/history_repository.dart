import '../../../core/network/api_response.dart';
import '../model/order_model.dart';

abstract class HistoryRepository {
  Future<({List<OrderModel> items, PaginationMeta? meta})> getOrders(
    int page,
    String filterType,
  );

  Future<OrderDetailModel> getOrderDetail(int id);
}
