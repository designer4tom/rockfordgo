import '../../ride/model/ride_status_model.dart';

class TrackingModel {
  final String status;
  final DriverInfo? driver;
  final double? driverLat;
  final double? driverLng;
  final double? driverBearing;
  final double? pickupLat;
  final double? pickupLng;
  final double? dropLat;
  final double? dropLng;
  final String paymentMethod;
  final String totalAmount;
  final String? otp;
  final int? estimatedArrival;

  TrackingModel({
    required this.status,
    this.driver,
    this.driverLat,
    this.driverLng,
    this.driverBearing,
    this.pickupLat,
    this.pickupLng,
    this.dropLat,
    this.dropLng,
    this.paymentMethod = 'cash',
    this.totalAmount = '0.00',
    this.otp,
    this.estimatedArrival,
  });

  factory TrackingModel.fromJson(Map<String, dynamic> json) {
    final driver = json['driver'] != null
        ? DriverInfo.fromJson(Map<String, dynamic>.from(json['driver']))
        : null;
    final pickup = json['pickup'] is Map ? json['pickup'] as Map : null;
    final drop = json['drop'] is Map ? json['drop'] as Map : null;
    return TrackingModel(
      status: json['status'] ?? 'pending',
      driver: driver,
      driverLat: _toDoubleN(json['driver_lat'] ?? driver?.currentLat),
      driverLng: _toDoubleN(json['driver_lng'] ?? driver?.currentLng),
      driverBearing: _toDoubleN(json['driver_bearing'] ?? json['bearing']),
      pickupLat: _toDoubleN(pickup?['lat']),
      pickupLng: _toDoubleN(pickup?['lng']),
      dropLat: _toDoubleN(drop?['lat']),
      dropLng: _toDoubleN(drop?['lng']),
      paymentMethod: json['payment_method'] ?? 'cash',
      totalAmount: (json['total_amount'] ?? '0.00').toString(),
      otp: json['otp']?.toString(),
      estimatedArrival: json['estimated_arrival'],
    );
  }

  TrackingModel copyWith({
    String? status,
    DriverInfo? driver,
    double? driverLat,
    double? driverLng,
    double? driverBearing,
    double? pickupLat,
    double? pickupLng,
    double? dropLat,
    double? dropLng,
    String? otp,
    int? estimatedArrival,
  }) {
    return TrackingModel(
      status: status ?? this.status,
      driver: driver ?? this.driver,
      driverLat: driverLat ?? this.driverLat,
      driverLng: driverLng ?? this.driverLng,
      driverBearing: driverBearing ?? this.driverBearing,
      pickupLat: pickupLat ?? this.pickupLat,
      pickupLng: pickupLng ?? this.pickupLng,
      dropLat: dropLat ?? this.dropLat,
      dropLng: dropLng ?? this.dropLng,
      otp: otp ?? this.otp,
      estimatedArrival: estimatedArrival ?? this.estimatedArrival,
    );
  }

  static double? _toDoubleN(dynamic value) {
    if (value == null) return null;
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value);
    return null;
  }
}
