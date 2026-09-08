import '../../ride_order/model/active_ride_model.dart';
import '../model/order_request_model.dart';

abstract class OrderRequestRepository {
  /// Accept a request. Returns the accepted ride (including its fare
  /// breakdown) on success, or null on failure.
  Future<ActiveRideModel?> accept(int orderId);

  /// Reject/decline a request.
  Future<bool> reject(int orderId);

  /// Fetch request details (used for FCM-triggered open). Null if no longer
  /// available/expired.
  Future<OrderRequestModel?> fetchRequest(int orderId);
}
