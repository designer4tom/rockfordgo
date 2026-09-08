import '../model/active_order_model.dart';
import '../model/tracking_model.dart';

abstract class TrackingRepository {
  /// The user's current in-progress order (for resume after app restart).
  Future<ActiveOrderModel> getActiveOrder();

  Future<TrackingModel> getStatus(int orderId, String type);

  Future<Map<String, dynamic>> cancelOrder(
    int orderId,
    String type,
    String reason,
  );

  Future<void> rate(
    int orderId,
    String type,
    int rating,
    String? comment,
    List<String> tags,
  );

  Future<void> addTip(int orderId, String type, double amount);

  Future<String> generateShareLink(int orderId);

  Future<void> triggerSos(int orderId, double? lat, double? lng);
}
