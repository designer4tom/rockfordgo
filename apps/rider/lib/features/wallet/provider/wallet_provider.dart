import 'package:flutter/material.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/network/api_response.dart';
import '../model/due_entry_model.dart';
import '../model/payment_method_model.dart';
import '../model/transaction_model.dart';
import '../model/wallet_model.dart';
import '../model/withdrawal_method_model.dart';
import '../model/withdrawal_model.dart';
import '../repository/wallet_repository.dart';

class WalletProvider extends ChangeNotifier {
  final WalletRepository _repository;

  WalletProvider(this._repository);

  /// Called whenever a fresh balance is fetched, so the cached user balance
  /// (AuthProvider) can be kept in sync across the app.
  void Function(String balance)? onBalanceChanged;

  WalletModel? wallet;
  List<TransactionModel> transactions = [];
  List<DueEntryModel> dues = [];
  PaginationMeta? duesMeta;
  bool isLoadingDues = false;
  bool isLoadingMoreDues = false;
  List<WithdrawalModel> withdrawals = [];
  List<WithdrawalMethodModel> withdrawalMethods = [];
  List<PaymentMethodModel> paymentMethods = [];
  bool isLoadingPaymentMethods = false;
  String? paymentMethodsError;
  PaginationMeta? meta;
  bool isLoading = false;
  bool isLoadingMore = false;
  bool isSubmitting = false;
  bool isInitiating = false;
  bool isLoadingWithdrawals = false;
  String? error;

  /// Outstanding delivery-charge due the sender owes (from GET /user/wallet).
  double get dueAmount => wallet?.dueValue ?? 0;

  bool get hasDue => dueAmount > 0;

  /// False when the due has reached the limit — COD bookings are blocked.
  bool get canPlaceCod => wallet?.canPlaceCod ?? true;

  Future<void> loadBalance() async {
    try {
      wallet = await _repository.getWallet();
      onBalanceChanged?.call(wallet!.balance);
      notifyListeners();
    } on ApiException catch (e) {
      error = e.message;
      notifyListeners();
    }
  }

  /// Reload the balance with a few short retries after an add-money. Payment
  /// gateways credit the wallet via an async server callback that can land a
  /// moment *after* the success redirect, so a single fetch may still return
  /// the old balance. Stops as soon as the balance changes.
  Future<void> refreshBalanceAfterTopup() async {
    final beforeBalance = wallet?.balance;
    final beforeDue = wallet?.dueAmount;
    for (var attempt = 0; attempt < 5; attempt++) {
      await loadBalance();
      if (wallet?.balance != beforeBalance || wallet?.dueAmount != beforeDue) {
        return;
      }
      if (attempt < 4) {
        await Future.delayed(const Duration(milliseconds: 1500));
      }
    }
  }

  Future<void> loadTransactions({bool refresh = false}) async {
    if (refresh) {
      transactions = [];
      meta = null;
    }
    final nextPage = (meta?.currentPage ?? 0) + 1;
    if (meta != null && !meta!.hasMore && !refresh) return;

    if (nextPage == 1) {
      isLoading = true;
    } else {
      isLoadingMore = true;
    }
    notifyListeners();

    try {
      final result = await _repository.getTransactions(nextPage);
      transactions.addAll(result.items);
      meta = result.meta;
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      isLoading = false;
      isLoadingMore = false;
      notifyListeners();
    }
  }

  Future<void> loadDues({bool refresh = false}) async {
    if (refresh) {
      dues = [];
      duesMeta = null;
    }
    final nextPage = (duesMeta?.currentPage ?? 0) + 1;
    if (duesMeta != null && !duesMeta!.hasMore && !refresh) return;

    if (nextPage == 1) {
      isLoadingDues = true;
    } else {
      isLoadingMoreDues = true;
    }
    notifyListeners();

    try {
      final result = await _repository.getDues(nextPage);
      dues.addAll(result.items);
      duesMeta = result.meta;
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      isLoadingDues = false;
      isLoadingMoreDues = false;
      notifyListeners();
    }
  }

  Future<bool> requestWithdrawal({
    required double amount,
    required String method,
    required String account,
  }) async {
    isSubmitting = true;
    error = null;
    notifyListeners();
    try {
      final ok = await _repository.requestWithdrawal(
        amount: amount,
        method: method,
        account: account,
      );
      if (ok) await loadBalance();
      return ok;
    } on ApiException catch (e) {
      error = e.message;
      return false;
    } finally {
      isSubmitting = false;
      notifyListeners();
    }
  }

  Future<void> loadWithdrawalHistory() async {
    isLoadingWithdrawals = true;
    notifyListeners();
    try {
      withdrawals = await _repository.getWithdrawalHistory();
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      isLoadingWithdrawals = false;
      notifyListeners();
    }
  }

  // ---- Add money (gateway checkout) ----
  Future<void> loadPaymentMethods() async {
    isLoadingPaymentMethods = true;
    paymentMethodsError = null;
    notifyListeners();
    try {
      paymentMethods = await _repository.getPaymentMethods();
    } on ApiException catch (e) {
      paymentMethodsError = e.message;
      error = e.message;
    } catch (e) {
      // A malformed gateway entry used to throw here and leave the list
      // empty forever, which the top-up screen rendered as an endless
      // spinner. Fail loudly enough for the UI to offer a retry.
      paymentMethodsError = 'Could not load payment methods.';
    } finally {
      isLoadingPaymentMethods = false;
      notifyListeners();
    }
  }

  /// Returns the gateway payment URL to open in a webview, or null on error.
  Future<String?> initiateAddMoney(double amount, String method) async {
    isInitiating = true;
    error = null;
    notifyListeners();
    try {
      final result = await _repository.initiateAddMoney(
        amount: amount,
        paymentMethod: method,
      );
      return result.paymentUrl.isNotEmpty ? result.paymentUrl : null;
    } on ApiException catch (e) {
      error = e.message;
      return null;
    } finally {
      isInitiating = false;
      notifyListeners();
    }
  }

  Future<void> loadWithdrawalMethods() async {
    try {
      withdrawalMethods = await _repository.getWithdrawalMethods();
      notifyListeners();
    } on ApiException catch (e) {
      error = e.message;
      notifyListeners();
    }
  }
}
