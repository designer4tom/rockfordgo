import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/dio_client.dart';
import '../../ride_order/model/active_ride_model.dart';
import '../model/order_request_model.dart';
import 'order_request_repository.dart';

class OrderRequestRepositoryImpl implements OrderRequestRepository {
  final DioClient _client;
  OrderRequestRepositoryImpl({DioClient? client})
      : _client = client ?? DioClient.instance;

  @override
  Future<ActiveRideModel?> accept(int orderId) async {
    try {
      final res = await _client.post(
        ApiEndpoints.rideRespond,
        data: {'order_id': orderId, 'action': 'accept'},
      );
      final body = res.data as Map;
      final ok = body['success'] == true || body['status'] == true;
      if (!ok) return null;
      // The accept response carries the authoritative fare breakdown — parse it
      // so the ride screen can show the exact figures even if the order-detail
      // endpoint omits them.
      final data = body['data'];
      if (data is! Map) return null;
      return ActiveRideModel.fromJson(data.cast<String, dynamic>());
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  @override
  Future<bool> reject(int orderId) => _respond(orderId, 'reject');

  Future<bool> _respond(int orderId, String action) async {
    try {
      final res = await _client.post(
        ApiEndpoints.rideRespond,
        data: {'order_id': orderId, 'action': action},
      );
      final body = res.data as Map;
      return body['success'] == true || body['status'] == true;
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  @override
  Future<OrderRequestModel?> fetchRequest(int orderId) async {
    try {
      final res = await _client.get(ApiEndpoints.orderDetail(orderId));
      final data = res.data['data'];
      if (data == null) return null;
      return OrderRequestModel.fromJson((data as Map).cast<String, dynamic>());
    } catch (_) {
      return null;
    }
  }
}
