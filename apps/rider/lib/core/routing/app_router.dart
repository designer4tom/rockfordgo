import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../features/auth/views/screen/complete_profile_screen.dart';
import '../../features/auth/views/screen/login_otp_screen.dart';
import '../../features/auth/views/screen/otp_verify_screen.dart';
import '../../features/auth/views/screen/phone_entry_screen.dart';
import '../../features/auth/views/screen/register_otp_screen.dart';
import '../../features/auth/views/screen/registration_success_screen.dart';
import '../../features/chat/views/screen/chat_screen.dart';
import '../../features/home/views/screen/home_screen.dart';
import '../../features/home/views/screen/map_picker_screen.dart';
import '../../features/location/views/screen/location_search_screen.dart';
import '../../features/home/views/screen/set_destination_screen.dart';
import '../../features/onboarding/views/screen/onboarding_screen.dart';
import '../../features/parcel/views/screen/parcel_cod_screen.dart';
import '../../features/parcel/views/screen/parcel_confirm_screen.dart';
import '../../features/parcel/views/screen/parcel_details_screen.dart';
import '../../features/parcel/views/screen/parcel_receiver_screen.dart';
import '../../features/parcel/views/screen/parcel_sender_screen.dart';
import '../../features/service/views/screen/our_services_screen.dart';
import '../../features/ride/views/screen/booking_confirm_screen.dart';
import '../../features/ride/views/screen/searching_driver_screen.dart';
import '../../features/ride/views/screen/vehicle_select_screen.dart';
import '../../features/complaint/views/screen/complaint_list_screen.dart';
import '../../features/complaint/views/screen/create_complaint_screen.dart';
import '../../features/favourite/views/screen/favourite_screen.dart';
import '../../features/history/views/screen/history_screen.dart';
import '../../features/history/views/screen/invoice_screen.dart';
import '../../features/history/views/screen/order_detail_screen.dart';
import '../../features/notification/views/screen/notification_screen.dart';
import '../../features/profile/views/screen/edit_profile_screen.dart';
import '../../features/performance/views/screen/performance_screen.dart';
import '../../features/profile/views/screen/profile_screen.dart';
import '../../features/profile/views/screen/settings_screen.dart';
import '../../features/referral/views/screen/referral_screen.dart';
import '../../features/splash/views/screen/splash_screen.dart';
import '../../features/tracking/views/screen/parcel_tracking_screen.dart';
import '../../features/tracking/views/screen/rating_screen.dart';
import '../../features/tracking/views/screen/ride_tracking_screen.dart';
import '../../features/tracking/views/screen/trip_complete_screen.dart';
import '../../features/tracking/views/screen/trip_payment_screen.dart';
import '../../features/wallet/views/screen/topup_screen.dart';
import '../../features/wallet/views/screen/wallet_screen.dart';
import '../../features/coupon/views/screen/offers_screen.dart';
import '../../features/help/views/screen/help_center_screen.dart';
import '../../features/help/views/screen/page_screen.dart';
import '../../features/help/views/screen/safety_screen.dart';
import '../../features/wallet/views/screen/due_history_screen.dart';
import '../../features/wallet/views/screen/transactions_screen.dart';
import '../../features/wallet/views/screen/wallet_webview_screen.dart';
import '../../features/wallet/views/screen/withdrawal_screen.dart';
import '../storage/secure_storage.dart';
import 'main_shell.dart';
import 'route_names.dart';

class AppRouter {
  AppRouter._();

  /// Navigator key so non-widget code (e.g. Dio interceptors, FCM handlers)
  /// can trigger navigation — used for the 401 → login redirect.
  static final GlobalKey<NavigatorState> navigatorKey =
      GlobalKey<NavigatorState>(debugLabel: 'root');

  /// Single GoRouter instance for the whole app lifetime.
  ///
  /// MUST be created once — if a new GoRouter is built on every
  /// MaterialApp rebuild (e.g. theme/locale change) it re-registers the
  /// shared [navigatorKey], causing "Duplicate GlobalKey detected" and
  /// "element._lifecycleState == inactive" crashes.
  static GoRouter? _router;

