import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/empty_state.dart';
import '../../../../core/widgets/loading_widget.dart';
import '../../model/wallet_model.dart';
import '../../provider/wallet_provider.dart';
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
      final p = context.read<WalletProvider>();
      p.loadBalance();
      p.loadTransactions(refresh: true);
    });
    _scroll.addListener(_onScroll);
  }

  void _onScroll() {
    if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 200) {
      context.read<WalletProvider>().loadTransactions();
    }
  }

  /// Open the top-up flow and refresh the balance/transactions on return, so a
  /// successful add-money is always reflected even if the gateway's success
  /// redirect wasn't detected inside the payment webview.
  Future<void> _openTopup() async {
    await context.push('/topup');
    if (!mounted) return;
    final p = context.read<WalletProvider>();
    p.loadBalance();
    p.loadTransactions(refresh: true);
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

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      body: SafeArea(
        child: RefreshIndicator(
        onRefresh: () async {
          await wallet.loadBalance();
          await wallet.loadTransactions(refresh: true);
        },
        child: ListView(
          controller: _scroll,
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
          children: [
            Text('wallet.title'.tr(),
                style: TextStyle(
                    fontSize: 26,
                    fontWeight: FontWeight.bold,
                    color: theme.colorScheme.onSurface)),
            const SizedBox(height: 16),
            _BalanceCard(
              balance: wallet.wallet?.balanceValue ?? 0,
              onAddMoney: _openTopup,
            ),
            if (wallet.wallet?.hasDue ?? false) ...[
              const SizedBox(height: 12),
              _DueBanner(wallet: wallet.wallet!),
            ],
            const SizedBox(height: 16),
            _quickActions(theme),
            const SizedBox(height: 16),
            _offersBanner(theme),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: Text('wallet.recent_transactions'.tr(),
                      style: TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.bold,
                          color: theme.colorScheme.onSurface)),
                ),
                GestureDetector(
                  onTap: () => context.push(RouteNames.transactions),
                  child: Text('home.view_all'.tr(),
                      style: const TextStyle(
                          fontWeight: FontWeight.w600,
                          color: AppColors.primary)),
                ),
              ],
            ),
            const SizedBox(height: 8),
            _transactionsCard(theme, wallet),
          ],
        ),
        ),
      ),
    );
  }

  // ---- Quick actions (4 in a card with dividers) ----
  Widget _quickActions(ThemeData theme) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 10,
              offset: const Offset(0, 3)),
        ],
      ),
      child: IntrinsicHeight(
        child: Row(
          children: [
            _quickAction(theme, Icons.account_balance_wallet_outlined,
                'wallet.add_money'.tr(), _openTopup),
            _divider(theme),
            _quickAction(theme, Icons.upload_outlined, 'wallet.withdraw'.tr(),
                () => context.push(RouteNames.withdrawal)),
            _divider(theme),
            _quickAction(theme, Icons.description_outlined,
                'wallet.transactions'.tr(),
                () => context.push(RouteNames.transactions)),
            _divider(theme),
            _quickAction(theme, Icons.percent_rounded, 'wallet.offers'.tr(),
                () => context.push(RouteNames.offers)),
          ],
        ),
      ),
    );
  }

  Widget _divider(ThemeData theme) =>
      VerticalDivider(width: 1, color: theme.dividerColor, indent: 4, endIndent: 4);

  Widget _quickAction(
      ThemeData theme, IconData icon, String label, VoidCallback onTap) {
    return Expanded(
      child: InkWell(
        onTap: onTap,
        child: Column(
          children: [
            Icon(icon, color: AppColors.primary, size: 26),
            const SizedBox(height: 8),
            Text(label,
                textAlign: TextAlign.center,
                style: TextStyle(
                    fontSize: 12, color: theme.colorScheme.onSurface)),
          ],
        ),
      ),
    );
  }

  // ---- Exclusive offers banner (green tint) ----
  Widget _offersBanner(ThemeData theme) {
    return InkWell(
      onTap: () => context.push(RouteNames.offers),
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.success.withValues(alpha: 0.10),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: AppColors.success.withValues(alpha: 0.15),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.local_offer_outlined,
                  color: AppColors.success, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('wallet.offers_title'.tr(),
                      style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                          color: theme.colorScheme.onSurface)),
                  const SizedBox(height: 2),
                  Text('wallet.offers_subtitle'.tr(),
                      style: const TextStyle(
                          fontSize: 12, color: AppColors.textSecondary)),
                ],
              ),
            ),
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text('wallet.view_offers'.tr(),
                    style: const TextStyle(
                        fontWeight: FontWeight.w600,
                        color: AppColors.success)),
                const Icon(Icons.chevron_right,
                    size: 18, color: AppColors.success),
              ],
            ),
          ],
        ),
      ),
    );
  }

  // ---- Recent transactions card ----
  Widget _transactionsCard(ThemeData theme, WalletProvider wallet) {
    if (wallet.isLoading) {
      return const Padding(
          padding: EdgeInsets.only(top: 40), child: LoadingWidget());
    }
    if (wallet.transactions.isEmpty) {
      return Padding(
        padding: const EdgeInsets.only(top: 24),
        child: EmptyState(
          icon: Icons.receipt_long_outlined,
          title: 'wallet.no_transactions'.tr(),
          message: 'wallet.no_transactions_msg'.tr(),
        ),
      );
    }

    final txns = wallet.transactions;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 10,
              offset: const Offset(0, 3)),
        ],
      ),
      child: Column(
        children: [
          for (var i = 0; i < txns.length; i++)
            TransactionTile(
              transaction: txns[i],
              showDivider: i != txns.length - 1,
            ),
          if (wallet.isLoadingMore)
            const Padding(
              padding: EdgeInsets.all(16),
              child: Center(child: CircularProgressIndicator()),
            ),
        ],
      ),
    );
  }
}

