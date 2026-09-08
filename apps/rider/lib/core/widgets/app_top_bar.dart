import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../features/notification/provider/notification_provider.dart';
import '../constants/app_colors.dart';
import '../routing/route_names.dart';

/// Shared, fixed top navigation used across the main tab pages (Home, Wallet,
/// Profile, …). It never scrolls away because it is mounted as the
/// `Scaffold.appBar`. The leading button opens the drawer when the host
/// Scaffold has one, otherwise it navigates back (or home).
class AppTopBar extends StatelessWidget implements PreferredSizeWidget {
  /// Show the small unread dot on the notification bell.
  final bool showNotificationDot;

  /// Show the leading menu (hamburger) button that opens the drawer. Only the
  /// dashboard/home tab shows the drawer, so the other tabs hide this button.
  final bool showMenu;

  const AppTopBar({
    super.key,
    this.showNotificationDot = true,
    this.showMenu = true,
  });

  @override
  Size get preferredSize => const Size.fromHeight(64);

  void _onMenu(BuildContext context) {
    final scaffold = Scaffold.maybeOf(context);
    if (scaffold?.hasDrawer ?? false) {
      scaffold!.openDrawer();
    } else if (context.canPop()) {
      context.pop();
    } else {
      context.go(RouteNames.home);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    // Only show the unread dot when there are unread notifications. Watching
    // the provider keeps it live: it appears when a message arrives and clears
    // once everything is marked read.
    final hasUnread =
        context.watch<NotificationProvider>().unreadCount > 0;
    return AppBar(
      toolbarHeight: 64,
      backgroundColor: theme.scaffoldBackgroundColor,
      surfaceTintColor: theme.scaffoldBackgroundColor,
      elevation: 0,
      scrolledUnderElevation: 0,
      automaticallyImplyLeading: false,
      titleSpacing: 0,
      title: Padding(
        padding: const EdgeInsetsDirectional.only(start: 16, end: 16),
        child: Row(
          children: [

            if (showMenu)
              IconButton(onPressed: (){
                _onMenu(context);
              }, icon: Icon(Icons.menu)),

            // _circleButton(theme,
            //     icon: Icons.menu, onTap: () => _onMenu(context)),
            // const SizedBox(width: 10),
            // "R" logo tile + ReadyRide wordmark.
            // Container(
            //   width: 38,
            //   height: 38,
            //   alignment: Alignment.center,
            //   decoration: BoxDecoration(
            //     color: AppColors.primary,
            //     borderRadius: BorderRadius.circular(10),
            //   ),
            //   child: const Text('R',
            //       style: TextStyle(
            //           color: Colors.white,
            //           fontWeight: FontWeight.bold,
            //           fontSize: 20)),
            // ),
            const SizedBox(width: 8),
            RichText(
              text: TextSpan(
                style:
                    const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                children: [
                  TextSpan(
                      text: 'Ready',
                      style: TextStyle(color: theme.colorScheme.onSurface)),
                  const TextSpan(
                      text: 'Ride',
                      style: TextStyle(color: AppColors.primary)),
                ],
              ),
            ),
            const Spacer(),
            Stack(
              clipBehavior: Clip.none,
              children: [
                IconButton(
                  onPressed: () {
                    context.push(RouteNames.notifications);
                  },
                  icon: const Icon(Icons.notifications_outlined),
                ),
                if (showNotificationDot && hasUnread)
                  PositionedDirectional(
                    top: 10,
                    end: 10,
                    child: Container(
                      width: 9,
                      height: 9,
                      decoration: BoxDecoration(
                        color: AppColors.primary,
                        shape: BoxShape.circle,
                        border: Border.all(
                            color: theme.scaffoldBackgroundColor, width: 1.5),
                      ),
                    ),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _circleButton(ThemeData theme,
      {required IconData icon, required VoidCallback onTap}) {
    return Material(
      color: theme.cardColor,
      shape: const CircleBorder(),
      elevation: 1,
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(9),
          child: Icon(icon, color: theme.colorScheme.onSurface),
        ),
      ),
    );
  }
}
