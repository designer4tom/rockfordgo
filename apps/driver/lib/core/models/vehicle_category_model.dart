/// Vehicle category for driver registration.
/// Source: `GET /driver/vehicle-categories` (location-free).
class VehicleCategoryModel {
  final int id;
  final String name;
  final String? icon;
  final int capacity;
  final int? serviceId;
  final String? serviceName;
  final String? serviceType; // ride | parcel ...

  VehicleCategoryModel({
    required this.id,
    required this.name,
    this.icon,
    required this.capacity,
    this.serviceId,
    this.serviceName,
    this.serviceType,
  });

  factory VehicleCategoryModel.fromJson(Map<String, dynamic> json) {
    int toInt(dynamic v) =>
        v is int ? v : int.tryParse(v?.toString() ?? '') ?? 0;
    return VehicleCategoryModel(
      id: toInt(json['id']),
      name: (json['name'] ?? '').toString(),
      icon: json['icon']?.toString(),
      capacity: toInt(json['capacity']),
      serviceId: json['service_id'] == null ? null : toInt(json['service_id']),
      serviceName: json['service_name']?.toString(),
      serviceType: json['service_type']?.toString(),
    );
  }
}
