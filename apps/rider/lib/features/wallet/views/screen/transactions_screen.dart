import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../../core/widgets/empty_state.dart';
import '../../../../core/widgets/loading_widget.dart';
import '../../provider/wallet_provider.dart';
import '../widgets/transaction_tile.dart';

/// Full wallet transaction history (paginated). Opened from the Wallet
/// "View all" link and the "Transactions" quick action.
class TransactionsScreen extends StatefulWidget {
  const TransactionsScreen({super.key});

  @override
  State<TransactionsScreen> createState() => _TransactionsScreenState();
}

class _TransactionsScreenState extends State<TransactionsScreen> {
  final _scroll = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final p = context.read<WalletProvider>();
      if (p.transactions.isEmpty) p.loadTransactions(refresh: true);
    });
    _scroll.addListener(() {
      if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 200) {
        context.read<WalletProvider>().loadTransactions();
      }
    });
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final wallet = context.watch<WalletProvider>();
    final theme = Theme.of(context);
    final txns = wallet.transactions;

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      appBar: AppBar(title: Text('wallet.transactions'.tr())),
      body: wallet.isLoading
          ? const LoadingWidget()
          : txns.isEmpty
              ? EmptyState(
                  icon: Icons.receipt_long_outlined,
                  title: 'wallet.no_transactions'.tr(),
                  message: 'wallet.no_transactions_msg'.tr(),
                )
              : RefreshIndicator(
                  onRefresh: () => wallet.loadTransactions(refresh: true),
                  child: ListView(
                    controller: _scroll,
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        decoration: BoxDecoration(
                          color: theme.cardColor,
                          borderRadius: BorderRadius.circular(16),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.04),
                              blurRadius: 10,
                              offset: const Offset(0, 3),
                            ),
                          ],
                        ),
                        child: Column(
                          children: [
                            for (var i = 0; i < txns.length; i++)
                              TransactionTile(
                                transaction: txns[i],
                                showDivider: i != txns.length - 1,
                              ),
                          ],
                        ),
                      ),
                      if (wallet.isLoadingMore)
                        const Padding(
                          padding: EdgeInsets.all(16),
                          child: Center(child: CircularProgressIndicator()),
                        ),
                    ],
                  ),
                ),
    );
  }
}
