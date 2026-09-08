import 'package:flutter/foundation.dart';

import '../model/payment_method_model.dart';
import '../model/recharge_model.dart';
import '../repository/recharge_repository.dart';

class RechargeProvider extends ChangeNotifier {
  final RechargeRepository _repository;
  RechargeProvider(this._repository);

  List<PaymentMethodModel> paymentMethods = [];
  List<RechargeModel> history = [];
  double amount = 0;
  String? selectedMethod;

  bool loading = false;
  bool submitting = false;
  String? error;

  void setAmount(double value) {
    amount = value;
    notifyListeners();
  }

  void selectMethod(String code) {
    selectedMethod = code;
    notifyListeners();
  }

  bool get canProceed => amount > 0 && (selectedMethod?.isNotEmpty ?? false);

  Future<void> loadPaymentMethods() async {
    loading = true;
    notifyListeners();
    paymentMethods = await _repository.getPaymentMethods();
    selectedMethod ??=
        paymentMethods.isNotEmpty ? paymentMethods.first.code : null;
    loading = false;
    notifyListeners();
  }

  /// Returns the payment_url to load in the webview, or null on failure.
  Future<String?> initiateRecharge() async {
    if (!canProceed) return null;
    submitting = true;
    error = null;
    notifyListeners();
    final result = await _repository.initiate(
      amount: amount,
      method: selectedMethod!,
    );
    submitting = false;
    notifyListeners();
    return result.paymentUrl;
  }

  Future<bool> confirmRecharge(int rechargeId) =>
      _repository.confirm(rechargeId);

  Future<void> loadHistory() async {
    history = await _repository.getHistory();
    notifyListeners();
  }
}
