import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_constants.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../auth/provider/auth_provider.dart';
import '../../../home/provider/home_provider.dart';
import '../../../notification/provider/notification_provider.dart';
import '../../../settings/provider/theme_provider.dart';
import '../../provider/profile_provider.dart';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  static const _accent = Color(0xFF5B3DF6);

  @override
  Widget build(BuildContext context) {
    final themeProvider = context.watch<ThemeProvider>();
    final currentLang = context.locale.languageCode;

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: Text('settings.settings'.tr())),
      body: SafeArea(
        top: false,
        child: ListView(
          padding: EdgeInsets.fromLTRB(16.w, 8.h, 16.w, 28.h),
          children: [
            // ---- Theme ----
            _section(context, 'settings.theme'.tr()),
            _card([
              _themeOption(
                context,
                themeProvider,
                ThemeMode.system,
                'settings.system_default'.tr(),
                Icons.brightness_auto_outlined,
              ),
              _divider(),
              _themeOption(
                context,
                themeProvider,
                ThemeMode.light,
                'settings.light_mode'.tr(),
                Icons.light_mode_outlined,
              ),
              _divider(),
              _themeOption(
                context,
                themeProvider,
                ThemeMode.dark,
                'settings.dark_mode'.tr(),
                Icons.dark_mode_outlined,
              ),
            ]),

            // ---- Language ----
            _section(context, 'settings.language'.tr()),
            _card([
              _langOption(context, 'en', 'settings.english'.tr(), currentLang),
              _divider(),
              _langOption(context, 'ar', 'settings.arabic'.tr(), currentLang),
            ]),

            // ---- Privacy ----
            _section(context, 'settings.privacy_location'.tr()),
            _card([
              _menuItem(
                context,
                icon: Icons.privacy_tip_outlined,
                iconBg: const Color(0xFFF1ECFF),
                iconColor: _accent,
                title: 'settings.privacy_policy'.tr(),
                onTap: () => context.push('/page/privacy-policy'),
              ),
              _divider(),
              _menuItem(
                context,
                icon: Icons.description_outlined,
                iconBg: const Color(0xFFEAF4FF),
                iconColor: const Color(0xFF1E88E5),
                title: 'settings.terms_conditions'.tr(),
                onTap: () => context.push('/page/terms-conditions'),
              ),
            ]),

            // ---- About ----
            _section(context, 'settings.about'.tr()),
            _card([
              _menuItem(
                context,
                icon: Icons.info_outline,
                iconBg: const Color(0xFFE8F7EF),
                iconColor: const Color(0xFF22B07D),
                title: 'settings.app_version'.tr(),
                showChevron: false,
                trailing: Text(
                  AppConstants.appVersion,
                  style: TextStyle(
                    color: Theme.of(context).hintColor,
                    fontWeight: FontWeight.w600,
                    fontSize: 13.sp,
                  ),
                ),
              ),
            ]),

            // ---- Account ----
            _section(context, 'settings.account'.tr()),
            _card([
              _menuItem(
                context,
                icon: Icons.delete_forever_outlined,
                iconBg: const Color(0xFFFDECEC),
                iconColor: AppColors.danger,
                title: 'settings.delete_account'.tr(),
                danger: true,
                onTap: () => _confirmDeleteAccount(context),
              ),
            ]),

            SizedBox(height: 28.h),
            Center(
              child: Text(
                '${AppConstants.appName} v${AppConstants.appVersion}',
                style: TextStyle(
                  fontSize: 12.5.sp,
                  color: Theme.of(context).hintColor,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// Rounded, soft-shadow card matching the profile menu card look.
  Widget _card(List<Widget> children) {
    return Builder(
      builder: (context) {
        return Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(22.r),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.035),
                blurRadius: 18,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          // The background lives on a Material (not the BoxDecoration) so the
          // ListTiles' ink splashes stay visible.
          child: Material(
            color: Theme.of(context).cardColor,
            borderRadius: BorderRadius.circular(22.r),
            clipBehavior: Clip.antiAlias,
            child: Column(children: children),
          ),
        );
      },
    );
  }

  Widget _divider() {
    return Builder(
      builder: (context) {
        return Container(
          height: 1,
          margin: EdgeInsets.only(left: 70.w),
          color: Theme.of(context).dividerColor.withValues(alpha: 0.5),
        );
      },
    );
  }

  /// Subtle neutral background for an unselected/neutral icon tile that stays
  /// visible in both light and dark mode.
  static Color _neutralIconBg(BuildContext context) =>
      Theme.of(context).brightness == Brightness.dark
      ? Colors.white.withValues(alpha: 0.08)
      : const Color(0xFFF1EEF6);

  /// Background for the accent (selected) icon tile, tinted for dark mode.
  static Color _accentIconBg(BuildContext context) =>
      Theme.of(context).brightness == Brightness.dark
      ? _accent.withValues(alpha: 0.22)
      : const Color(0xFFF1ECFF);

  /// Generic menu row with a colored rounded-square icon tile, bold title and
  /// an optional chevron — mirrors the profile screen's `_menuItem`.
  Widget _menuItem(
    BuildContext context, {
    required IconData icon,
    required Color iconBg,
    required Color iconColor,
    required String title,
    VoidCallback? onTap,
    Widget? trailing,
    bool danger = false,
    bool showChevron = true,
  }) {
    return ListTile(
      contentPadding: EdgeInsets.symmetric(horizontal: 14.w, vertical: 4.h),
      minLeadingWidth: 0,
      leading: Container(
        width: 44.r,
        height: 44.r,
        decoration: BoxDecoration(
          color: iconBg,
          borderRadius: BorderRadius.circular(12.r),
        ),
        child: Icon(icon, color: iconColor, size: 22.sp),
      ),
      title: Text(
        title,
        style: TextStyle(
          color: danger
              ? AppColors.danger
              : Theme.of(context).colorScheme.onSurface,
          fontSize: 14.sp,
          fontWeight: FontWeight.w700,
        ),
      ),
      trailing:
          trailing ??
          (showChevron
              ? Icon(
                  Icons.chevron_right,
                  size: 22.sp,
                  color: Theme.of(context).hintColor,
                )
              : null),
      onTap: onTap,
    );
  }

  Widget _themeOption(
    BuildContext context,
    ThemeProvider p,
    ThemeMode mode,
    String label,
    IconData icon,
  ) {
    final selected = p.themeMode == mode;
    return _menuItem(
      context,
      icon: icon,
      iconBg: selected ? _accentIconBg(context) : _neutralIconBg(context),
      iconColor: selected ? _accent : Theme.of(context).hintColor,
      title: label,
      showChevron: false,
      trailing: selected
          ? Icon(Icons.check_circle, color: _accent, size: 22.sp)
          : null,
      onTap: () => p.setTheme(mode),
    );
  }

  Widget _langOption(
    BuildContext context,
    String code,
    String label,
    String current,
  ) {
    final selected = current == code;
    return _menuItem(
      context,
      icon: Icons.translate,
      iconBg: selected ? _accentIconBg(context) : _neutralIconBg(context),
      iconColor: selected ? _accent : Theme.of(context).hintColor,
      title: label,
      showChevron: false,
      trailing: selected
          ? Icon(Icons.check_circle, color: _accent, size: 22.sp)
          : null,
      onTap: () {
        if (!selected) context.setLocale(Locale(code));
      },
    );
  }

  Future<void> _confirmDeleteAccount(BuildContext context) async {
    final reasonController = TextEditingController();
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: Text('settings.delete_account'.tr()),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('settings.delete_account_confirm'.tr()),
            const SizedBox(height: 16),
            TextField(
              controller: reasonController,
              maxLines: 2,
              decoration: InputDecoration(
                hintText: 'settings.delete_account_reason_hint'.tr(),
                border: const OutlineInputBorder(),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: Text('common.cancel'.tr()),
          ),
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: Text(
              'settings.delete'.tr(),
              style: const TextStyle(color: AppColors.danger),
            ),
          ),
        ],
      ),
    );

    if (confirmed != true || !context.mounted) return;

    final provider = context.read<ProfileProvider>();
    final auth = context.read<AuthProvider>();
    final home = context.read<HomeProvider>();
    final notifications = context.read<NotificationProvider>();
    final reason = reasonController.text.trim();
    final ok = await provider.deleteAccount(reason.isEmpty ? null : reason);
    if (!context.mounted) return;

    if (ok) {
      // The account is already deleted server-side and the token is revoked,
      // so we must NOT call the logout API (it would 401). Clear local
      // auth/session + every cached driver-scoped provider so the next
      // sign-in starts from a clean slate, then go to the "Join as a Driver"
      // page.
      provider.reset();
      await home.reset();
      notifications.reset();
      await auth.clearSession();
      if (!context.mounted) return;
      context.go(RouteNames.phoneEntry);
      AppSnackbar.show(context, 'settings.account_deleted'.tr());
    } else {
      AppSnackbar.error(
        context,
        provider.error ?? 'settings.delete_account_failed'.tr(),
      );
    }
  }

  Widget _section(BuildContext context, String title) {
    return Padding(
      padding: EdgeInsets.fromLTRB(8.w, 22.h, 8.w, 10.h),
      child: Text(
        title.toUpperCase(),
        style: TextStyle(
          fontSize: 12.sp,
          fontWeight: FontWeight.w700,
          letterSpacing: 0.7,
          color: Theme.of(context).hintColor,
        ),
      ),
    );
  }
}
