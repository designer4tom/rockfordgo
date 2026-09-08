/// Minimal shape from GET /user/active-order — just enough to decide where to
/// resume the user. The tracking screen fetches the full status itself.
class ActiveOrderModel {
  final bool hasActive;
  final int orderId;
  final String type; // ride / parcel
  final String status;

  ActiveOrderModel({
    this.hasActive = false,
    this.orderId = 0,
    this.type = 'ride',
    this.status = '',
  });

  factory ActiveOrderModel.fromJson(Map<String, dynamic> json) {
    return ActiveOrderModel(
      hasActive: json['has_active'] == true,
      orderId: json['order_id'] ?? 0,
      type: json['type'] ?? 'ride',
      status: json['status'] ?? '',
    );
  }

  bool get isParcel => type == 'parcel';

  /// Still searching for a driver (no driver assigned yet).
  bool get isSearching => status == 'pending';
}
