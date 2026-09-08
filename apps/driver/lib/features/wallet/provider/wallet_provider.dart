import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../model/transaction_model.dart';
import '../model/wallet_model.dart';
import '../model/withdrawal_method_model.dart';
import '../model/withdrawal_model.dart';
import '../repository/wallet_repository.dart';

class WalletProvider extends ChangeNotifier {
  final WalletRepository _repository;
  WalletProvider(this._repository);

  WalletModel? wallet;
  List<TransactionModel> transactions = [];
  List<WithdrawalModel> withdrawals = [];
  List<WithdrawalMethodModel> withdrawalMethods = [];

  bool loading = false;
  bool loadingMethods = false;
  bool submitting = false;
  bool _loadingMore = false;
  bool _hasMore = true;
  int _page = 1;
  String? error;

  Future<void> loadWallet() async {
    loading = true;
    notifyListeners();
    try {
      wallet = await _repository.getWallet();
      error = null;
    } on ApiException catch (e) {
      error = e.message;
    }
    loading = false;
    notifyListeners();
    loadTransactions(refresh: true);
  }

  /// Refresh after a recharge. The payment gateway credits the wallet via an
  /// async server callback, so the new balance is often not live the instant
  /// the app returns from the payment webview — a single fetch can still read
  /// the old value. Poll a few times until the balance changes (or attempts
  /// run out), updating the UI as soon as it does.
  Future<void> refreshAfterRecharge({String? previousBalance}) async {
    final before = previousBalance ?? wallet?.balance;
    // Check immediately, then re-check fast (most credits land within ~1s) and
    // back off — so the balance shows the instant the server has it, instead
    // of waiting a fixed interval.
    const retryDelaysMs = [300, 500, 800, 1200, 1800, 2500];
    for (var i = 0; i <= retryDelaysMs.length; i++) {
      try {
        final fresh = await _repository.getWallet();
        wallet = fresh;
        error = null;
        notifyListeners();
        if (fresh.balance != before) break; // credited — done
      } on ApiException catch (e) {
        error = e.message;
        notifyListeners();
      }
      if (i < retryDelaysMs.length) {
        await Future.delayed(Duration(milliseconds: retryDelaysMs[i]));
      }
    }
    loadTransactions(refresh: true);
  }

  Future<void> loadTransactions({bool refresh = false}) async {
    if (refresh) {
      _page = 1;
      _hasMore = true;
      transactions = [];
    }
    if (_loadingMore || !_hasMore) return;
    _loadingMore = true;
    try {
      final list = await _repository.getTransactions(page: _page);
      if (list.isEmpty) {
        _hasMore = false;
      } else {
        transactions.addAll(list);
        _page++;
      }
      notifyListeners();
    } catch (_) {
    } finally {
      _loadingMore = false;
    }
  }

  /// Loads the active withdrawal methods from the backend. The chosen method's
  /// `code` is what the request API validates — hardcoded codes get rejected.
  Future<void> loadWithdrawalMethods() async {
    loadingMethods = true;
    notifyListeners();
    try {
      withdrawalMethods = await _repository.getWithdrawalMethods();
      error = null;
    } on ApiException catch (e) {
      error = e.message;
    }
    loadingMethods = false;
    notifyListeners();
  }

  Future<bool> requestWithdrawal(
      double amount, String method, String account) async {
    submitting = true;
    error = null;
    notifyListeners();
    try {
      final ok = await _repository.requestWithdrawal(amount, method, account);
      submitting = false;
      if (ok) await loadWallet();
      notifyListeners();
      return ok;
    } on ApiException catch (e) {
      error = e.message;
      submitting = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> loadWithdrawalHistory() async {
    try {
      withdrawals = await _repository.getWithdrawalHistory();
      notifyListeners();
    } on ApiException catch (e) {
      error = e.message;
      notifyListeners();
    }
  }
}
