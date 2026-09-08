import 'package:go_router/go_router.dart';

import '../../features/auth/views/screen/otp_verify_screen.dart';
import '../../features/chat/views/screen/chat_screen.dart';
import '../../features/auth/views/screen/pending_approval_screen.dart';
import '../../features/auth/views/screen/phone_entry_screen.dart';
import '../../features/auth/views/screen/registration_screen.dart';
import '../../features/complaint/views/screen/complaint_list_screen.dart';
import '../../features/complaint/views/screen/create_complaint_screen.dart';
import '../../features/documents/views/screen/documents_screen.dart';
import '../../features/dashboard/views/screen/dashboard_screen.dart';
import '../../features/earnings/views/screen/earnings_screen.dart';
import '../../features/history/views/screen/history_screen.dart';
import '../../features/history/views/screen/order_detail_screen.dart';
import '../../features/notification/views/screen/notification_screen.dart';
import '../../features/onboarding/views/screen/onboarding_screen.dart';
import '../../features/order_request/views/screen/order_request_screen.dart';
import '../../features/parcel_order/views/screen/parcel_complete_screen.dart';
import '../../features/parcel_order/views/screen/parcel_order_screen.dart';
import '../../features/performance/views/screen/performance_screen.dart';
import '../../features/profile/views/screen/edit_profile_screen.dart';
import '../../features/profile/views/screen/emergency_contact_screen.dart';
import '../../features/profile/views/screen/profile_screen.dart';
import '../../features/profile/views/screen/settings_screen.dart';
import '../../features/profile/views/screen/withdrawal_account_screen.dart';
import '../../features/page/views/screen/page_screen.dart';
import '../../features/recharge/views/screen/recharge_screen.dart';
import '../../features/recharge/views/screen/recharge_webview_screen.dart';
import '../../features/ride_order/views/screen/ride_complete_screen.dart';
import '../../features/ride_order/views/screen/ride_order_screen.dart';
import '../../features/splash/views/screen/splash_screen.dart';
import '../../features/wallet/views/screen/wallet_screen.dart';
import '../../features/wallet/views/screen/withdrawal_history_screen.dart';
import '../../features/wallet/views/screen/withdrawal_screen.dart';
import '../services/auth_session.dart';
import 'route_names.dart';

/// Central app router with all feature routes.
class AppRouter {
  static int _asOrderId(Object? extra) =>
      extra is int ? extra : int.tryParse(extra?.toString() ?? '') ?? 0;

  // Screens that are part of the pre-login / auth flow.
  static const _authFlow = {
    RouteNames.splash,
    RouteNames.onboarding,
    RouteNames.phoneEntry,
    RouteNames.otpVerify,
  };

  /// Guard: keeps the driver on the correct screen for their account state,
  /// including after an app restart. Splash runs its own bootstrap untouched.
  static String? _redirect(_, GoRouterState state) {
    final s = AuthSession.instance;
    final loc = state.matchedLocation;

    if (loc == RouteNames.splash) return null;

    if (!s.isLoggedIn) {
      return _authFlow.contains(loc) ? null : RouteNames.phoneEntry;
    }
    if (s.isBlocked) {
      return _authFlow.contains(loc) ? null : RouteNames.pendingApproval;
    }
    if (s.isRejected) {
      return loc == RouteNames.registration ? null : RouteNames.registration;
    }
    if (s.isApproved) {
      final stuckInAuth = _authFlow.contains(loc) ||
          loc == RouteNames.registration ||
          loc == RouteNames.pendingApproval;
      return stuckInAuth ? RouteNames.home : null;
    }
    // pending: finish registration first, then wait for approval.
    if (!s.registrationCompleted) {
      return loc == RouteNames.registration ? null : RouteNames.registration;
    }
    return loc == RouteNames.pendingApproval
        ? null
        : RouteNames.pendingApproval;
  }

