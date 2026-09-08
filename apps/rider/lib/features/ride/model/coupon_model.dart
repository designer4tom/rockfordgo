class CouponModel {
  final String code;
  final String discountType; // 'fixed' | 'percentage'
  final double discountValue; // percentage rate, or fixed amount
  final double? maxDiscount; // cap for percentage coupons (if any)
  final String message;

  CouponModel({
    required this.code,
    required this.discountType,
    required this.discountValue,
    this.maxDiscount,
    this.message = '',
  });

  factory CouponModel.fromJson(Map<String, dynamic> json) {
    final max = json['max_discount'];
    return CouponModel(
      code: json['code'] ?? '',
      discountType: json['discount_type'] ?? 'fixed',
      // `discount_value` is the percentage rate (e.g. 20) or the fixed amount.
      // Older backends may only send `discount_amount`/`discount`.
      discountValue: _toDouble(
        json['discount_value'] ?? json['discount_amount'] ?? json['discount'],
      ),
      maxDiscount: max == null ? null : _toDouble(max),
      message: json['message'] ?? '',
    );
  }

  /// The actual discount for [amount], honouring the percentage rate and the
  /// max-discount cap. Never negative and never larger than [amount].
  double discountOn(double amount) {
    var discount = discountType == 'percentage'
        ? amount * discountValue / 100
        : discountValue;
    final cap = maxDiscount;
    if (cap != null && cap > 0 && discount > cap) discount = cap;
    if (discount > amount) discount = amount;
    return discount < 0 ? 0 : discount;
  }

  static double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }
}
