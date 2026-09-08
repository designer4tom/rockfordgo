import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_constants.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/services/config_service.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../auth/provider/auth_provider.dart';
import '../../provider/profile_provider.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => context.read<ProfileProvider>().loadProfile(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final profile = context.watch<ProfileProvider>();
    final auth = context.watch<AuthProvider>();
    final user = profile.user ?? auth.user;

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: SafeArea(
        child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
        children: [
          _ProfileHeader(
            name: user?.name ?? '—',
            phone: user?.phone ?? '',
            email: user?.email,
            avatar: user?.avatar,
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: _StatCard(
                  icon: Icons.account_balance_wallet_outlined,
                  iconColor: AppColors.primary,
                  iconBg: AppColors.primary.withValues(alpha: 0.12),
                  label: 'home.wallet_balance'.tr(),
                  value: Helpers.currency(
                      double.tryParse(user?.walletBalance ?? '0') ?? 0),
                  action: 'home.view_wallet'.tr(),
                  onTap: () => context.go(RouteNames.wallet),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: _StatCard(
                  icon: Icons.star_border_rounded,
                  iconColor: AppColors.success,
                  iconBg: AppColors.success.withValues(alpha: 0.14),
                  label: 'profile.reward_points'.tr(),
                  value: '${profile.rewardPoints}',
                  action: 'profile.view_rewards'.tr(),
                  onTap: () => SnackbarHelper.showInfo(
                      context, 'home.coming_soon'.tr()),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          _MenuCard(
            items: [
              _MenuItem(Icons.person_outline, 'profile.personal_information'.tr(),
                  () => context.push('/edit-profile')),
              _MenuItem(Icons.credit_card_outlined,
                  'profile.payment_methods'.tr(),
                  () => context.go(RouteNames.wallet)),
              _MenuItem(Icons.place_outlined, 'home.saved_places'.tr(),
                  () => context.push(RouteNames.favourites)),
              _MenuItem(Icons.local_offer_outlined, 'profile.promo_codes'.tr(),
                  () => context.push(RouteNames.offers)),
              _MenuItem(Icons.card_giftcard_outlined, 'home.refer_earn'.tr(),
                  () => context.push(RouteNames.referral)),
              _MenuItem(Icons.notifications_outlined,
                  'profile.notification_settings'.tr(),
                  () => context.push(RouteNames.settings)),
              _MenuItem(Icons.headset_mic_outlined, 'home.help_center'.tr(),
                  () => context.push(RouteNames.helpCenter)),
              _MenuItem(Icons.settings_outlined, 'home.settings'.tr(),
                  () => context.push(RouteNames.settings)),
            ],
          ),
          const SizedBox(height: 20),
          // Logout + Delete account (danger zone).
          _MenuCard(
            items: [
              _MenuItem(Icons.logout, 'profile.logout_title'.tr(),
                  _confirmLogout,
                  color: AppColors.danger),
              _MenuItem(Icons.delete_forever_outlined,
                  'profile.delete_title'.tr(), _confirmDelete,
                  color: AppColors.danger),
            ],
          ),
          const SizedBox(height: 16),
          Center(
            child: Text(
              '${ConfigService.getCached().appName} v${AppConstants.appVersion}',
              style: const TextStyle(
                  fontSize: 12, color: AppColors.textSecondary),
            ),
          ),
        ],
        ),
      ),
    );
  }

  Future<void> _confirmLogout() async {
    final ok = await _confirm(
        'profile.logout_title'.tr(), 'profile.logout_msg'.tr());
    if (ok != true || !mounted) return;
    await context.read<AuthProvider>().logout();
    if (mounted) context.go(RouteNames.phoneEntry);
  }

  Future<void> _confirmDelete() async {
    final ok = await _confirm(
      'profile.delete_title'.tr(),
      'profile.delete_msg'.tr(),
      danger: true,
    );
    if (ok != true || !mounted) return;
    final provider = context.read<ProfileProvider>();
    final deleted = await provider.deleteAccount(null);
    if (!mounted) return;
    if (deleted) {
      await context.read<AuthProvider>().logout();
      if (mounted) context.go(RouteNames.phoneEntry);
    } else if (provider.deleteErrorStatus == 422) {
      // Blocked by a server precondition (outstanding due / ongoing order) —
      // show the server message and offer the wallet to clear a due.
      _showDeleteBlockedDialog(
          provider.error ?? 'profile.delete_failed'.tr());
    } else {
      SnackbarHelper.showError(
          context, provider.error ?? 'profile.delete_failed'.tr());
    }
  }

  void _showDeleteBlockedDialog(String message) {
    showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('profile.delete_title'.tr()),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text('common.cancel'.tr()),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              context.go(RouteNames.wallet);
            },
            child: Text('wallet.go_to_wallet'.tr()),
          ),
        ],
      ),
    );
  }

  Future<bool?> _confirm(String title, String msg, {bool danger = false}) {
    final theme = Theme.of(context);
    final accent = danger ? AppColors.danger : AppColors.primary;
    final icon = danger ? Icons.delete_outline : Icons.logout_rounded;

    return showDialog<bool>(
      context: context,
      barrierColor: Colors.black.withValues(alpha: 0.45),
      builder: (ctx) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.symmetric(horizontal: 28),
        child: Container(
          padding: const EdgeInsets.fromLTRB(20, 24, 20, 20),
          decoration: BoxDecoration(
            color: theme.cardColor,
            borderRadius: BorderRadius.circular(22),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.08),
                blurRadius: 24,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 72,
                height: 72,
                decoration: BoxDecoration(
                  color: accent.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                alignment: Alignment.center,
                child: Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: accent,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(icon, color: Colors.white, size: 26),
                ),
              ),
              const SizedBox(height: 16),
              // Text(
              //   title,
              //   textAlign: TextAlign.center,
              //   style: TextStyle(
              //     fontSize: 18,
              //     fontWeight: FontWeight.bold,
              //     color: theme.colorScheme.onSurface,
              //   ),
              // ),
              //const SizedBox(height: 8),
              Text(
                msg,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 13.5,
                  height: 1.4,
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 22),
              Row(
                children: [
                  Expanded(
                    child: SizedBox(
                      height: 48,
                      child: OutlinedButton(
                        onPressed: () => Navigator.pop(ctx, false),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: theme.colorScheme.onSurface,
                          side: BorderSide(color: theme.dividerColor),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: Text(
                          'common.no'.tr(),
                          style: const TextStyle(
                              fontSize: 14, fontWeight: FontWeight.w600),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: SizedBox(
                      height: 48,
                      child: ElevatedButton(
                        onPressed: () => Navigator.pop(ctx, true),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: accent,
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        child: Text(
                          'common.yes'.tr(),
                          style: const TextStyle(
                              fontSize: 14, fontWeight: FontWeight.w600),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ProfileHeader extends StatelessWidget {
  final String name;
  final String phone;
  final String? email;
  final String? avatar;

  const _ProfileHeader({
    required this.name,
    required this.phone,
    required this.email,
    required this.avatar,
  });

  String get _displayPhone {
    if (phone.isEmpty) return '';
    if (phone.startsWith('+')) return phone;
    return '${ConfigService.getCached().phoneCode} $phone';
  }

  @override
  Widget build(BuildContext context) {
    final onSurface = Theme.of(context).colorScheme.onSurface;
    return InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: () => context.push('/edit-profile'),
      child: Row(
        children: [
          // Avatar with edit pencil overlay.
          Stack(
            clipBehavior: Clip.none,
            children: [
              Container(
                padding: const EdgeInsets.all(3),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white, width: 3),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.08),
                      blurRadius: 10,
                    ),
                  ],
                ),
                child: CircleAvatar(
                  radius: 40,
                  backgroundColor: AppColors.primary.withValues(alpha: 0.15),
                  backgroundImage: (avatar != null && avatar!.isNotEmpty)
                      ? NetworkImage(avatar!)
                      : null,
                  child: (avatar == null || avatar!.isEmpty)
                      ? const Icon(Icons.person,
                          size: 42, color: AppColors.primary)
                      : null,
                ),
              ),
              PositionedDirectional(
                bottom: 0,
                end: 0,
                child: Container(
                  width: 28,
                  height: 28,
                  decoration: BoxDecoration(
                    color: AppColors.primary,
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white, width: 2),
                  ),
                  child: const Icon(Icons.edit, size: 14, color: Colors.white),
                ),
              ),
            ],
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(name,
                    style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: onSurface)),
                if (_displayPhone.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(_displayPhone,
                      style: const TextStyle(
                          fontSize: 13, color: AppColors.textSecondary)),
                ],
                if (email != null && email!.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(email!,
                      style: const TextStyle(
                          fontSize: 13, color: AppColors.textSecondary)),
                ],
                const SizedBox(height: 6),
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.verified, size: 16,
                        color: AppColors.primary),
                    const SizedBox(width: 4),
                    Text('home.verified'.tr(),
                        style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: AppColors.primary)),
                  ],
                ),
              ],
            ),
          ),
          const Icon(Icons.chevron_right, color: AppColors.textSecondary),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final Color iconBg;
  final String label;
  final String value;
  final String action;
  final VoidCallback onTap;

  const _StatCard({
    required this.icon,
    required this.iconColor,
    required this.iconBg,
    required this.label,
    required this.value,
    required this.action,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(color: iconBg, shape: BoxShape.circle),
            child: Icon(icon, color: iconColor, size: 22),
          ),
          const SizedBox(width: 10),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label,
                textAlign: TextAlign.center,
                style: const TextStyle(
                    fontSize: 12, color: AppColors.textSecondary)),
            const SizedBox(height: 4),
            Text(value,
                style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.onSurface)),
            const SizedBox(height: 4),
            InkWell(
              onTap: onTap,
              borderRadius: BorderRadius.circular(8),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(action,
                      style: const TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
                          color: AppColors.primary)),
                  const Icon(Icons.chevron_right,
                      size: 18, color: AppColors.primary),
                ],
              ),
            ),
          ],
        )
        ],
      ),
    );
  }
}

