class VehicleCategoryModel {
  final int id;
  final String name;
  final String? icon;
  final int capacity;
  final String baseFare;
  final String perKmRate;
  final String perMinuteRate;
  final String minimumFare;
  final int estimatedArrival;
  final int availableDrivers;
  final bool surgeActive;
  final double surgeMultiplier;

  VehicleCategoryModel({
    required this.id,
    required this.name,
    this.icon,
    this.capacity = 1,
    this.baseFare = '0.00',
    this.perKmRate = '0.00',
    this.perMinuteRate = '0.00',
    this.minimumFare = '0.00',
    this.estimatedArrival = 0,
    this.availableDrivers = 0,
    this.surgeActive = false,
    this.surgeMultiplier = 1.0,
  });

  factory VehicleCategoryModel.fromJson(Map<String, dynamic> json) {
    return VehicleCategoryModel(
      id: json['id'],
      name: json['name'] ?? '',
      icon: json['icon'],
      capacity: json['capacity'] ?? 1,
      baseFare: (json['base_fare'] ?? '0.00').toString(),
      perKmRate: (json['per_km_rate'] ?? '0.00').toString(),
      perMinuteRate: (json['per_minute_rate'] ?? '0.00').toString(),
      minimumFare: (json['minimum_fare'] ?? '0.00').toString(),
      estimatedArrival: json['estimated_arrival'] ?? 0,
      availableDrivers: json['available_drivers'] ?? 0,
      surgeActive: json['surge_active'] ?? false,
      surgeMultiplier: VehicleCategoryModel._toDouble(json['surge_multiplier']),
    );
  }

  static double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 1.0;
    return 1.0;
  }
}
