import '../../../core/utils/helpers.dart';

class OrderModel {
  final int id;
  final String orderNumber;
  final String type; // ride | parcel
  final String status;
  final String earning;
  final String? pickupAddress;
  final String? dropAddress;
  final DateTime? createdAt;
  final String? customerName;
  final String? customerImage;

  OrderModel({
    required this.id,
    required this.orderNumber,
    required this.type,
    required this.status,
    required this.earning,
    this.pickupAddress,
    this.dropAddress,
    this.createdAt,
    this.customerName,
    this.customerImage,
  });

  bool get isParcel => type.toLowerCase() == 'parcel';

  factory OrderModel.fromJson(Map<String, dynamic> json) => OrderModel(
        id: json['id'] is int
            ? json['id']
            : int.tryParse(json['id']?.toString() ?? '') ?? 0,
        orderNumber: (json['order_number'] ?? json['number'] ?? '').toString(),
        type: (json['type'] ?? 'ride').toString(),
        status: (json['status'] ?? '').toString(),
        earning: (json['earning'] ?? json['driver_earning'] ?? '0').toString(),
        pickupAddress: json['pickup_address']?.toString() ??
            (json['pickup'] is Map ? json['pickup']['address']?.toString() : null),
        dropAddress: json['drop_address']?.toString() ??
            (json['drop'] is Map ? json['drop']['address']?.toString() : null),
        createdAt: Helpers.tryParse(json['created_at'] ?? json['date']),
        customerName: json['customer'] is Map
            ? json['customer']['name']?.toString()
            : json['customer_name']?.toString(),
        customerImage: json['customer'] is Map
            ? json['customer']['image']?.toString()
            : json['customer_image']?.toString(),
      );
}

/// Detailed view of a single order (reuses summary + breakdown).
class OrderDetailModel {
  final OrderModel order;
  final Map<String, dynamic> raw;
  OrderDetailModel({required this.order, required this.raw});

  factory OrderDetailModel.fromJson(Map<String, dynamic> json) =>
      OrderDetailModel(order: OrderModel.fromJson(json), raw: json);
}
