import 'package:flutter/material.dart';

import '../../../core/network/api_exception.dart';
import '../model/referral_model.dart';
import '../repository/referral_repository.dart';

class ReferralProvider extends ChangeNotifier {
  final ReferralRepository _repository;

  ReferralProvider(this._repository);

  ReferralModel? referral;
  bool isLoading = false;
  String? error;

  Future<void> loadReferral() async {
    isLoading = true;
    notifyListeners();
    try {
      referral = await _repository.getReferral();
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }
}
