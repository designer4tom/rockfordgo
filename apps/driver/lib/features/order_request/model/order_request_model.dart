import '../../../core/models/place_info.dart';

/// Incoming ride/parcel request shown in the 30s popup.
class OrderRequestModel {
  final int orderId;
  final String orderNumber;
  final String type; // ride | parcel
  final PlaceInfo pickup;
  final PlaceInfo drop;
  final double distanceKm; // trip distance
  final double? distanceToPickupKm;
  final String estimatedEarning;
  final String paymentMethod;
  final String? codAmount; // parcel COD
  final int timeoutSeconds;

  OrderRequestModel({
    required this.orderId,
    required this.orderNumber,
    required this.type,
    required this.pickup,
    required this.drop,
    required this.distanceKm,
    this.distanceToPickupKm,
    required this.estimatedEarning,
    required this.paymentMethod,
    this.codAmount,
    this.timeoutSeconds = 30,
  });

  bool get isParcel => type.toLowerCase() == 'parcel';
  bool get isCod => (codAmount != null && codAmount != '0' && codAmount!.isNotEmpty);

  factory OrderRequestModel.fromJson(Map<String, dynamic> json) {
    // Accept either a flat payload or { data: {...} }.
    final m = (json['data'] is Map ? json['data'] : json) as Map;
    PlaceInfo place(dynamic v) => v is Map
        ? PlaceInfo.fromJson(v.cast<String, dynamic>())
        : PlaceInfo(address: '', lat: 0, lng: 0);

    return OrderRequestModel(
      orderId: _toInt(m['order_id'] ?? m['id']),
      orderNumber: (m['order_number'] ?? m['number'] ?? '').toString(),
      type: (m['type'] ?? 'ride').toString(),
      pickup: place(m['pickup']),
      drop: place(m['drop'] ?? m['dropoff'] ?? m['destination']),
      distanceKm: toDouble(m['distance_km'] ?? m['trip_distance'] ?? 0),
      distanceToPickupKm: m['distance_to_pickup_km'] == null
          ? null
          : toDouble(m['distance_to_pickup_km']),
      estimatedEarning:
          (m['estimated_earning'] ?? m['fare'] ?? '0').toString(),
      paymentMethod: (m['payment_method'] ?? 'cash').toString(),
      codAmount: m['cod_amount']?.toString(),
      timeoutSeconds: _toInt(m['timeout_seconds'] ?? 30, fallback: 30),
    );
  }

  /// Build straight from an FCM `data` payload (flat string keys sent by the
  /// backend's sendOrderRequest). Used for the background/terminated tap path —
  /// the order is only *offered*, not yet assigned, so the order-detail endpoint
  /// would 404. Everything the popup needs is already in the notification.
  static OrderRequestModel? fromFcmData(Map<String, dynamic> d) {
    final id = _toInt(d['order_id'] ?? d['id']);
    if (id == 0) return null;
    final isCod = d['is_cod'] == '1' ||
        d['is_cod'] == 1 ||
        d['is_cod'] == true ||
        d['is_cod']?.toString() == 'true';
    return OrderRequestModel(
      orderId: id,
      orderNumber: (d['order_number'] ?? d['number'] ?? '').toString(),
      type: (d['order_type'] ?? d['type'] ?? 'ride').toString(),
      pickup: PlaceInfo(
        address: (d['pickup'] ?? d['pickup_address'] ?? '').toString(),
        lat: toDouble(d['pickup_lat']),
        lng: toDouble(d['pickup_lng']),
      ),
      drop: PlaceInfo(
        address: (d['drop'] ?? d['drop_address'] ?? '').toString(),
        lat: toDouble(d['drop_lat']),
        lng: toDouble(d['drop_lng']),
      ),
      distanceKm: toDouble(d['distance_km'] ?? 0),
      estimatedEarning:
          (d['estimated_earning'] ?? d['driver_earning'] ?? d['total_amount'] ?? '0')
              .toString(),
      paymentMethod: (d['payment_method'] ?? 'cash').toString(),
      codAmount: isCod ? d['cod_amount']?.toString() : null,
      timeoutSeconds: _toInt(d['timeout_seconds'] ?? 30, fallback: 30),
    );
  }

  static int _toInt(dynamic v, {int fallback = 0}) =>
      v is int ? v : int.tryParse(v?.toString() ?? '') ?? fallback;
}
