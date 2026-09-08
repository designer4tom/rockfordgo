import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../constants/app_colors.dart';

/// Shared bottom navigation for the main shell (Home / Bookings / Wallet /
/// Profile). It is mounted once in [MainShell]; tapping a tab only swaps the
/// inner branch — the bar itself never moves or rebuilds its position.
class AppBottomNav extends StatelessWidget {
  final int currentIndex;
  final ValueChanged<int> onTap;

  const AppBottomNav({
    super.key,
    required this.currentIndex,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return BottomNavigationBar(
      currentIndex: currentIndex,
      onTap: onTap,
      type: BottomNavigationBarType.fixed,
      backgroundColor: theme.cardColor,
      selectedItemColor: AppColors.primary,
      unselectedItemColor: AppColors.textSecondary,
      items: [
        BottomNavigationBarItem(
          icon: Icon(currentIndex == 0 ? Icons.home : Icons.home_outlined),
          label: 'home.nav_home'.tr(),
        ),
        BottomNavigationBarItem(
          icon: Icon(currentIndex == 1
              ? Icons.receipt_long
              : Icons.receipt_long_outlined),
          label: 'home.nav_bookings'.tr(),
        ),
        BottomNavigationBarItem(
          icon: Icon(currentIndex == 2
              ? Icons.account_balance_wallet
              : Icons.account_balance_wallet_outlined),
          label: 'home.wallet'.tr(),
        ),
        BottomNavigationBarItem(
          icon: Icon(currentIndex == 3 ? Icons.person : Icons.person_outline),
          label: 'profile.title'.tr(),
        ),
      ],
    );
  }
}
