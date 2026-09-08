double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  if (value is String) return double.tryParse(value) ?? 0.0;
  return 0.0;
}

/// Returns the first value that is present and non-empty, as a String.
String _firstNonEmpty(List<dynamic> values) {
  for (final v in values) {
    if (v != null && v.toString().isNotEmpty) return v.toString();
  }
  return '';
}

class VehicleInfo {
  final String model;
  final String plateNumber;
  final String color;
  final String type;

  VehicleInfo({
    this.model = '',
    this.plateNumber = '',
    this.color = '',
    this.type = '',
  });

  factory VehicleInfo.fromJson(Map<String, dynamic> json) {
    return VehicleInfo(
      model: _firstNonEmpty([json['model'], json['name'], json['vehicle_model']]),
      plateNumber: _firstNonEmpty([
        json['plate_number'],
        json['plate'],
        json['registration_number'],
        json['license_plate'],
      ]),
      color: _firstNonEmpty([json['color'], json['vehicle_color']]),
      type: _firstNonEmpty([json['type'], json['vehicle_type']]),
    );
  }
}

class DriverInfo {
  final int id;
  final String name;
  final String phone;
  final String? avatar;
  final String rating;
  final VehicleInfo vehicle;
  final double currentLat;
  final double currentLng;
  final int estimatedArrival;

  DriverInfo({
    required this.id,
    this.name = '',
    this.phone = '',
    this.avatar,
    this.rating = '0.0',
    required this.vehicle,
    this.currentLat = 0,
    this.currentLng = 0,
    this.estimatedArrival = 0,
  });

  factory DriverInfo.fromJson(Map<String, dynamic> json) {
    final avatar = _firstNonEmpty([
      json['avatar'],
      json['photo'],
      json['image'],
      json['profile_photo'],
      json['profile_image'],
    ]);
    final vehicleJson = json['vehicle'] ?? json['vehicle_info'] ?? json['car'];
    return DriverInfo(
      id: json['id'] ?? json['driver_id'] ?? 0,
      name: _firstNonEmpty(
          [json['name'], json['full_name'], json['driver_name']]),
      phone: _firstNonEmpty(
          [json['phone'], json['phone_number'], json['mobile']]),
      avatar: avatar.isEmpty ? null : avatar,
      rating: _firstNonEmpty(
          [json['rating'], json['avg_rating'], json['ratings'], '0.0']),
      vehicle: vehicleJson is Map
          ? VehicleInfo.fromJson(Map<String, dynamic>.from(vehicleJson))
          : VehicleInfo(),
      currentLat: _toDouble(json['current_lat'] ?? json['lat']),
      currentLng: _toDouble(json['current_lng'] ?? json['lng']),
      estimatedArrival:
          json['estimated_arrival'] ?? json['eta'] ?? 0,
    );
  }
}

class RideStatusModel {
  final String status; // pending/accepted/.../completed
  final String message;
  final DriverInfo? driver;
  final String? otp;

  RideStatusModel({
    required this.status,
    this.message = '',
    this.driver,
    this.otp,
  });

  factory RideStatusModel.fromJson(Map<String, dynamic> json) {
    return RideStatusModel(
      status: json['status'] ?? 'pending',
      message: json['message'] ?? '',
      driver: json['driver'] != null
          ? DriverInfo.fromJson(Map<String, dynamic>.from(json['driver']))
          : null,
      otp: json['otp']?.toString(),
    );
  }
}
