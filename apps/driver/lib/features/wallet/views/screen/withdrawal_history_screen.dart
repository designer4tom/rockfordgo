import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../../core/widgets/empty_state.dart';
import '../../provider/wallet_provider.dart';
import '../widgets/withdrawal_tile.dart';

class WithdrawalHistoryScreen extends StatefulWidget {
  const WithdrawalHistoryScreen({super.key});

  @override
  State<WithdrawalHistoryScreen> createState() =>
      _WithdrawalHistoryScreenState();
}

class _WithdrawalHistoryScreenState extends State<WithdrawalHistoryScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<WalletProvider>().loadWithdrawalHistory();
    });
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<WalletProvider>();
    return Scaffold(
      appBar: AppBar(title: Text('wallet.withdrawal_history'.tr())),
      body: RefreshIndicator(
        onRefresh: () => p.loadWithdrawalHistory(),
        child: p.withdrawals.isEmpty
            ? ListView(
                children: [
                  const SizedBox(height: 120),
                  EmptyState(
                    icon: Icons.account_balance_wallet_outlined,
                    title: 'wallet.no_withdrawals_yet'.tr(),
                  ),
                ],
              )
            : ListView(
                padding: const EdgeInsets.all(16),
                children:
                    p.withdrawals.map((w) => WithdrawalTile(withdrawal: w)).toList(),
              ),
      ),
    );
  }
}
