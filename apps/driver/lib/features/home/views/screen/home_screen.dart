import 'package:easy_localization/easy_localization.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/services/fcm_service.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../auth/repository/auth_repository.dart';
import '../../../chat/provider/chat_provider.dart';
import '../../../dashboard/dashboard_tab.dart';
import '../../../notification/provider/notification_provider.dart';
import '../../../order_request/provider/order_request_provider.dart';
import '../../provider/home_provider.dart';
import '../widgets/home_map.dart';
import '../widgets/online_pill_toggle.dart';
import '../widgets/status_alerts.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen>
    with WidgetsBindingObserver {
  final _scaffoldKey = GlobalKey<ScaffoldState>();
  final _mapKey = GlobalKey<HomeMapState>();
  OrderRequestProvider? _orderProvider;
  HomeProvider? _home;
  bool _popupOpen = false;
  bool _resumedActiveOrder = false; // auto-resume only once per launch

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) => _init());
  }

  Future<void> _init() async {
    final home = context.read<HomeProvider>();
    _home = home;
    _orderProvider = context.read<OrderRequestProvider>();

    // New Pusher order → push the full-screen popup.
    home.onOrderRequest = (req) => _orderProvider?.showRequest(req);
    // Order cancelled (another driver took it / customer cancelled).
    home.onOrderCancelled = (id) => _orderProvider?.dismissIfMatches(id);

    _orderProvider!.addListener(_onOrderChanged);
    _wireFcm();

    // Populate the unread notification badge.
    context.read<NotificationProvider>().loadNotifications();

    // Load driver (starts location tracking if online), then check for an
    // ongoing order and resume straight into its live screen.
    await home.loadDriverData();
    await home.checkActiveOrder();
    if (!mounted) return;
    if (!_resumedActiveOrder && home.activeOrderRef != null) {
      _resumedActiveOrder = true;
      _resumeActiveOrder();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    // Returning to foreground while online → re-subscribe to Pusher.
    if (state == AppLifecycleState.resumed) {
      _home?.reconnectRealtime();
    }
  }

  /// App background/killed → FCM order notification tap → resolve & show popup
  /// if still valid, else inform the driver it expired.
  void _wireFcm() {
    try {
      // Background tap (app alive in background).
      FcmService.instance.onMessageOpenedApp(_handleFcmTap);
      // Terminated → tap. Prefer the message captured in main() (never missed
      // due to mount timing); fall back to querying the plugin directly.
      final captured = FcmService.initialMessage;
      if (captured != null) {
        FcmService.initialMessage = null; // consume once
        debugPrint('FCMTAP draining captured initialMessage');
        _handleFcmTap(captured);
      } else {
        FcmService.instance.getInitialMessage().then((m) {
          debugPrint('FCMTAP getInitialMessage: ${m?.data}');
          if (m != null) _handleFcmTap(m);
        }).catchError((Object e) {
          debugPrint('FCMTAP getInitialMessage error: $e');
          return null;
        });
      }
    } catch (e) {
      debugPrint('FCMTAP wireFcm error: $e');
    }

    // Keep the backend's FCM token current — see FcmService.onTokenRefresh.
    try {
      FcmService.instance.onTokenRefresh((token) {
        debugPrint('FCM token refreshed, pushing to backend');
        context.read<AuthRepository>().updateFcmToken(token);
      });
    } catch (e) {
      debugPrint('FCM onTokenRefresh wiring error: $e');
    }
  }

  Future<void> _handleFcmTap(RemoteMessage message) async {
    final data = message.data;
    debugPrint('FCMTAP handle data=$data');
    final type = data['type']?.toString() ?? '';

    if (type == 'chat') {
      await _openChatFromNotification(data);
      return;
    }

    if (type != 'order_request' && type != 'new_order') {
      debugPrint('FCMTAP ignored: type="$type"');
      return;
    }

    final orderId = int.tryParse(data['order_id']?.toString() ?? '');
    if (orderId == null) return;

    // Compute elapsed time since the request was sent (if provided).
    var elapsed = 0;
    final sentAt = int.tryParse(data['sent_at']?.toString() ?? '');
    if (sentAt != null) {
      elapsed = (DateTime.now().millisecondsSinceEpoch ~/ 1000) - sentAt;
      if (elapsed < 0) elapsed = 0;
    }

    // Prefer building the popup straight from the notification payload — a
    // freshly offered order has no driver_id yet, so the order-detail endpoint
    // would 404. Fall back to fetching only if the payload lacks the details.
    final hasPayload = data.containsKey('order_number') ||
        data.containsKey('pickup') ||
        data.containsKey('pickup_lat');
    debugPrint('FCMTAP orderId=$orderId hasPayload=$hasPayload elapsed=$elapsed');
    final req = hasPayload
        ? _orderProvider?.openFromData(data, elapsedSeconds: elapsed)
        : await _orderProvider?.openFromNotification(orderId,
            elapsedSeconds: elapsed);
    debugPrint('FCMTAP result req=${req?.orderId} expired=${_orderProvider?.lastExpired}');
    if (!mounted) return;
    if (req == null && (_orderProvider?.lastExpired ?? false)) {
      AppSnackbar.show(context, 'order.request_expired'.tr());
    }
  }

  /// Chat push tap (§4 of test/CHAT_API.md) → resolve the conversation from
  /// `order_id` and deep-link straight into the thread.
  Future<void> _openChatFromNotification(Map<String, dynamic> data) async {
    final orderId = int.tryParse(data['order_id']?.toString() ?? '');
    if (orderId == null) return;
    final chat = context.read<ChatProvider>();
    await chat.loadForOrder(orderId);
    if (!mounted) return;
    if (!chat.hasChat) {
      debugPrint('FCMTAP chat: no conversation for order=$orderId');
      return;
    }
    context.push(RouteNames.chat);
  }

  void _onOrderChanged() {
    final hasRequest = _orderProvider?.hasRequest ?? false;
    debugPrint('FCMTAP onOrderChanged hasRequest=$hasRequest popupOpen=$_popupOpen mounted=$mounted');
    if (hasRequest && !_popupOpen && mounted) {
      _popupOpen = true;
      context.push(RouteNames.orderRequest).then((_) => _popupOpen = false);
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _orderProvider?.removeListener(_onOrderChanged);
    super.dispose();
  }

  void _onToggle() async {
    final home = context.read<HomeProvider>();
    final ok = await home.toggleOnline(!home.isOnline);
    if (!ok && mounted && home.blockReasons.isNotEmpty) {
      _showBlockDialog(home.blockReasons);
    }
  }

  void _showBlockDialog(List<String> reasons) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('home.cannot_go_online'.tr()),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: reasons
              .map((r) => Padding(
                    padding: const EdgeInsets.symmetric(vertical: 4),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(Icons.cancel,
                            color: AppColors.danger, size: 18),
                        const SizedBox(width: 8),
                        Expanded(child: Text(r)),
                      ],
                    ),
                  ))
              .toList(),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text('common.ok'.tr()),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final home = context.watch<HomeProvider>();

    return Scaffold(
      key: _scaffoldKey,
      body: Stack(
        children: [
          // Map fills the screen (always rendered; falls back to a default
          // center until the driver's real position is available).
          Positioned.fill(
            child: HomeMap(key: _mapKey, position: home.currentPosition),
          ),

          // Top bar.
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                children: [
                  Row(
                    children: [
                      _circleButton(
                        Icons.menu,
                        () => DashboardTab.scaffoldKey.currentState
                            ?.openDrawer(),
                      ),
                      const SizedBox(width: 12),
                      OnlinePillToggle(
                        isOnline: home.isOnline,
                        loading: home.togglingOnline,
                        onToggle: _onToggle,
                      ),
                      const Spacer(),
                      _notificationButton(
                        () => context.push(RouteNames.notifications),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  StatusAlerts(
                    expiringDocs: home.expiringDocs,
                    dueAmount: home.dueAmount,
                    dueExceeded: home.dueExceeded,
                  ),
                ],
              ),
            ),
          ),

          // Active order banner.
          if (home.hasActiveOrder)
            Positioned(
              left: 16,
              right: 16,
              bottom: 220,
              child: _activeOrderBanner(),
            ),

          // Bottom-left: recenter location button (online only).
          if (home.isOnline)
            Positioned(
              left: 16,
              bottom: 130,
              child: _circleButton(
                Icons.my_location,
                () => _recenterMap(),
                iconColor: const Color(0xFF5B3DF6),
              ),
            ),
        ],
      ),
    );
  }

  void _recenterMap() {
    final home = context.read<HomeProvider>();
    if (home.currentPosition == null) {
      AppSnackbar.show(context, 'home.locating_you'.tr());
      return;
    }
    _mapKey.currentState?.recenter();
  }

  Widget _notificationButton(VoidCallback onTap) {
    final unread = context.watch<NotificationProvider>().unreadCount;
    return Stack(
      clipBehavior: Clip.none,
      children: [
        _circleButton(Icons.notifications_outlined, onTap),
        if (unread > 0)
          Positioned(
            right: -2,
            top: -2,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
              decoration: BoxDecoration(
                color: AppColors.danger,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: Colors.white, width: 1.5),
              ),
              constraints: const BoxConstraints(minWidth: 18, minHeight: 18),
              child: Text(
                unread > 99 ? '99+' : '$unread',
                textAlign: TextAlign.center,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
      ],
    );
  }

  Widget _circleButton(IconData icon, VoidCallback onTap, {Color? iconColor}) {
    return Material(
      color: Theme.of(context).cardColor,
      shape: const CircleBorder(),
      elevation: 2,
      shadowColor: Colors.black12,
      child: InkWell(
        onTap: onTap,
        customBorder: const CircleBorder(),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Icon(icon,
              color: iconColor ?? Theme.of(context).colorScheme.onSurface),
        ),
      ),
    );
  }

  void _resumeActiveOrder() {
    final ref = context.read<HomeProvider>().activeOrderRef;
    if (ref == null) return;
    context.push(
      ref.isParcel ? RouteNames.parcelOrder : RouteNames.rideOrder,
      extra: ref.id,
    );
  }

  Widget _activeOrderBanner() {
    return Material(
      color: AppColors.primary,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: _resumeActiveOrder,
        borderRadius: BorderRadius.circular(14),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              const Icon(Icons.directions_car, color: Colors.white),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  'order.active_tap_resume'.tr(),
                  style: const TextStyle(
                      color: Colors.white, fontWeight: FontWeight.w600),
                ),
              ),
              const Icon(Icons.chevron_right, color: Colors.white),
            ],
          ),
        ),
      ),
    );
  }
}
