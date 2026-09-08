class OrderModel {
  final int id;
  final String orderNumber;
  final String type; // ride/parcel
  final String? serviceIcon; // icon URL from the API (service_icon)
  final String status;
  final String pickupAddress;
  final String dropAddress;
  final String totalAmount;
  final String paymentMethod;
  final String? driverName;
  final String? driverAvatar;
  final String createdAt;
  final String? completedAt;

  OrderModel({
    required this.id,
    this.orderNumber = '',
    this.type = 'ride',
    this.serviceIcon,
    this.status = '',
    this.pickupAddress = '',
    this.dropAddress = '',
    this.totalAmount = '0.00',
    this.paymentMethod = '',
    this.driverName,
    this.driverAvatar,
    this.createdAt = '',
    this.completedAt,
  });

  factory OrderModel.fromJson(Map<String, dynamic> json) {
    return OrderModel(
      id: json['id'] ?? 0,
      orderNumber: json['order_number'] ?? '',
      type: json['type'] ?? 'ride',
      serviceIcon: json['service_icon'],
      status: json['status'] ?? '',
      pickupAddress: json['pickup_address'] ?? '',
      dropAddress: json['drop_address'] ?? '',
      totalAmount: (json['total_amount'] ?? '0.00').toString(),
      paymentMethod: json['payment_method'] ?? '',
      driverName: json['driver_name'],
      driverAvatar: json['driver_avatar'],
      createdAt: json['created_at'] ?? '',
      completedAt: json['completed_at'],
    );
  }

  bool get isOngoing => !['completed', 'cancelled', 'delivered'].contains(status);
}

double _d(dynamic v) {
  if (v is num) return v.toDouble();
  return double.tryParse(v?.toString() ?? '') ?? 0;
}

/// Detailed order for the detail/invoice screens — matches the structured
/// `GET /user/orders/{id}` response (nested pickup/drop/fare/driver).
class OrderDetailModel {
  final int id;
  final String orderNumber;
  final String type; // ride/parcel
  final String status;
  final String pickupAddress;
  final String dropAddress;
  final String distanceKm;
  final int durationMinutes;
  final FareDetail fare;
  final String paymentMethod;
  final String paymentStatus; // pending/paid
  final OrderDriver? driver;
  final String createdAt;
  final String? completedAt;

  OrderDetailModel({
    required this.id,
    this.orderNumber = '',
    this.type = 'ride',
    this.status = '',
    this.pickupAddress = '',
    this.dropAddress = '',
    this.distanceKm = '0',
    this.durationMinutes = 0,
    required this.fare,
    this.paymentMethod = '',
    this.paymentStatus = '',
    this.driver,
    this.createdAt = '',
    this.completedAt,
  });

  bool get isOngoing =>
      !['completed', 'cancelled', 'delivered'].contains(status);

  factory OrderDetailModel.fromJson(Map<String, dynamic> json) {
    final pickup = json['pickup'] is Map ? json['pickup'] as Map : const {};
    final drop = json['drop'] is Map ? json['drop'] as Map : const {};
    return OrderDetailModel(
      id: json['id'] ?? 0,
      orderNumber: json['order_number'] ?? '',
      type: json['type'] ?? 'ride',
      status: json['status'] ?? '',
      pickupAddress: (pickup['address'] ?? '').toString(),
      dropAddress: (drop['address'] ?? '').toString(),
      distanceKm: (json['distance_km'] ?? '0').toString(),
      durationMinutes: (json['duration_minutes'] as num?)?.toInt() ?? 0,
      fare: FareDetail.fromJson(
          json['fare'] is Map ? Map<String, dynamic>.from(json['fare']) : {}),
      paymentMethod: json['payment_method'] ?? '',
      paymentStatus: json['payment_status'] ?? '',
      driver: json['driver'] is Map
          ? OrderDriver.fromJson(Map<String, dynamic>.from(json['driver']))
          : null,
      createdAt: json['created_at'] ?? '',
      completedAt: json['completed_at'],
    );
  }
}

class FareDetail {
  final double baseFare;
  final double distanceCharge;
  final double timeCharge;
  final double deliveryCharge;
  final double surgeAmount;
  final double couponDiscount;
  final double tip;
  final double total;

  FareDetail({
    this.baseFare = 0,
    this.distanceCharge = 0,
    this.timeCharge = 0,
    this.deliveryCharge = 0,
    this.surgeAmount = 0,
    this.couponDiscount = 0,
    this.tip = 0,
    this.total = 0,
  });

  factory FareDetail.fromJson(Map<String, dynamic> json) => FareDetail(
        baseFare: _d(json['base_fare']),
        distanceCharge: _d(json['distance_charge']),
        timeCharge: _d(json['time_charge']),
        deliveryCharge: _d(json['delivery_charge']),
        surgeAmount: _d(json['surge_amount']),
        couponDiscount: _d(json['coupon_discount']),
        tip: _d(json['tip']),
        total: _d(json['total']),
      );

  /// Ordered non-zero line items for display. `discount` items are subtracted.
  List<({String labelKey, double amount, bool discount})> get lines {
    final all = <({String labelKey, double amount, bool discount})>[
      (labelKey: 'history.base_fare', amount: baseFare, discount: false),
      (labelKey: 'history.distance_charge', amount: distanceCharge, discount: false),
      (labelKey: 'history.time_charge', amount: timeCharge, discount: false),
      (labelKey: 'history.delivery_charge', amount: deliveryCharge, discount: false),
      (labelKey: 'history.surge', amount: surgeAmount, discount: false),
      (labelKey: 'history.tip', amount: tip, discount: false),
      (labelKey: 'history.coupon_discount', amount: couponDiscount, discount: true),
    ];
    return all.where((e) => e.amount > 0).toList();
  }
}

class OrderDriver {
  final String name;
  final String phone;
  final String vehicle;
  final double rating;

  OrderDriver({
    this.name = '',
    this.phone = '',
    this.vehicle = '',
    this.rating = 0,
  });

  factory OrderDriver.fromJson(Map<String, dynamic> json) => OrderDriver(
        name: json['name'] ?? '',
        phone: json['phone'] ?? '',
        vehicle: (json['vehicle'] ?? '').toString(),
        rating: _d(json['rating']),
      );
}
