class DriverMarker {
  final int id;
  final double lat;
  final double lng;
  final String type; // e.g. 'bike', 'car'
  final double heading;

  DriverMarker({
    required this.id,
    required this.lat,
    required this.lng,
    this.type = 'car',
    this.heading = 0,
  });

  factory DriverMarker.fromJson(Map<String, dynamic> json) {
    return DriverMarker(
      id: json['id'],
      lat: _toDouble(json['lat'] ?? json['latitude']),
      lng: _toDouble(json['lng'] ?? json['longitude']),
      type: json['type'] ?? json['vehicle_type'] ?? 'car',
      heading: _toDouble(json['heading'] ?? json['bearing']),
    );
  }

  static double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }
}
