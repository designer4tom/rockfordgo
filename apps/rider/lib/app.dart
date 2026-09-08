import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'core/constants/app_constants.dart';
import 'features/settings/provider/theme_provider.dart';
import 'core/network/dio_client.dart';
import 'core/network/pusher_service.dart';
import 'core/routing/app_router.dart';
import 'core/services/config_service.dart';
import 'core/storage/secure_storage.dart';
import 'core/theme/app_theme.dart';
import 'features/auth/provider/auth_provider.dart';
import 'features/auth/repository/auth_repository.dart';
import 'features/auth/repository/auth_repository_impl.dart';
import 'features/home/provider/home_provider.dart';
import 'features/home/repository/home_repository.dart';
import 'features/home/repository/home_repository_impl.dart';
import 'features/location/provider/location_provider.dart';
import 'features/location/repository/location_repository.dart';
import 'features/location/repository/location_repository_impl.dart';
import 'features/parcel/provider/parcel_provider.dart';
import 'features/parcel/repository/parcel_repository.dart';
import 'features/parcel/repository/parcel_repository_impl.dart';
import 'features/ride/provider/ride_provider.dart';
import 'features/ride/repository/ride_repository.dart';
import 'features/ride/repository/ride_repository_impl.dart';
import 'features/tracking/provider/tracking_provider.dart';
import 'features/tracking/repository/tracking_repository.dart';
import 'features/tracking/repository/tracking_repository_impl.dart';
import 'features/service/provider/service_provider.dart';
import 'features/service/repository/service_repository.dart';
import 'features/service/repository/service_repository_impl.dart';
import 'features/wallet/provider/wallet_provider.dart';
import 'features/wallet/repository/wallet_repository.dart';
import 'features/wallet/repository/wallet_repository_impl.dart';
import 'features/history/provider/history_provider.dart';
import 'features/history/repository/history_repository.dart';
import 'features/history/repository/history_repository_impl.dart';
import 'features/performance/provider/performance_provider.dart';
import 'features/notification/provider/notification_provider.dart';
import 'features/notification/repository/notification_repository.dart';
import 'features/notification/repository/notification_repository_impl.dart';
import 'features/profile/provider/profile_provider.dart';
import 'features/profile/repository/profile_repository.dart';
import 'features/profile/repository/profile_repository_impl.dart';
import 'features/favourite/provider/favourite_provider.dart';
import 'features/favourite/repository/favourite_repository.dart';
import 'features/favourite/repository/favourite_repository_impl.dart';
import 'features/coupon/provider/coupon_provider.dart';
import 'features/coupon/repository/coupon_repository.dart';
import 'features/coupon/repository/coupon_repository_impl.dart';
import 'features/help/provider/help_provider.dart';
import 'features/help/repository/help_repository.dart';
import 'features/help/repository/help_repository_impl.dart';
import 'features/referral/provider/referral_provider.dart';
import 'features/referral/repository/referral_repository.dart';
import 'features/referral/repository/referral_repository_impl.dart';
import 'features/complaint/provider/complaint_provider.dart';
import 'features/complaint/repository/complaint_repository.dart';
import 'features/complaint/repository/complaint_repository_impl.dart';
import 'features/banner/provider/banner_provider.dart';
import 'features/banner/repository/banner_repository.dart';
import 'features/banner/repository/banner_repository_impl.dart';
import 'features/chat/provider/chat_provider.dart';
import 'features/chat/repository/chat_repository.dart';
import 'features/chat/repository/chat_repository_impl.dart';

class MyApp extends StatelessWidget {
  final SharedPreferences prefs;

  const MyApp({super.key, required this.prefs});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        // Core
        Provider<SharedPreferences>.value(value: prefs),
        ChangeNotifierProvider<ThemeProvider>(
          create: (ctx) => ThemeProvider(ctx.read<SharedPreferences>()),
        ),
        Provider<SecureStorage>(create: (_) => SecureStorage()),
        Provider<DioClient>(
          create: (ctx) => DioClient(ctx.read<SecureStorage>()),
        ),
        ChangeNotifierProvider<ConfigService>(
          create: (ctx) => ConfigService(ctx.read<DioClient>().dio),
        ),
        Provider<PusherService>(
          lazy: false,
          create: (ctx) => PusherService(
            ctx.read<SecureStorage>(),
            ctx.read<DioClient>().dio,
          ),
        ),

