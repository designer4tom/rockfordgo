import '../../../core/utils/helpers.dart';

class CouponOfferModel {
  final String code;
  final String? description;
  final String discountType; // percentage / fixed
  final String discountValue;
  final String? maxDiscount;
  final String minOrderAmount;
  final String? validUntil;

  CouponOfferModel({
    required this.code,
    this.description,
    this.discountType = 'fixed',
    this.discountValue = '0',
    this.maxDiscount,
    this.minOrderAmount = '0',
    this.validUntil,
  });

  factory CouponOfferModel.fromJson(Map<String, dynamic> json) {
    return CouponOfferModel(
      code: json['code'] ?? '',
      description: json['description'],
      discountType: json['discount_type'] ?? 'fixed',
      discountValue: (json['discount_value'] ?? '0').toString(),
      maxDiscount: json['max_discount']?.toString(),
      minOrderAmount: (json['min_order_amount'] ?? '0').toString(),
      validUntil: json['valid_until']?.toString(),
    );
  }

  String _trim(String v) =>
      v.replaceAll('.00', '').replaceAll(RegExp(r'\.0$'), '');

  bool get isPercentage => discountType == 'percentage';

  /// "20% OFF" or "৳50 OFF" (currency symbol from /config).
  String get discountLabel => isPercentage
      ? '${_trim(discountValue)}% OFF'
      : '${Helpers.currencySymbol}${_trim(discountValue)} OFF';

  String get minOrderDisplay => _trim(minOrderAmount);
}
