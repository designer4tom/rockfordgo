import '../model/active_ride_model.dart';

abstract class RideOrderRepository {
  Future<ActiveRideModel?> loadActiveOrder(int orderId);
  Future<bool> updateStatus(int orderId, String status, {String? otp});
  Future<bool> rate(int orderId, int rating, String? comment);
}
