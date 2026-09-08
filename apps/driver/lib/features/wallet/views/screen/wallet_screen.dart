import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/routing/route_names.dart';
import '../../../../core/widgets/empty_state.dart';
import '../../../../core/widgets/loading_indicator.dart';
import '../../provider/wallet_provider.dart';
import '../widgets/balance_due_card.dart';
import '../widgets/transaction_tile.dart';

class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  final _scroll = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<WalletProvider>().loadWallet();
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
    final p = context.watch<WalletProvider>();
    return Scaffold(
      appBar: AppBar(
        title: Text('wallet.title'.tr()),
        actions: [
          IconButton(
            icon: const Icon(Icons.history),
            onPressed: () => context.push('/withdrawal-history'),
          ),
        ],
      ),
      body: p.loading && p.wallet == null
          ? const LoadingIndicator()
          : RefreshIndicator(
              onRefresh: () => p.loadWallet(),
              child: ListView(
                controller: _scroll,
                padding: const EdgeInsets.all(16),
                children: [
                  if (p.wallet != null)
                    BalanceDueCard(
                      wallet: p.wallet!,
                      // Re-fetch on return: the screen stays mounted while
                      // recharge/withdrawal is pushed, so its initState won't
                      // run again — refresh explicitly so the balance is current
                      // even if the payment webview couldn't report 'success'.
                      onRecharge: () async {
                        await context.push(RouteNames.recharge);
                        if (context.mounted) {
                          context.read<WalletProvider>().loadWallet();
                        }
                      },
                      onWithdraw: () async {
                        await context.push(RouteNames.withdrawal);
                        if (context.mounted) {
                          context.read<WalletProvider>().loadWallet();
                        }
                      },
                    ),
                  const SizedBox(height: 20),
                  Text('wallet.transactions'.tr(),
                      style: const TextStyle(
                          fontSize: 16, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  if (p.transactions.isEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 40),
                      child: EmptyState(
                        icon: Icons.receipt_long_outlined,
                        title: 'wallet.no_transactions'.tr(),
                      ),
                    )
                  else
                    ...p.transactions.map((t) => Column(
                          children: [
                            TransactionTile(transaction: t),
                            const Divider(height: 1),
                          ],
                        )),
                ],
              ),
            ),
    );
  }
}
