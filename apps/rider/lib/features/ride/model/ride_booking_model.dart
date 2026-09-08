import '../../location/model/place_model.dart';
import 'fare_estimate_model.dart';

class RideBookingModel {
  final int orderId;
  final String orderNumber;
  final String status;
  final String otp;
  final FareBreakdown fare;
  final String paymentMethod;
  final PlaceModel? pickup;
  final PlaceModel? drop;

  RideBookingModel({
    required this.orderId,
    this.orderNumber = '',
    this.status = 'pending',
    this.otp = '',
    required this.fare,
    this.paymentMethod = 'cash',
    this.pickup,
    this.drop,
  });

  factory RideBookingModel.fromJson(Map<String, dynamic> json) {
    return RideBookingModel(
      orderId: json['order_id'] ?? json['id'] ?? 0,
      orderNumber: json['order_number'] ?? '',
      status: json['status'] ?? 'pending',
      otp: (json['otp'] ?? '').toString(),
      fare: json['fare'] != null
          ? FareBreakdown.fromJson(Map<String, dynamic>.from(json['fare']))
          : FareBreakdown(),
      paymentMethod: json['payment_method'] ?? 'cash',
      pickup: json['pickup'] != null
          ? PlaceModel.fromJson(Map<String, dynamic>.from(json['pickup']))
          : null,
      drop: json['drop'] != null
          ? PlaceModel.fromJson(Map<String, dynamic>.from(json['drop']))
          : null,
    );
  }
}
