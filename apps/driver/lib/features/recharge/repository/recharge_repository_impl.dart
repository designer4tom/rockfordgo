import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/dio_client.dart';
import '../model/payment_method_model.dart';
import '../model/recharge_model.dart';
import 'recharge_repository.dart';

class RechargeRepositoryImpl implements RechargeRepository {
  final DioClient _client;
  RechargeRepositoryImpl({DioClient? client})
      : _client = client ?? DioClient.instance;

  // Static demo URL used until the real gateway link is returned by the API.
  static const String _demoPaymentUrl =
      'https://uber.razinsoft.com/payment/demo';

  @override
  Future<List<PaymentMethodModel>> getPaymentMethods() async {
    try {
      final res = await _client.get(ApiEndpoints.rechargeMethods);
      final list = (res.data['data'] as List?) ?? const [];
      final methods = list
          .map((e) =>
              PaymentMethodModel.fromJson((e as Map).cast<String, dynamic>()))
          .toList();
      // Guard against the backend sending items with a blank/duplicate
      // code (e.g. an unrecognised JSON key): a shared code makes every
      // RadioListTile using it match the groupValue at once, so they all
      // render as selected. Fall back to a unique per-index code.
      final seen = <String>{};
      for (var i = 0; i < methods.length; i++) {
        final m = methods[i];
        if (m.code.isEmpty || !seen.add(m.code)) {
          methods[i] = PaymentMethodModel(
            code: '${m.code.isEmpty ? 'method' : m.code}_$i',
            name: m.name,
            icon: m.icon,
          );
          seen.add(methods[i].code);
        }
      }
      return methods.isEmpty ? PaymentMethodModel.defaults() : methods;
    } catch (_) {
      // Static fallback so the screen works before the backend is wired.
      return PaymentMethodModel.defaults();
    }
  }

  @override
  Future<RechargeInitiateModel> initiate({
    required double amount,
    required String method,
  }) async {
    try {
      final res = await _client.post(
        ApiEndpoints.rechargeInitiate,
        data: {'amount': amount, 'payment_method': method},
      );
      final result = RechargeInitiateModel.fromJson(
          (res.data as Map).cast<String, dynamic>());
      // Fall back to the demo URL if the backend didn't return one yet.
      return result.paymentUrl == null || result.paymentUrl!.isEmpty
          ? RechargeInitiateModel(
              rechargeId: result.rechargeId, paymentUrl: _demoPaymentUrl)
          : result;
    } catch (_) {
      return RechargeInitiateModel(paymentUrl: _demoPaymentUrl);
    }
  }

  @override
  Future<bool> confirm(int rechargeId) async {
    try {
      final res = await _client.post(
        ApiEndpoints.rechargeConfirm,
        data: {'recharge_id': rechargeId},
      );
      final body = res.data as Map;
      return body['success'] == true || body['status'] == true;
    } catch (_) {
      return false;
    }
  }

  @override
  Future<List<RechargeModel>> getHistory() async {
    try {
      final res = await _client.get(ApiEndpoints.rechargeHistory);
      final data = res.data['data'];
      final list = data is List
          ? data
          : (data is Map && data['data'] is List ? data['data'] as List : const []);
      return list
          .map((e) => RechargeModel.fromJson((e as Map).cast<String, dynamic>()))
          .toList();
    } catch (_) {
      return const [];
    }
  }
}
