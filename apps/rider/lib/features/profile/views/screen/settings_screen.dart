import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../auth/provider/auth_provider.dart';
import '../../../settings/provider/theme_provider.dart';
import '../../provider/profile_provider.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  bool _pushEnabled = true;
  bool _promoEnabled = false;

  @override
  Widget build(BuildContext context) {
    final theme = context.watch<ThemeProvider>();
    final lang = context.locale.languageCode;

    return Scaffold(
      appBar: AppBar(title: Text('settings.title'.tr())),
      body: ListView(
        children: [
          _SectionHeader('settings.appearance'.tr()),
          _choice('settings.system_default'.tr(),
              theme.themeMode == ThemeMode.system,
              () => theme.setTheme(ThemeMode.system)),
          _choice('settings.light_mode'.tr(),
              theme.themeMode == ThemeMode.light,
              () => theme.setTheme(ThemeMode.light)),
          _choice('settings.dark_mode'.tr(),
              theme.themeMode == ThemeMode.dark,
              () => theme.setTheme(ThemeMode.dark)),
          const Divider(),
          _SectionHeader('settings.language'.tr()),
          _choice('settings.english'.tr(), lang == 'en',
              () => context.setLocale(const Locale('en'))),
          _choice('settings.arabic'.tr(), lang == 'ar',
              () => context.setLocale(const Locale('ar'))),
          const Divider(),
          _SectionHeader('settings.notifications'.tr()),
          SwitchListTile(
            title: Text('settings.push_notifications'.tr()),
            value: _pushEnabled,
            activeThumbColor: AppColors.primary,
            onChanged: (v) => setState(() => _pushEnabled = v),
          ),
          SwitchListTile(
            title: Text('settings.promo_offers'.tr()),
            value: _promoEnabled,
            activeThumbColor: AppColors.primary,
            onChanged: (v) => setState(() => _promoEnabled = v),
          ),
          const Divider(),
          _SectionHeader('settings.legal'.tr()),
          ListTile(
            title: Text('settings.privacy_policy'.tr()),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => context.push('/page/privacy-policy'),
          ),
          ListTile(
            title: Text('settings.terms'.tr()),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => context.push('/page/terms-conditions'),
          ),
          const Divider(),
          _SectionHeader('settings.account'.tr()),
          ListTile(
            leading: const Icon(Icons.logout, color: AppColors.danger),
            title: Text('profile.logout_title'.tr(),
                style: const TextStyle(color: AppColors.danger)),
            onTap: _confirmLogout,
          ),
          ListTile(
            leading: const Icon(Icons.delete_forever_outlined,
                color: AppColors.danger),
            title: Text('profile.delete_title'.tr(),
                style: const TextStyle(color: AppColors.danger)),
            onTap: _confirmDelete,
          ),
          const Divider(),
          // ListTile(
          //   title: Text('settings.app_version'.tr()),
          //   trailing: const Text('1.0.0'),
          // ),
        ],
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
    return showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title),
        content: Text(msg),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: Text('common.no'.tr())),
          ElevatedButton(
            style: danger
                ? ElevatedButton.styleFrom(backgroundColor: AppColors.danger)
                : null,
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('common.yes'.tr()),
          ),
        ],
      ),
    );
  }

  Widget _choice(String label, bool selected, VoidCallback onTap) {
    return ListTile(
      title: Text(label),
      trailing: Icon(
        selected ? Icons.radio_button_checked : Icons.radio_button_unchecked,
        color: selected ? AppColors.primary : null,
      ),
      onTap: onTap,
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final String title;
  const _SectionHeader(this.title);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsetsDirectional.only(start: 16, top: 16, bottom: 8),
      child: Text(
        title,
        style: TextStyle(
          color: Theme.of(context).colorScheme.primary,
          fontWeight: FontWeight.w600,
          fontSize: 13,
        ),
      ),
    );
  }
}
