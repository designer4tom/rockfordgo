import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/dio_client.dart';
import '../model/transaction_model.dart';
import '../model/wallet_model.dart';
import '../model/withdrawal_method_model.dart';
import '../model/withdrawal_model.dart';

class WalletRepository {
  final DioClient _client;
  WalletRepository({DioClient? client})
      : _client = client ?? DioClient.instance;

  Future<WalletModel> getWallet() async {
    try {
      final res = await _client.get(ApiEndpoints.wallet);
      final data = (res.data['data'] ?? res.data) as Map;
      return WalletModel.fromJson(data.cast<String, dynamic>());
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<List<TransactionModel>> getTransactions({int page = 1}) async {
    try {
      final res = await _client
          .get(ApiEndpoints.walletTransactions, query: {'page': page});
      final list = _listFrom(res.data);
      return list
          .map((e) =>
              TransactionModel.fromJson((e as Map).cast<String, dynamic>()))
          .toList();
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<List<WithdrawalMethodModel>> getWithdrawalMethods() async {
    try {
      final res = await _client.get(ApiEndpoints.withdrawalMethods);
      final list = _listFrom(res.data);
      return list
          .map((e) =>
              WithdrawalMethodModel.fromJson((e as Map).cast<String, dynamic>()))
          .toList();
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<bool> requestWithdrawal(
      double amount, String method, String account) async {
    try {
      final res = await _client.post(
        ApiEndpoints.withdrawalRequest,
        data: {'amount': amount, 'method': method, 'account': account},
      );
      final body = res.data as Map;
      return body['success'] == true || body['status'] == true;
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<List<WithdrawalModel>> getWithdrawalHistory() async {
    try {
      final res = await _client.get(ApiEndpoints.withdrawalHistory);
      final list = _listFrom(res.data);
      return list
          .map((e) =>
              WithdrawalModel.fromJson((e as Map).cast<String, dynamic>()))
          .toList();
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  /// Handles both `{data: [...]}` and `{data: {data: [...]}}` (paginated).
  List _listFrom(dynamic body) {
    final data = body['data'];
    if (data is List) return data;
    if (data is Map && data['data'] is List) return data['data'] as List;
    return const [];
  }
}
