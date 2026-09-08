import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../../core/widgets/empty_state.dart';
import '../../../../core/widgets/loading_widget.dart';
import '../../provider/wallet_provider.dart';
import '../widgets/due_tile.dart';

/// Paginated ledger of the sender's delivery-charge dues (added/paid).
/// Opened from the outstanding-due banner on the Wallet screen.
class DueHistoryScreen extends StatefulWidget {
  const DueHistoryScreen({super.key});

  @override
  State<DueHistoryScreen> createState() => _DueHistoryScreenState();
}

class _DueHistoryScreenState extends State<DueHistoryScreen> {
  final _scroll = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<WalletProvider>().loadDues(refresh: true);
    });
    _scroll.addListener(() {
      if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 200) {
        context.read<WalletProvider>().loadDues();
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
    final dues = wallet.dues;

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      appBar: AppBar(title: Text('wallet.due_history'.tr())),
      body: wallet.isLoadingDues
          ? const LoadingWidget()
          : dues.isEmpty
              ? EmptyState(
                  icon: Icons.receipt_long_outlined,
                  title: 'wallet.no_dues'.tr(),
                  message: 'wallet.no_dues_msg'.tr(),
                )
              : RefreshIndicator(
                  onRefresh: () => wallet.loadDues(refresh: true),
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
                            for (var i = 0; i < dues.length; i++)
                              DueTile(
                                entry: dues[i],
                                showDivider: i != dues.length - 1,
                              ),
                          ],
                        ),
                      ),
                      if (wallet.isLoadingMoreDues)
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
