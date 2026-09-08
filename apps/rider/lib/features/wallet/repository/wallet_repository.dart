import '../../../core/network/api_response.dart';
import '../model/due_entry_model.dart';
import '../model/payment_method_model.dart';
import '../model/topup_initiate_model.dart';
import '../model/transaction_model.dart';
import '../model/wallet_model.dart';
import '../model/withdrawal_method_model.dart';
import '../model/withdrawal_model.dart';

abstract class WalletRepository {
  Future<WalletModel> getWallet();

  Future<({List<TransactionModel> items, PaginationMeta? meta})> getTransactions(
    int page,
  );

  /// Paginated ledger of sender delivery-charge dues (added/paid).
  Future<({List<DueEntryModel> items, PaginationMeta? meta})> getDues(
    int page,
  );

  /// Submit a withdrawal request (processed by an admin).
  Future<bool> requestWithdrawal({
    required double amount,
    required String method,
    required String account,
  });

  Future<List<WithdrawalModel>> getWithdrawalHistory();

  Future<List<WithdrawalMethodModel>> getWithdrawalMethods();

  /// Add-money (gateway checkout) flow.
  Future<List<PaymentMethodModel>> getPaymentMethods();

  Future<TopupInitiateModel> initiateAddMoney({
    required double amount,
    required String paymentMethod,
  });
}
