import '../model/coupon_offer_model.dart';

abstract class CouponRepository {
  Future<List<CouponOfferModel>> getCoupons({String? serviceType});
}
