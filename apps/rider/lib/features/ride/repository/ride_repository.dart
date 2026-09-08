import '../../location/model/place_model.dart';
import '../model/coupon_model.dart';
import '../model/fare_estimate_model.dart';
import '../model/ride_booking_model.dart';
import '../model/ride_status_model.dart';

abstract class RideRepository {
  Future<FareEstimateModel> getFareEstimate({
    required int vehicleCategoryId,
    required PlaceModel pickup,
    required PlaceModel drop,
    List<PlaceModel>? stops,
  });

  Future<RideBookingModel> bookRide({
    required int vehicleCategoryId,
    required PlaceModel pickup,
    required PlaceModel drop,
    required String paymentMethod,
    List<PlaceModel>? stops,
    String? couponCode,
    DateTime? scheduledAt,
    bool rideShare,
  });

  Future<RideStatusModel> getStatus(int orderId);

  Future<Map<String, dynamic>> cancelRide(int orderId, String reason);

  Future<void> addTip(int orderId, double amount);

  Future<void> rateRide(
    int orderId,
    int rating,
    String? comment,
    List<String> tags,
  );

  Future<String> generateShareLink(int orderId);

  Future<CouponModel> validateCoupon(
    String code,
    String serviceType,
    double amount,
  );
}
