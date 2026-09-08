import 'package:flutter/material.dart';

import '../../../core/network/api_exception.dart';
import '../model/coupon_offer_model.dart';
import '../repository/coupon_repository.dart';

class CouponProvider extends ChangeNotifier {
  final CouponRepository _repository;

  CouponProvider(this._repository);

  List<CouponOfferModel> coupons = [];
  bool isLoading = false;
  String? error;

  Future<void> loadCoupons({String? serviceType}) async {
    isLoading = true;
    error = null;
    notifyListeners();
    try {
      coupons = await _repository.getCoupons(serviceType: serviceType);
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }
}
