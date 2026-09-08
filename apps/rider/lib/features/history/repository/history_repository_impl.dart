import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_response.dart';
import '../../../core/network/dio_client.dart';
import '../model/order_model.dart';
import 'history_repository.dart';

class HistoryRepositoryImpl implements HistoryRepository {
  final DioClient _client;

  HistoryRepositoryImpl(this._client);

  @override
  Future<({List<OrderModel> items, PaginationMeta? meta})> getOrders(
    int page,
    String filterType,
  ) async {
    try {
      final res = await _client.dio.get(
        ApiEndpoints.orders,
        queryParameters: {
          'page': page,
          if (filterType != 'all') 'type': filterType,
        },
      );
      final list = (res.data['data'] as List? ?? [])
          .map((e) => OrderModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
      final meta = res.data['meta'] != null
          ? PaginationMeta.fromJson(Map<String, dynamic>.from(res.data['meta']))
          : null;
      return (items: list, meta: meta);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<OrderDetailModel> getOrderDetail(int id) async {
    try {
      final res = await _client.dio.get(ApiEndpoints.orderDetail(id));
      return OrderDetailModel.fromJson(
        Map<String, dynamic>.from(res.data['data']),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