  static final GoRouter router = GoRouter(
    initialLocation: RouteNames.splash,
    redirect: _redirect,
    routes: [
      GoRoute(
        path: RouteNames.splash,
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: RouteNames.onboarding,
        builder: (context, state) => const OnboardingScreen(),
      ),
      GoRoute(
        path: RouteNames.phoneEntry,
        builder: (context, state) => const PhoneEntryScreen(),
      ),
      GoRoute(
        path: RouteNames.otpVerify,
        builder: (context, state) => const OtpVerifyScreen(),
      ),
      GoRoute(
        path: RouteNames.registration,
        builder: (context, state) => const RegistrationScreen(),
      ),
      GoRoute(
        path: RouteNames.pendingApproval,
        builder: (context, state) => const PendingApprovalScreen(),
      ),
      GoRoute(
        path: RouteNames.home,
        // Non-const so the shell + its tabs rebuild on locale/theme change.
        // ignore: prefer_const_constructors
        builder: (context, state) => DashboardScreen(),
      ),
      // Order request popup.
      GoRoute(
        path: RouteNames.orderRequest,
        pageBuilder: (context, state) => const NoTransitionPage(
          child: OrderRequestScreen(),
        ),
      ),
      // Ride execution.
      GoRoute(
        path: RouteNames.rideOrder,
        builder: (context, state) =>
            RideOrderScreen(orderId: _asOrderId(state.extra)),
      ),
      GoRoute(
        path: RouteNames.rideComplete,
        builder: (context, state) =>
            RideCompleteScreen(orderId: _asOrderId(state.extra)),
      ),
      // Parcel execution.
      GoRoute(
        path: RouteNames.parcelOrder,
        builder: (context, state) =>
            ParcelOrderScreen(orderId: _asOrderId(state.extra)),
      ),
      GoRoute(
        path: RouteNames.parcelComplete,
        builder: (context, state) =>
            ParcelCompleteScreen(orderId: _asOrderId(state.extra)),
      ),
      // Earnings & wallet.
      GoRoute(
        path: RouteNames.earnings,
        builder: (context, state) => const EarningsScreen(),
      ),
      GoRoute(
        path: RouteNames.wallet,
        builder: (context, state) => const WalletScreen(),
      ),
      GoRoute(
        path: RouteNames.withdrawal,
        builder: (context, state) => const WithdrawalScreen(),
      ),
      GoRoute(
        path: '/withdrawal-history',
        builder: (context, state) => const WithdrawalHistoryScreen(),
      ),
      GoRoute(
        path: RouteNames.recharge,
        builder: (context, state) => const RechargeScreen(),
      ),
      GoRoute(
        path: RouteNames.rechargeWebview,
        builder: (context, state) =>
            RechargeWebViewScreen(paymentUrl: state.extra as String),
      ),
      // Documents / performance / history.
      GoRoute(
        path: RouteNames.documents,
        builder: (context, state) => const DocumentsScreen(),
      ),
      GoRoute(
        path: RouteNames.performance,
        builder: (context, state) => const PerformanceScreen(),
      ),
      GoRoute(
        path: RouteNames.history,
        builder: (context, state) => const HistoryScreen(),
      ),
      GoRoute(
        path: RouteNames.orderDetail,
        builder: (context, state) =>
            OrderDetailScreen(orderId: _asOrderId(state.extra)),
      ),
      // Profile & engagement.
      GoRoute(
        path: RouteNames.notifications,
        builder: (context, state) => const NotificationScreen(),
      ),
      GoRoute(
        path: RouteNames.chat,
        builder: (context, state) => const ChatScreen(),
      ),
      GoRoute(
        path: RouteNames.profile,
        builder: (context, state) => const ProfileScreen(),
      ),
      GoRoute(
        path: '/edit-profile',
        builder: (context, state) => const EditProfileScreen(),
      ),
      GoRoute(
        path: '/emergency-contact',
        builder: (context, state) => const EmergencyContactScreen(),
      ),
      GoRoute(
        path: '/withdrawal-account',
        builder: (context, state) => const WithdrawalAccountScreen(),
      ),
      GoRoute(
        path: RouteNames.settings,
        builder: (context, state) => const SettingsScreen(),
      ),
      GoRoute(
        path: '/complaints',
        builder: (context, state) => const ComplaintListScreen(),
      ),
      GoRoute(
        path: '/create-complaint',
        builder: (context, state) => const CreateComplaintScreen(),
      ),
      GoRoute(
        path: '/page/:slug',
        builder: (context, state) =>
            PageScreen(slug: state.pathParameters['slug']!),
      ),
    ],
  );
}
