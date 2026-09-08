import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../features/auth/provider/auth_provider.dart';
import '../../features/home/views/widgets/home_drawer.dart';
import '../../features/notification/provider/notification_provider.dart';
import '../../features/wallet/provider/wallet_provider.dart';
import '../services/fcm_service.dart';
import '../widgets/app_bottom_nav.dart';
import '../widgets/app_top_bar.dart';

/// Persistent shell hosting the four main tabs. The drawer, top bar and bottom
/// bar are mounted here once and stay fixed; tapping a tab only swaps the inner
/// branch ([navigationShell]) via an IndexedStack — the chrome never moves.
class MainShell extends StatefulWidget {
  final StatefulNavigationShell navigationShell;

  const MainShell({super.key, required this.navigationShell});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      final notifications = context.read<NotificationProvider>();
      // Fetch the current unread count so the bell dot is correct on launch.
      notifications.loadNotifications(refresh: true);
      // Real-time: bump the unread count when a foreground push arrives.
      FcmService().onForegroundMessage = notifications.onForegroundMessage;

      // Keep the cached user balance in sync with the live wallet balance so
      // profile/drawer/ride payment all reflect an add-money automatically.
      context.read<WalletProvider>().onBalanceChanged =
          context.read<AuthProvider>().updateWalletBalance;
    });
  }

  void _onTap(int index) {
    // Tapping the active tab again resets it to its initial route.
    widget.navigationShell.goBranch(
      index,
      initialLocation: index == widget.navigationShell.currentIndex,
    );
  }

  @override
  Widget build(BuildContext context) {
    // The drawer (and its hamburger button) only belong on the dashboard/home
    // tab. The other three tabs keep the same top bar but without the menu.
    final isHome = widget.navigationShell.currentIndex == 0;
    // Back/exit always returns to the home tab first; only from home does the
    // system back actually close the app.
    return PopScope(
      canPop: isHome,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        widget.navigationShell.goBranch(0);
      },
      child: Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        drawer: isHome ? const HomeDrawer() : null,
        appBar: isHome ? const AppTopBar(showMenu: true) : null,
        body: widget.navigationShell,
        bottomNavigationBar: AppBottomNav(
          currentIndex: widget.navigationShell.currentIndex,
          onTap: _onTap,
        ),
      ),
    );
  }
}
