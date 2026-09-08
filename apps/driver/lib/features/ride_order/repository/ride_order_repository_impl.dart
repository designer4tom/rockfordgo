import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/dio_client.dart';
import '../model/active_ride_model.dart';
import 'ride_order_repository.dart';

class RideOrderRepositoryImpl implements RideOrderRepository {
  final DioClient _client;
  RideOrderRepositoryImpl({DioClient? client})
      : _client = client ?? DioClient.instance;

  @override
  Future<ActiveRideModel?> loadActiveOrder(int orderId) async {
    try {
      final res = await _client.get(ApiEndpoints.orderDetail(orderId));
      final data = res.data['data'] ?? res.data;
      if (data == null) return null;
      return ActiveRideModel.fromJson((data as Map).cast<String, dynamic>());
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  @override
  Future<bool> updateStatus(int orderId, String status, {String? otp}) async {
    try {
      final res = await _client.post(
        ApiEndpoints.rideUpdateStatus,
        data: {
          'order_id': orderId,
          'status': status,
          'otp': ?otp,
        },
      );
      final body = res.data as Map;
      return body['success'] == true || body['status'] == true;
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  @override
  Future<bool> rate(int orderId, int rating, String? comment) async {
    try {
      final res = await _client.post(
        ApiEndpoints.rideRate(orderId),
        data: {'rating': rating, 'comment': ?comment},
      );
      final body = res.data as Map;
      return body['success'] == true || body['status'] == true;
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }
}
