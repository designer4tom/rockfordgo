// Pages are intentionally non-const so they rebuild on locale/theme change.
// ignore_for_file: prefer_const_constructors
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../earnings/views/screen/earnings_screen.dart';
import '../../../history/views/screen/history_screen.dart';
import '../../../home/provider/home_provider.dart';
import '../../../home/views/screen/home_screen.dart';
import '../../../home/views/widgets/home_drawer.dart';
import '../../../profile/provider/profile_provider.dart';
import '../../../profile/views/screen/profile_screen.dart';
import '../../dashboard_tab.dart';
import '../widgets/dashboard_bottom_nav.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  @override
  void initState() {
    super.initState();
    // React to tab changes from the bottom-nav AND from elsewhere (e.g. the
    // drawer's "Profile" item switching to the Profile tab).
    DashboardTab.index.addListener(_onTabChanged);
  }

  @override
  void dispose() {
    DashboardTab.index.removeListener(_onTabChanged);
    super.dispose();
  }

  void _onTabChanged() {
    if (!mounted) return;
    setState(() {});
    // The Profile tab lives inside an IndexedStack, so its initState only ever
    // runs once. Refetch /driver/profile every time the tab is opened so the
    // page always shows fresh data.
    if (DashboardTab.index.value == DashboardTab.profile) {
      context.read<ProfileProvider>().loadProfile();
    }
  }

  @override
  Widget build(BuildContext context) {
    // Built non-const each build so the tabs rebuild on locale/theme change.
    final pages = <Widget>[
      HomeScreen(),
      EarningsScreen(),
      HistoryScreen(),
      ProfileScreen(),
    ];
    return Scaffold(
      key: DashboardTab.scaffoldKey,
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      extendBody: true,
      // Drawer lives on the outermost Scaffold so it slides over the whole
      // screen — top bar and bottom nav stay behind it (Figma).
      drawer: HomeDrawer(driver: context.watch<HomeProvider>().driver),
      body: IndexedStack(index: DashboardTab.index.value, children: pages),
      bottomNavigationBar: DashboardBottomNav(
        currentIndex: DashboardTab.index.value,
        onChanged: (i) => DashboardTab.index.value = i,
      ),
    );
  }
}
