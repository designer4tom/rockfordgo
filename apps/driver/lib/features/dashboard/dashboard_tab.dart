import 'package:flutter/material.dart';

/// Shared selector for the dashboard's bottom-nav tab.
///
/// Lets screens outside the bottom-nav (e.g. the drawer's "Profile" item)
/// switch the active dashboard tab instead of pushing a separate route, so the
/// user lands on the real dashboard Profile section.
class DashboardTab {
  DashboardTab._();

  static const int home = 0;
  static const int earnings = 1;
  static const int history = 2;
  static const int profile = 3;

  static final ValueNotifier<int> index = ValueNotifier<int>(home);

  /// Key for the dashboard's outermost [Scaffold]. The drawer lives there so it
  /// overlays the full screen (top bar + bottom nav) instead of being trapped
  /// inside the Home tab. Screens open it via [scaffoldKey].currentState.
  static final GlobalKey<ScaffoldState> scaffoldKey =
      GlobalKey<ScaffoldState>();
}
