import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/dio_client.dart';
import '../model/order_model.dart';
import '../model/shift_model.dart';

class HistoryRepository {
  final DioClient _client;
  HistoryRepository({DioClient? client})
      : _client = client ?? DioClient.instance;

  Future<List<OrderModel>> getOrders({int page = 1, String type = 'all'}) async {
    try {
      final query = {'page': page, if (type != 'all') 'type': type};
      final res = await _client.get(ApiEndpoints.orders, query: query);
      return _list(res.data)
          .map((e) => OrderModel.fromJson((e as Map).cast<String, dynamic>()))
          .toList();
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<List<ShiftModel>> getShifts() async {
    try {
      final res = await _client.get(ApiEndpoints.shifts);
      return _list(res.data)
          .map((e) => ShiftModel.fromJson((e as Map).cast<String, dynamic>()))
          .toList();
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<OrderDetailModel> getOrderDetail(int id) async {
    try {
      final res = await _client.get(ApiEndpoints.orderDetail(id));
      final data = (res.data['data'] ?? res.data) as Map;
      return OrderDetailModel.fromJson(data.cast<String, dynamic>());
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  List _list(dynamic body) {
    final data = body['data'];
    if (data is List) return data;
    if (data is Map && data['data'] is List) return data['data'] as List;
    return const [];
  }
}
