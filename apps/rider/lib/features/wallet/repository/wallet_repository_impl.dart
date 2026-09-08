import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/api_response.dart';
import '../../../core/network/dio_client.dart';
import '../model/due_entry_model.dart';
import '../model/payment_method_model.dart';
import '../model/topup_initiate_model.dart';
import '../model/transaction_model.dart';
import '../model/wallet_model.dart';
import '../model/withdrawal_method_model.dart';
import '../model/withdrawal_model.dart';
import 'wallet_repository.dart';

class WalletRepositoryImpl implements WalletRepository {
  final DioClient _client;

  WalletRepositoryImpl(this._client);

  @override
  Future<WalletModel> getWallet() async {
    try {
      final res = await _client.dio.get(ApiEndpoints.wallet);
      return WalletModel.fromJson(Map<String, dynamic>.from(res.data['data']));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<({List<TransactionModel> items, PaginationMeta? meta})>
      getTransactions(int page) async {
    try {
      final res = await _client.dio.get(
        ApiEndpoints.walletTransactions,
        queryParameters: {'page': page},
      );
      final list = (res.data['data'] as List? ?? [])
          .map((e) => TransactionModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
      final meta = res.data['meta'] != null
          ? PaginationMeta.fromJson(Map<String, dynamic>.from(res.data['meta']))
          : null;
      return (items: list, meta: meta);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<({List<DueEntryModel> items, PaginationMeta? meta})> getDues(
      int page) async {
    try {
      final res = await _client.dio.get(
        ApiEndpoints.walletDues,
        queryParameters: {'page': page, 'per_page': 20},
      );
      final list = (res.data['data'] as List? ?? [])
          .map((e) => DueEntryModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
      final meta = res.data['meta'] != null
          ? PaginationMeta.fromJson(Map<String, dynamic>.from(res.data['meta']))
          : null;
      return (items: list, meta: meta);
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<bool> requestWithdrawal({
    required double amount,
    required String method,
    required String account,
  }) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.userWithdrawalRequest,
        data: {
          'amount': amount,
          'method': method,
          'account': account,
        },
      );
      return res.data['success'] ?? true;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<List<WithdrawalModel>> getWithdrawalHistory() async {
    try {
      final res = await _client.dio.get(ApiEndpoints.userWithdrawalHistory);
      final list = (res.data['data'] as List? ?? [])
          .map((e) => WithdrawalModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
      return list;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<List<PaymentMethodModel>> getPaymentMethods() async {
    try {
      final res = await _client.dio.get(ApiEndpoints.walletPaymentMethods);
      return (res.data['data'] as List? ?? [])
          .map((e) => PaymentMethodModel.fromJson(Map<String, dynamic>.from(e)))
          .where((m) => m.isActive)
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<TopupInitiateModel> initiateAddMoney({
    required double amount,
    required String paymentMethod,
  }) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.walletAddMoneyInitiate,
        data: {'amount': amount, 'payment_method': paymentMethod},
      );
      return TopupInitiateModel.fromJson(
        Map<String, dynamic>.from(res.data['data']),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<List<WithdrawalMethodModel>> getWithdrawalMethods() async {
    try {
      final res = await _client.dio.get(ApiEndpoints.withdrawalMethods);
      final list = (res.data['data'] as List? ?? [])
          .map((e) =>
              WithdrawalMethodModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
      return list;
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
