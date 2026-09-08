import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_constants.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/app_side_drawer.dart';
import '../../../auth/model/driver_model.dart';
import '../../../auth/provider/auth_provider.dart';
import '../../../dashboard/dashboard_tab.dart';

/// Driver drawer — maps the driver's data + existing menu into [AppSideDrawer].
class HomeDrawer extends StatelessWidget {
  final DriverModel? driver;
  const HomeDrawer({super.key, this.driver});

  void _go(BuildContext context, String route) {
    Navigator.pop(context); // close drawer first
    context.push(route);
  }

  /// Close the drawer and switch the dashboard's bottom-nav tab, so Profile /
  /// Earnings land on the real dashboard sections instead of a pushed route.
  void _switchTab(BuildContext context, int tab) {
    Navigator.pop(context); // close drawer first
    DashboardTab.index.value = tab;
  }

  @override
  Widget build(BuildContext context) {
    final balance = double.tryParse(driver?.walletBalance ?? '0') ?? 0;

    return AppSideDrawer(
      appName: AppConstants.appName,
      version: AppConstants.appVersion,
      header: DrawerHeaderData(
        name: driver?.name ?? 'home.driver'.tr(),
        subtitle: driver?.phone ?? '',
        avatarUrl: driver?.avatar,
        verified: driver?.isApproved ?? false,
        verifiedLabel: 'home.verified'.tr(),
        unverifiedLabel: (driver?.status ?? 'pending').toUpperCase(),
      ),
      summary: DrawerSummaryCard(
        icon: Icons.account_balance_wallet_outlined,
        label: 'wallet.balance'.tr(),
        value: Helpers.money(balance),
        actionLabel: 'wallet.view_wallet'.tr(),
        onAction: () => _go(context, RouteNames.wallet),
      ),
      items: [
        // Group 0 — account & operations
        DrawerMenuItem(
          icon: Icons.person_outline,
          label: 'profile.title'.tr(),
          route: RouteNames.profile,
          // Switch to the dashboard's Profile tab instead of pushing a route.
          onTap: () => _switchTab(context, DashboardTab.profile),
        ),
        DrawerMenuItem(
          icon: Icons.bar_chart_rounded,
          label: 'earnings.title'.tr(),
          route: RouteNames.earnings,
          // Switch to the dashboard's Earnings tab instead of pushing a route.
          onTap: () => _switchTab(context, DashboardTab.earnings),
        ),
        DrawerMenuItem(
          icon: Icons.account_balance_wallet_outlined,
          label: 'wallet.wallet_withdrawal'.tr(),
          route: RouteNames.wallet,
          onTap: () => _go(context, RouteNames.wallet),
        ),
        DrawerMenuItem(
          icon: Icons.history_rounded,
          label: 'history.trip_history'.tr(),
          route: RouteNames.history,
          onTap: () => _go(context, RouteNames.history),
        ),
        DrawerMenuItem(
          icon: Icons.description_outlined,
          label: 'documents.title'.tr(),
          route: RouteNames.documents,
          onTap: () => _go(context, RouteNames.documents),
        ),
        DrawerMenuItem(
          icon: Icons.insights_outlined,
          label: 'performance.title'.tr(),
          route: RouteNames.performance,
          onTap: () => _go(context, RouteNames.performance),
        ),
        // Group 1 — account & support
        DrawerMenuItem(
          icon: Icons.notifications_outlined,
          label: 'notification.title'.tr(),
          route: RouteNames.notifications,
          groupId: 1,
          onTap: () => _go(context, RouteNames.notifications),
        ),
        DrawerMenuItem(
          icon: Icons.settings_outlined,
          label: 'settings.title'.tr(),
          route: RouteNames.settings,
          groupId: 1,
          onTap: () => _go(context, RouteNames.settings),
        ),
        DrawerMenuItem(
          icon: Icons.headset_mic_outlined,
          label: 'home.help_center'.tr(),
          groupId: 1,
          onTap: () {
            Navigator.pop(context);
            AppSnackbar.show(context, 'home.help_center_coming_soon'.tr());
          },
        ),
        // Group 2 — destructive
        DrawerMenuItem(
          icon: Icons.logout_rounded,
          label: 'auth.logout'.tr(),
          danger: true,
          groupId: 2,
          onTap: () => _logout(context),
        ),
      ],
    );
  }

  Future<void> _logout(BuildContext context) async {
    // Capture the router and provider BEFORE popping the drawer. Closing the
    // drawer unmounts this row's context, so reading them (or gating on
    // `context.mounted`) after the await would fail silently — which left the
    // driver on the Home screen and made Logout seem to need several taps.
    final router = GoRouter.of(context);
    final auth = context.read<AuthProvider>();
    Navigator.pop(context); // close drawer
    await auth.logout();
    router.go(RouteNames.onboarding);
  }
}