class _MenuItem {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Color? color;
  const _MenuItem(this.icon, this.label, this.onTap, {this.color});
}

class _MenuCard extends StatelessWidget {
  final List<_MenuItem> items;
  const _MenuCard({required this.items});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
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
          for (int i = 0; i < items.length; i++) ...[
            InkWell(
              borderRadius: BorderRadius.vertical(
                top: i == 0 ? const Radius.circular(16) : Radius.zero,
                bottom: i == items.length - 1
                    ? const Radius.circular(16)
                    : Radius.zero,
              ),
              onTap: items[i].onTap,
              child: Padding(
                padding:
                    const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                child: Row(
                  children: [
                    Icon(items[i].icon,
                        size: 22,
                        color: items[i].color ??
                            Theme.of(context).colorScheme.onSurface),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Text(items[i].label,
                          style: TextStyle(
                              fontSize: 15,
                              color: items[i].color ??
                                  Theme.of(context).colorScheme.onSurface)),
                    ),
                    const Icon(Icons.chevron_right,
                        size: 20, color: AppColors.textSecondary),
                  ],
                ),
              ),
            ),
            if (i != items.length - 1)
              Divider(
                  height: 1,
                  indent: 54,
                  color: Theme.of(context).dividerColor.withValues(alpha: 0.1)),
          ],
        ],
      ),
    );
  }
}

