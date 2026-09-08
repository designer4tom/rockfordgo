import '../model/payment_method_model.dart';
import '../model/recharge_model.dart';

abstract class RechargeRepository {
  Future<List<PaymentMethodModel>> getPaymentMethods();

  /// Initiate a recharge; returns the payment_url to load in the webview.
  Future<RechargeInitiateModel> initiate({
    required double amount,
    required String method,
  });

  Future<bool> confirm(int rechargeId);

  Future<List<RechargeModel>> getHistory();
}
