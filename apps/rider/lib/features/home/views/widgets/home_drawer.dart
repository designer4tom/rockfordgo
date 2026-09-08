import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:readyride_customer/features/profile/provider/profile_provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_constants.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/services/config_service.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../auth/provider/auth_provider.dart';
import '../../../history/provider/history_provider.dart';
import '../../../wallet/provider/wallet_provider.dart';

class HomeDrawer extends StatelessWidget {
  const HomeDrawer({super.key});

  @override
  Widget build(BuildContext context) {
    final profile = context.watch<ProfileProvider>();
    final auth = context.watch<AuthProvider>();

    //final user = context.select<AuthProvider, dynamic>((p) => p.user);
    final user = profile.user ?? auth.user;
    // Live wallet balance (falls back to the user's cached balance).
    final liveBalance = context.select<WalletProvider, String?>(
        (p) => p.wallet?.balance);

    return Drawer(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      child: SafeArea(
        bottom: false,
        child: Column(
          children: [
            _Header(
              name: user?.name ?? '',
              phone: user?.phone ?? '',
              avatar: user?.avatar,
              walletBalance: liveBalance ?? user?.walletBalance ?? '0.00',
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(vertical: 8),
                children: [
                  // ---- Group 1: core trips ----
                  _DrawerTile(
                    icon: Icons.home_rounded,
                    label: 'home.nav_home'.tr(),
                    selected: true,
                    onTap: () => _goTab(context, RouteNames.home),
                  ),
                  _DrawerTile(
                    icon: Icons.receipt_long_outlined,
                    label: 'home.my_bookings'.tr(),
                    onTap: () => _openHistory(context, 'all'),
                  ),
                  _DrawerTile(
                    icon: Icons.schedule_outlined,
                    label: 'home.schedule_rides'.tr(),
                    onTap: () => _comingSoon(context),
                  ),
                  _DrawerTile(
                    icon: Icons.local_shipping_outlined,
                    label: 'home.courier_orders'.tr(),
                    onTap: () => _openHistory(context, 'parcel'),
                  ),
                  _DrawerTile(
                    icon: Icons.place_outlined,
                    label: 'home.saved_places'.tr(),
                    onTap: () => _go(context, RouteNames.favourites),
                  ),

                  const _DrawerDivider(),

                  // ---- Group 2: account & support ----
                  _DrawerTile(
                    icon: Icons.credit_card_outlined,
                    label: 'home.payments'.tr(),
                    onTap: () => _goTab(context, RouteNames.wallet),
                  ),
                  _DrawerTile(
                    icon: Icons.local_offer_outlined,
                    label: 'home.promotions'.tr(),
                    onTap: () => _go(context, RouteNames.offers),
                  ),
                  _DrawerTile(
                    icon: Icons.card_giftcard_outlined,
                    label: 'home.refer_earn'.tr(),
                    onTap: () => _go(context, RouteNames.referral),
                  ),
                  _DrawerTile(
                    icon: Icons.insights_outlined,
                    label: 'home.performance'.tr(),
                    onTap: () => _go(context, RouteNames.performance),
                  ),
                  _DrawerTile(
                    icon: Icons.notifications_outlined,
                    label: 'home.notifications'.tr(),
                    onTap: () => _go(context, RouteNames.notifications),
                  ),
                  _DrawerTile(
                    icon: Icons.headset_mic_outlined,
                    label: 'home.help_center'.tr(),
                    onTap: () => _go(context, RouteNames.helpCenter),
                  ),
                  _DrawerTile(
                    icon: Icons.settings_outlined,
                    label: 'home.settings'.tr(),
                    onTap: () => _go(context, RouteNames.settings),
                  ),
                ],
              ),
            ),

            // ---- App version footer ----
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 16),
              child: Text(
                '${ConfigService.getCached().appName} v${AppConstants.appVersion}',
                style: const TextStyle(
                  fontSize: 12,
                  color: AppColors.textSecondary,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _go(BuildContext context, String route) {
    Navigator.pop(context);
    context.push(route);
  }

  /// Switch to one of the shell tabs (home/history/wallet/profile) instead of
  /// pushing a new route over the shell.
  void _goTab(BuildContext context, String route) {
    Navigator.pop(context);
    context.go(route);
  }

  void _openHistory(BuildContext context, String filter) {
    context.read<HistoryProvider>().setFilter(filter);
    Navigator.pop(context);
    context.go(RouteNames.history);
  }

  void _comingSoon(BuildContext context) {
    Navigator.pop(context);
    SnackbarHelper.showInfo(context, 'home.coming_soon'.tr());
  }
}

/// Profile header with avatar, name, phone, Verified badge and wallet card.
class _Header extends StatelessWidget {
  final String name;
  final String phone;
  final String? avatar;
  final String walletBalance;

  const _Header({
    required this.name,
    required this.phone,
    required this.avatar,
    required this.walletBalance,
  });

  String get _displayPhone {
    if (phone.isEmpty) return '';
    if (phone.startsWith('+')) return phone;
    return '${ConfigService.getCached().phoneCode} $phone';
  }

  @override
  Widget build(BuildContext context) {
    final onSurface = Theme.of(context).colorScheme.onSurface;

    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            AppColors.primary.withValues(alpha: 0.10),
            AppColors.primary.withValues(alpha: 0.02),
          ],
        ),
      ),
      child: Stack(
        children: [
          // Subtle decorative circle (top-trailing), matches the design.
          PositionedDirectional(
            top: -28,
            end: -20,
            child: Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: AppColors.primary.withValues(alpha: 0.07),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Avatar + name + phone + verified
                InkWell(
                  borderRadius: BorderRadius.circular(12),
                  onTap: () {
                    Navigator.pop(context);
                    context.go(RouteNames.profile);
                  },
                  child: Row(
                    children: [
                      CircleAvatar(
                        radius: 32,
                        backgroundColor: AppColors.primary.withValues(alpha: 0.15),
                        backgroundImage: (avatar != null && avatar!.isNotEmpty)
                            ? NetworkImage(avatar!)
                            : null,
                        child: (avatar == null || avatar!.isEmpty)
                            ? const Icon(Icons.person,
                                size: 34, color: AppColors.primary)
                            : null,
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              name.isEmpty ? 'app_name'.tr() : name,
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: onSurface,
                              ),
                            ),
                            if (_displayPhone.isNotEmpty) ...[
                              const SizedBox(height: 2),
                              Text(
                                _displayPhone,
                                style: const TextStyle(
                                  fontSize: 13,
                                  color: AppColors.textSecondary,
                                ),
                              ),
                            ],
                            const SizedBox(height: 6),
                            // OTP login implies a verified phone → always shown.
                            Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                const Icon(Icons.verified,
                                    size: 16, color: AppColors.primary),
                                const SizedBox(width: 4),
                                Text(
                                  'home.verified'.tr(),
                                  style: const TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.w600,
                                    color: AppColors.primary,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                _WalletCard(balance: walletBalance),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _WalletCard extends StatelessWidget {
  final String balance;
  const _WalletCard({required this.balance});

  @override
  Widget build(BuildContext context) {
    final amount = Helpers.currency(double.tryParse(balance) ?? 0);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.12),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.account_balance_wallet_outlined,
                color: AppColors.primary, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'home.wallet_balance'.tr(),
                  style: const TextStyle(
                    fontSize: 12,
                    color: AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  amount,
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                ),
              ],
            ),
          ),
          InkWell(
            borderRadius: BorderRadius.circular(8),
            onTap: () {
              Navigator.pop(context);
              context.go(RouteNames.wallet);
            },
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    'home.view_wallet'.tr(),
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: AppColors.primary,
                    ),
                  ),
                  const Icon(Icons.chevron_right,
                      size: 18, color: AppColors.primary),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// A single drawer menu row: icon + label + trailing chevron.
/// When [selected] the row gets a soft primary highlight.
class _DrawerTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final bool selected;

  const _DrawerTile({
    required this.icon,
    required this.label,
    required this.onTap,
    this.selected = false,
  });

  @override
  Widget build(BuildContext context) {
    final color =
        selected ? AppColors.primary : Theme.of(context).colorScheme.onSurface;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
      child: Material(
        color: selected
            ? AppColors.primary.withValues(alpha: 0.10)
            : Colors.transparent,
        borderRadius: BorderRadius.circular(12),
        child: InkWell(
          borderRadius: BorderRadius.circular(12),
          onTap: onTap,
          child: Padding(
            padding:
                const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
            child: Row(
              children: [
                Icon(icon, size: 22, color: color),
                const SizedBox(width: 16),
                Expanded(
                  child: Text(
                    label,
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight:
                          selected ? FontWeight.w600 : FontWeight.w500,
                      color: color,
                    ),
                  ),
                ),
                Icon(Icons.chevron_right,
                    size: 20,
                    color: selected
                        ? AppColors.primary
                        : AppColors.textSecondary),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _DrawerDivider extends StatelessWidget {
  const _DrawerDivider();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
      child: Divider(height: 1, color: Theme.of(context).dividerColor),
    );
  }
}
