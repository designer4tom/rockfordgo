/// Driver account model returned by auth/status and profile endpoints.
class DriverModel {
  final int id;
  final String name;
  final String phone;
  final String? email;
  final String? avatar;
  final String status; // pending/approved/rejected/suspended/blocked
  final bool isOnline;
  final String walletBalance;
  final String dueAmount;
  final String averageRating;
  final int totalTrips;
  final ZoneInfo? zone;
  final ZoneInfo? currentZone;
  final VehicleInfo? vehicle;
  final String? rejectionReason;

  DriverModel({
    required this.id,
    required this.name,
    required this.phone,
    this.email,
    this.avatar,
    required this.status,
    required this.isOnline,
    required this.walletBalance,
    required this.dueAmount,
    required this.averageRating,
    required this.totalTrips,
    this.zone,
    this.currentZone,
    this.vehicle,
    this.rejectionReason,
  });

  bool get isApproved => status == 'approved';
  bool get isPending => status == 'pending';
  bool get isRejected => status == 'rejected';
  bool get isBlocked => status == 'blocked' || status == 'suspended';

  factory DriverModel.fromJson(Map<String, dynamic> json) {
    return DriverModel(
      id: _toInt(json['id']),
      name: (json['name'] ?? '').toString(),
      phone: (json['phone'] ?? '').toString(),
      email: json['email']?.toString(),
      avatar: json['avatar']?.toString(),
      status: (json['status'] ?? 'pending').toString(),
      isOnline: json['is_online'] == true || json['is_online'] == 1,
      walletBalance: (json['wallet_balance'] ?? '0').toString(),
      dueAmount: (json['due_amount'] ?? '0').toString(),
      averageRating: (json['average_rating'] ?? '0').toString(),
      totalTrips: _toInt(json['total_trips']),
      zone: json['zone'] is Map
          ? ZoneInfo.fromJson(json['zone'] as Map<String, dynamic>)
          : null,
      currentZone: json['current_zone'] is Map
          ? ZoneInfo.fromJson(json['current_zone'] as Map<String, dynamic>)
          : (json['zone'] is Map
              ? ZoneInfo.fromJson(json['zone'] as Map<String, dynamic>)
              : null),
      vehicle: json['vehicle'] is Map
          ? VehicleInfo.fromJson(json['vehicle'] as Map<String, dynamic>)
          : null,
      rejectionReason: json['rejection_reason']?.toString(),
    );
  }

  static int _toInt(dynamic v) =>
      v is int ? v : int.tryParse(v?.toString() ?? '') ?? 0;
}

class ZoneInfo {
  final int id;
  final String name;
  ZoneInfo({required this.id, required this.name});

  factory ZoneInfo.fromJson(Map<String, dynamic> json) => ZoneInfo(
        id: DriverModel._toInt(json['id']),
        name: (json['name'] ?? '').toString(),
      );
}

class VehicleInfo {
  final int? categoryId;
  final String? categoryName;
  final String? make;
  final String? model;
  final String? year;
  final String? color;
  final String? registrationNumber;

  VehicleInfo({
    this.categoryId,
    this.categoryName,
    this.make,
    this.model,
    this.year,
    this.color,
    this.registrationNumber,
  });

  factory VehicleInfo.fromJson(Map<String, dynamic> json) => VehicleInfo(
        categoryId: json['category_id'] == null
            ? null
            : DriverModel._toInt(json['category_id']),
        categoryName: json['category_name']?.toString() ??
            (json['category'] is Map
                ? json['category']['name']?.toString()
                : json['category']?.toString()),
        make: json['make']?.toString(),
        model: json['model']?.toString(),
        year: json['year']?.toString(),
        color: json['color']?.toString(),
        registrationNumber: json['registration_number']?.toString(),
      );
}