  static GoRouter router() {
    return _router ??= GoRouter(
      navigatorKey: navigatorKey,
      initialLocation: RouteNames.splash,
      redirect: (context, state) async {
        final storage = context.read<SecureStorage>();
        final hasToken = await storage.hasToken();
        final location = state.matchedLocation;
        final onAuthScreen = location == RouteNames.phoneEntry ||
            location == RouteNames.otpVerify ||
            location == RouteNames.registerOtp ||
            location == RouteNames.loginOtp;

        // Logged in but on an auth screen → send to home.
        if (hasToken && onAuthScreen) return RouteNames.home;
        return null;
      },
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
          path: RouteNames.registerOtp,
          builder: (context, state) => const RegisterOtpScreen(),
        ),
        GoRoute(
          path: RouteNames.loginOtp,
          builder: (context, state) => const LoginOtpScreen(),
        ),
        GoRoute(
          path: RouteNames.completeProfile,
          builder: (context, state) => const CompleteProfileScreen(),
        ),
        GoRoute(
          path: RouteNames.registrationSuccess,
          builder: (context, state) => const RegistrationSuccessScreen(),
        ),
        // Main tabs live in a persistent shell: the top bar, drawer and
        // bottom bar stay fixed while only the inner branch swaps.
        StatefulShellRoute.indexedStack(
          builder: (context, state, navigationShell) =>
              MainShell(navigationShell: navigationShell),
          branches: [
            StatefulShellBranch(routes: [
              GoRoute(
                path: RouteNames.home,
                builder: (context, state) => const HomeScreen(),
              ),
            ]),
            StatefulShellBranch(routes: [
              GoRoute(
                path: RouteNames.history,
                builder: (context, state) => const HistoryScreen(),
              ),
            ]),
            StatefulShellBranch(routes: [
              GoRoute(
                path: RouteNames.wallet,
                builder: (context, state) => const WalletScreen(),
              ),
            ]),
            StatefulShellBranch(routes: [
              GoRoute(
                path: RouteNames.profile,
                builder: (context, state) => const ProfileScreen(),
              ),
            ]),
          ],
        ),
        GoRoute(
          path: RouteNames.setDestination,
          builder: (context, state) => const SetDestinationScreen(),
        ),
        GoRoute(
          path: '/map-picker',
          builder: (context, state) => const MapPickerScreen(),
        ),
        GoRoute(
          path: '/location-search',
          builder: (context, state) =>
              LocationSearchScreen(title: (state.extra as String?) ?? ''),
        ),
        GoRoute(
          path: RouteNames.ourServices,
          builder: (context, state) => const OurServicesScreen(),
        ),
        GoRoute(
          path: RouteNames.vehicleSelect,
          builder: (context, state) => const VehicleSelectScreen(),
        ),
        GoRoute(
          path: RouteNames.bookingConfirm,
          builder: (context, state) => const BookingConfirmScreen(),
        ),
        GoRoute(
          path: '/searching-driver',
          builder: (context, state) => const SearchingDriverScreen(),
        ),

        // Parcel flow (multi-step)
        GoRoute(
          path: RouteNames.parcelBooking,
          builder: (context, state) => const ParcelSenderScreen(),
        ),
        GoRoute(
          path: '/parcel-receiver',
          builder: (context, state) => const ParcelReceiverScreen(),
        ),
        GoRoute(
          path: '/parcel-details',
          builder: (context, state) => const ParcelDetailsScreen(),
        ),
        GoRoute(
          path: '/parcel-cod',
          builder: (context, state) => const ParcelCodScreen(),
        ),
        GoRoute(
          path: '/parcel-confirm',
          builder: (context, state) => const ParcelConfirmScreen(),
        ),

        // Tracking
        GoRoute(
          path: RouteNames.rideTracking,
          builder: (context, state) =>
              RideTrackingScreen(orderId: state.extra as int),
        ),
        GoRoute(
          path: RouteNames.parcelTracking,
          builder: (context, state) =>
              ParcelTrackingScreen(orderId: state.extra as int),
        ),
        GoRoute(
          path: '/trip-complete',
          builder: (context, state) =>
              TripCompleteScreen(orderId: state.extra as int),
        ),
        GoRoute(
          path: '/trip-payment',
          builder: (context, state) =>
              TripPaymentScreen(orderId: state.extra as int),
        ),
        GoRoute(
          path: '/rating',
          builder: (context, state) =>
              RatingScreen(orderId: state.extra as int),
        ),
        // Caller must resolve the conversation first via
        // ChatProvider.openForOrder(orderId) — this route just opens it.
        GoRoute(
          path: RouteNames.chat,
          builder: (context, state) => const ChatScreen(),
        ),

        // Wallet (the wallet tab lives in the shell above; these are full
        // screens pushed over it)
        GoRoute(
          path: '/topup',
          builder: (context, state) => const TopupScreen(),
        ),
        GoRoute(
          path: '/wallet-webview',
          builder: (context, state) =>
              WalletWebViewScreen(paymentUrl: state.extra as String),
        ),
        GoRoute(
          path: RouteNames.withdrawal,
          builder: (context, state) => const WithdrawalScreen(),
        ),
        GoRoute(
          path: RouteNames.transactions,
          builder: (context, state) => const TransactionsScreen(),
        ),
        GoRoute(
          path: RouteNames.dueHistory,
          builder: (context, state) => const DueHistoryScreen(),
        ),

        // History (the bookings tab lives in the shell above)
        GoRoute(
          path: RouteNames.orderDetail,
          builder: (context, state) =>
              OrderDetailScreen(orderId: state.extra as int),
        ),
        GoRoute(
          path: '/invoice',
          builder: (context, state) =>
              InvoiceScreen(orderId: state.extra as int),
        ),

        // Notifications, Profile, Settings
        GoRoute(
          path: RouteNames.notifications,
          builder: (context, state) => const NotificationScreen(),
        ),
        GoRoute(
          path: '/edit-profile',
          builder: (context, state) => const EditProfileScreen(),
        ),
        GoRoute(
          path: RouteNames.settings,
          builder: (context, state) => const SettingsScreen(),
        ),

        // Favourites, Referral, Complaints
        GoRoute(
          path: RouteNames.favourites,
          builder: (context, state) => const FavouriteScreen(),
        ),
        GoRoute(
          path: RouteNames.referral,
          builder: (context, state) => const ReferralScreen(),
        ),
        GoRoute(
          path: '/complaints',
          builder: (context, state) => const ComplaintListScreen(),
        ),
        GoRoute(
          path: RouteNames.offers,
          builder: (context, state) => const OffersScreen(),
        ),
        GoRoute(
          path: RouteNames.helpCenter,
          builder: (context, state) => const HelpCenterScreen(),
        ),
        GoRoute(
          path: RouteNames.safety,
          builder: (context, state) => const SafetyScreen(),
        ),
        GoRoute(
          path: RouteNames.performance,
          builder: (context, state) => const PerformanceScreen(),
        ),
        GoRoute(
          path: '/page/:slug',
          builder: (context, state) =>
              PageScreen(slug: state.pathParameters['slug']!),
        ),
        GoRoute(
          path: '/create-complaint',
          builder: (context, state) =>
              CreateComplaintScreen(orderId: state.extra as int?),
        ),
      ],
    );
  }
}