// ---- Outstanding delivery-charge due banner (red tint) ----
class _DueBanner extends StatelessWidget {
  final WalletModel wallet;

  const _DueBanner({required this.wallet});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final hasLimit = wallet.dueLimitValue > 0;

    return InkWell(
      onTap: () => context.push(RouteNames.dueHistory),
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.danger.withValues(alpha: 0.10),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: AppColors.danger.withValues(alpha: 0.15),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.error_outline,
                  color: AppColors.danger, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('wallet.outstanding_due'.tr(),
                      style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                          color: theme.colorScheme.onSurface)),
                  const SizedBox(height: 2),
                  Text(Helpers.currency(wallet.dueValue),
                      style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                          color: AppColors.danger)),
                  if (hasLimit) ...[
                    const SizedBox(height: 8),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: LinearProgressIndicator(
                        value: (wallet.dueValue / wallet.dueLimitValue)
                            .clamp(0.0, 1.0),
                        minHeight: 4,
                        color: AppColors.danger,
                        backgroundColor:
                            AppColors.danger.withValues(alpha: 0.15),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                        'wallet.due_of_limit'.tr(namedArgs: {
                          'due': Helpers.currency(wallet.dueValue),
                          'limit': Helpers.currency(wallet.dueLimitValue),
                        }),
                        style: const TextStyle(
                            fontSize: 12, color: AppColors.textSecondary)),
                  ],
                ],
              ),
            ),
            const Icon(Icons.chevron_right, size: 18, color: AppColors.danger),
          ],
        ),
      ),
    );
  }
}

// ---- Blue gradient balance card ----
class _BalanceCard extends StatelessWidget {
  final double balance;
  final VoidCallback onAddMoney;

  const _BalanceCard({required this.balance, required this.onAddMoney});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.centerLeft,
          end: Alignment.centerRight,
          colors: [AppColors.primaryDark, AppColors.primary],
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Stack(
        children: [
          // Decorative wallet illustration (subtle, trailing).
          PositionedDirectional(
            end: -10,
            top: 4,
            child: Icon(
              Icons.account_balance_wallet,
              size: 120,
              color: Colors.white.withValues(alpha: 0.10),
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 48,
                    height: 48,
                    alignment: Alignment.center,
                    decoration: const BoxDecoration(
                        color: Colors.white, shape: BoxShape.circle),
                    child: const Icon(Icons.account_balance_wallet_outlined,
                        color: AppColors.primary),
                  ),
                  const SizedBox(width: 14),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('home.wallet_balance'.tr(),
                          style: const TextStyle(
                              color: Colors.white70, fontSize: 13)),
                      const SizedBox(height: 2),
                      Text(Helpers.currency(balance),
                          style: const TextStyle(
                              color: Colors.white,
                              fontSize: 28,
                              fontWeight: FontWeight.bold)),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 18),
              // White "+ Add Money" button.
              Material(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                child: InkWell(
                  borderRadius: BorderRadius.circular(12),
                  onTap: onAddMoney,
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 18, vertical: 12),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.add,
                            color: AppColors.primary, size: 20),
                        const SizedBox(width: 6),
                        Text('wallet.add_money'.tr(),
                            style: const TextStyle(
                                color: AppColors.primary,
                                fontWeight: FontWeight.bold)),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