        // Auth
        Provider<AuthRepository>(
          create: (ctx) => AuthRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<AuthProvider>(
          create: (ctx) => AuthProvider(
            ctx.read<AuthRepository>(),
            ctx.read<SecureStorage>(),
          ),
        ),

        // Location
        Provider<LocationRepository>(
          create: (ctx) => LocationRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<LocationProvider>(
          create: (ctx) => LocationProvider(ctx.read<LocationRepository>()),
        ),

        // Service
        Provider<ServiceRepository>(
          create: (ctx) => ServiceRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<ServiceProvider>(
          create: (ctx) => ServiceProvider(ctx.read<ServiceRepository>()),
        ),

        // Home
        Provider<HomeRepository>(
          create: (ctx) => HomeRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<HomeProvider>(
          create: (ctx) => HomeProvider(
            ctx.read<HomeRepository>(),
            ctx.read<LocationRepository>(),
          ),
        ),

        // Ride
        Provider<RideRepository>(
          create: (ctx) => RideRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<RideProvider>(
          create: (ctx) => RideProvider(
            ctx.read<RideRepository>(),
            ctx.read<PusherService>(),
          ),
        ),

        // Parcel
        Provider<ParcelRepository>(
          create: (ctx) => ParcelRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<ParcelProvider>(
          create: (ctx) => ParcelProvider(ctx.read<ParcelRepository>()),
        ),

        // Tracking
        Provider<TrackingRepository>(
          create: (ctx) => TrackingRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<TrackingProvider>(
          create: (ctx) => TrackingProvider(
            ctx.read<TrackingRepository>(),
            ctx.read<PusherService>(),
          ),
        ),

        // Wallet
        Provider<WalletRepository>(
          create: (ctx) => WalletRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<WalletProvider>(
          create: (ctx) => WalletProvider(ctx.read<WalletRepository>()),
        ),

        // History
        Provider<HistoryRepository>(
          create: (ctx) => HistoryRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<HistoryProvider>(
          create: (ctx) => HistoryProvider(ctx.read<HistoryRepository>()),
        ),

        // Performance (reuses HistoryRepository — no dedicated endpoint)
        ChangeNotifierProvider<PerformanceProvider>(
          create: (ctx) => PerformanceProvider(ctx.read<HistoryRepository>()),
        ),

        // Notification
        Provider<NotificationRepository>(
          create: (ctx) => NotificationRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<NotificationProvider>(
          create: (ctx) =>
              NotificationProvider(ctx.read<NotificationRepository>()),
        ),

        // Profile
        Provider<ProfileRepository>(
          create: (ctx) => ProfileRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<ProfileProvider>(
          create: (ctx) => ProfileProvider(ctx.read<ProfileRepository>()),
        ),

        // Favourite
        Provider<FavouriteRepository>(
          create: (ctx) => FavouriteRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<FavouriteProvider>(
          create: (ctx) => FavouriteProvider(ctx.read<FavouriteRepository>()),
        ),

        // Referral
        Provider<ReferralRepository>(
          create: (ctx) => ReferralRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<ReferralProvider>(
          create: (ctx) => ReferralProvider(ctx.read<ReferralRepository>()),
        ),

        // Coupons / Offers
        Provider<CouponRepository>(
          create: (ctx) => CouponRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<CouponProvider>(
          create: (ctx) => CouponProvider(ctx.read<CouponRepository>()),
        ),

        // Help / Safety
        Provider<HelpRepository>(
          create: (ctx) => HelpRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<HelpProvider>(
          create: (ctx) => HelpProvider(ctx.read<HelpRepository>()),
        ),

        // Complaint
        Provider<ComplaintRepository>(
          create: (ctx) => ComplaintRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<ComplaintProvider>(
          create: (ctx) => ComplaintProvider(ctx.read<ComplaintRepository>()),
        ),

        // Banner
        Provider<BannerRepository>(
          create: (ctx) => BannerRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<BannerProvider>(
          create: (ctx) => BannerProvider(ctx.read<BannerRepository>()),
        ),

        // Chat
        Provider<ChatRepository>(
          create: (ctx) => ChatRepositoryImpl(ctx.read<DioClient>()),
        ),
        ChangeNotifierProvider<ChatProvider>(
          create: (ctx) => ChatProvider(
            ctx.read<ChatRepository>(),
            ctx.read<PusherService>(),
          ),
        ),
      ],
      child: const _AppRoot(),
    );
  }
}

/// Hosts the router and handles app-wide startup (load /config → init Pusher)
/// and lifecycle (reconnect socket + re-fetch active order on resume).
class _AppRoot extends StatefulWidget {
  const _AppRoot();

  @override
  State<_AppRoot> createState() => _AppRootState();
}

class _AppRootState extends State<_AppRoot> with WidgetsBindingObserver {
  // Built once — never recreate on rebuild (theme/locale change), otherwise
  // the shared navigatorKey gets duplicated and the app crashes.
  late final GoRouter _router;

  @override
  void initState() {
    super.initState();
    _router = AppRouter.router();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) => _bootstrap());
  }

  Future<void> _bootstrap() async {
    // Load remote config first so Pusher picks up the key/cluster from it.
    await context.read<ConfigService>().load();
    if (!mounted) return;
    await context.read<PusherService>().init();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      final pusher = context.read<PusherService>();
      pusher.reconnect();
      // Re-fetch the active order in case socket events were missed while
      // the app was backgrounded.
      final tracking = context.read<TrackingProvider>();
      if (tracking.orderId != null) tracking.refreshStatus();
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      title: AppConstants.appName,
      theme: AppTheme.light,
      darkTheme: AppTheme.dark,
      themeMode: context.watch<ThemeProvider>().themeMode,
      // Localization (easy_localization). Arabic switches the app to RTL
      // automatically.
      localizationsDelegates: context.localizationDelegates,
      supportedLocales: context.supportedLocales,
      locale: context.locale,
      debugShowCheckedModeBanner: false,
      routerConfig: _router,
    );
  }
}
