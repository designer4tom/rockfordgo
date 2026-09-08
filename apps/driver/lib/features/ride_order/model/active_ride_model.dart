import '../../../core/models/place_info.dart';

class CustomerInfo {
  final String name;
  final String phone;
  final String? avatar;
  final String rating;

  CustomerInfo({
    required this.name,
    required this.phone,
    this.avatar,
    this.rating = '0.0',
  });

  factory CustomerInfo.fromJson(Map<String, dynamic> json) => CustomerInfo(
        name: (json['name'] ?? '').toString(),
        phone: (json['phone'] ?? '').toString(),
        // The backend may expose the photo under any of these keys depending on
        // the resource — accept all so the image isn't silently dropped.
        avatar: (json['avatar'] ??
                json['image'] ??
                json['photo'] ??
                json['profile_image'] ??
                json['profile_photo'] ??
                json['picture'])
            ?.toString(),
        rating: (json['rating'] ?? json['average_rating'] ?? '0.0').toString(),
      );
}

/// Pricing breakdown returned in the ride's `fare` object (accept-ride /
/// order-detail responses). Every field defaults to a safe `'0'` so a missing
/// or null `fare` object never crashes the UI.
class FareInfo {
  final String baseFare;
  final String surgeAmount;
  final String couponDiscount;
  final String tipAmount;
  final String totalFare;
  final String customerPayable;
  final String adminCommission;
  final double commissionPercent;
  final String driverEarning;

  const FareInfo({
    this.baseFare = '0',
    this.surgeAmount = '0',
    this.couponDiscount = '0',
    this.tipAmount = '0',
    this.totalFare = '0',
    this.customerPayable = '0',
    this.adminCommission = '0',
    this.commissionPercent = 0,
    this.driverEarning = '0',
  });

  // Backward-compatible aliases for older callers that referenced the previous
  // gross/commission/net field names.
  String get gross => totalFare;
  String get commission => adminCommission;
  String get net => driverEarning;

  /// True when no meaningful pricing is present (used to decide whether a fare
  /// is worth keeping/seeding).
  bool get isEmpty {
    double v(String s) => double.tryParse(s) ?? 0;
    return v(totalFare) == 0 &&
        v(customerPayable) == 0 &&
        v(adminCommission) == 0 &&
        v(driverEarning) == 0;
  }

  factory FareInfo.fromJson(Map<String, dynamic> json) => FareInfo(
        baseFare: _str(json['base_fare']),
        surgeAmount: _str(json['surge_amount'] ?? json['surge']),
        couponDiscount: _str(json['coupon_discount']),
        tipAmount: _str(json['tip_amount']),
        totalFare: _str(json['total_fare'] ??
            json['gross'] ??
            json['total'] ??
            json['total_amount'] ??
            json['amount'] ??
            json['fare']),
        // Amount the driver collects from the customer. Falls back to the total
        // fare when the backend hasn't sent a distinct payable figure.
        customerPayable: _str(json['customer_payable'] ??
            json['total_fare'] ??
            json['gross'] ??
            json['total'] ??
            json['total_amount'] ??
            json['amount']),
        adminCommission: _str(json['admin_commission'] ?? json['commission']),
        commissionPercent: _toDouble(json['commission_percent']),
        driverEarning:
            _str(json['driver_earning'] ?? json['net'] ?? json['earning']),
      );

  static String _str(dynamic v) => (v ?? '0').toString();

  static double _toDouble(dynamic v) =>
      v is num ? v.toDouble() : double.tryParse(v?.toString() ?? '') ?? 0;
}

class ActiveRideModel {
  final int orderId;
  final String orderNumber;
  final String status;
  final String type;
  final CustomerInfo customer;
  final PlaceInfo pickup;
  final PlaceInfo drop;
  final String otp; // driver verifies at pickup
  final FareInfo fare;
  final String paymentMethod;
  final bool otpRequired;

  ActiveRideModel({
    required this.orderId,
    required this.orderNumber,
    required this.status,
    required this.type,
    required this.customer,
    required this.pickup,
    required this.drop,
    required this.otp,
    required this.fare,
    required this.paymentMethod,
    this.otpRequired = true,
  });

  bool get isCash => paymentMethod.toLowerCase() == 'cash';

  factory ActiveRideModel.fromJson(Map<String, dynamic> json) {
    final m = (json['data'] is Map ? json['data'] : json) as Map;
    Map<String, dynamic> sub(dynamic v) =>
        v is Map ? v.cast<String, dynamic>() : <String, dynamic>{};

    return ActiveRideModel(
      orderId: _toInt(m['order_id'] ?? m['id']),
      orderNumber: (m['order_number'] ?? m['number'] ?? '').toString(),
      status: (m['status'] ?? 'accepted').toString(),
      type: (m['type'] ?? 'ride').toString(),
      customer: CustomerInfo.fromJson(sub(m['customer'] ?? m['user'])),
      pickup: PlaceInfo.fromJson(sub(m['pickup'])),
      drop: PlaceInfo.fromJson(
          sub(m['drop'] ?? m['dropoff'] ?? m['destination'])),
      otp: (m['otp'] ?? '').toString(),
      fare: FareInfo.fromJson(sub(m['fare'] ?? m)),
      paymentMethod: (m['payment_method'] ?? 'cash').toString(),
      otpRequired: m['otp_required'] == null
          ? true
          : (m['otp_required'] == true || m['otp_required'] == 1),
    );
  }

  ActiveRideModel copyWith({String? status, FareInfo? fare}) => ActiveRideModel(
        orderId: orderId,
        orderNumber: orderNumber,
        status: status ?? this.status,
        type: type,
        customer: customer,
        pickup: pickup,
        drop: drop,
        otp: otp,
        fare: fare ?? this.fare,
        paymentMethod: paymentMethod,
        otpRequired: otpRequired,
      );

  static int _toInt(dynamic v) =>
      v is int ? v : int.tryParse(v?.toString() ?? '') ?? 0;
}
