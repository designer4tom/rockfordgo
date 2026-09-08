import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'core/constants/app_constants.dart';
import 'features/settings/provider/theme_provider.dart';
import 'core/network/dio_client.dart';
import 'core/network/pusher_service.dart';
import 'core/providers/config_provider.dart';
import 'core/routing/app_router.dart';
import 'core/services/location_service.dart';
import 'core/storage/secure_storage.dart';
import 'core/theme/app_theme.dart';
import 'features/auth/provider/auth_provider.dart';
import 'features/auth/provider/registration_provider.dart';
import 'features/auth/repository/auth_repository.dart';
import 'features/auth/repository/auth_repository_impl.dart';
import 'features/chat/provider/chat_provider.dart';
import 'features/chat/repository/chat_repository.dart';
import 'features/complaint/provider/complaint_provider.dart';
import 'features/documents/provider/documents_provider.dart';
import 'features/documents/repository/documents_repository.dart';
import 'features/earnings/provider/earnings_provider.dart';
import 'features/earnings/repository/earnings_repository.dart';
import 'features/history/provider/history_provider.dart';
import 'features/history/repository/history_repository.dart';
import 'features/home/provider/home_provider.dart';
import 'features/home/repository/home_repository.dart';
import 'features/home/repository/home_repository_impl.dart';
import 'features/notification/provider/notification_provider.dart';
import 'features/notification/repository/notification_repository.dart';
import 'features/order_request/provider/order_request_provider.dart';
import 'features/order_request/repository/order_request_repository.dart';
import 'features/order_request/repository/order_request_repository_impl.dart';
import 'features/parcel_order/provider/parcel_order_provider.dart';
import 'features/parcel_order/repository/parcel_order_repository.dart';
import 'features/parcel_order/repository/parcel_order_repository_impl.dart';
import 'features/performance/provider/performance_provider.dart';
import 'features/profile/provider/profile_provider.dart';
import 'features/page/provider/page_provider.dart';
import 'features/page/repository/page_repository.dart';
import 'features/profile/repository/profile_repository.dart';
import 'features/recharge/provider/recharge_provider.dart';
import 'features/recharge/repository/recharge_repository.dart';
import 'features/recharge/repository/recharge_repository_impl.dart';
import 'features/ride_order/provider/ride_order_provider.dart';
import 'features/ride_order/repository/ride_order_repository.dart';
import 'features/ride_order/repository/ride_order_repository_impl.dart';
import 'features/wallet/provider/wallet_provider.dart';
import 'features/wallet/repository/wallet_repository.dart';

class DriverApp extends StatelessWidget {
  final SharedPreferences prefs;
  const DriverApp({super.key, required this.prefs});

  @override
  Widget build(BuildContext context) {
    final AuthRepository authRepo = AuthRepositoryImpl();
    final HomeRepository homeRepo = HomeRepositoryImpl();
    final OrderRequestRepository orderRepo = OrderRequestRepositoryImpl();
    final RideOrderRepository rideRepo = RideOrderRepositoryImpl();
    final ParcelOrderRepository parcelRepo = ParcelOrderRepositoryImpl();
    final earningsRepo = EarningsRepository();
    final walletRepo = WalletRepository();
    final documentsRepo = DocumentsRepository();
    final historyRepo = HistoryRepository();
    final profileRepo = ProfileRepository();
    final notificationRepo = NotificationRepository();
    final chatRepo = ChatRepository();
    final RechargeRepository rechargeRepo = RechargeRepositoryImpl();
    final pusher = PusherService(SecureStorage.instance, DioClient.instance.dio);

    return MultiProvider(
      providers: [
        Provider<SharedPreferences>.value(value: prefs),
        ChangeNotifierProvider(create: (_) => ThemeProvider(prefs)),
        Provider<AuthRepository>.value(value: authRepo),
        Provider<PusherService>.value(value: pusher),
        ChangeNotifierProvider(create: (_) => ConfigProvider()),
        ChangeNotifierProvider(create: (_) => AuthProvider(authRepo)),
        ChangeNotifierProvider(create: (_) => RegistrationProvider(authRepo)),
        ChangeNotifierProvider(
          create: (_) => HomeProvider(
            homeRepo,
            LocationService.instance,
            pusher,
          ),
        ),
        ChangeNotifierProvider(
          create: (_) => OrderRequestProvider(orderRepo),
        ),
        ChangeNotifierProvider(
          create: (_) =>
              RideOrderProvider(rideRepo, LocationService.instance, pusher),
        ),
        ChangeNotifierProvider(
          create: (_) =>
              ParcelOrderProvider(parcelRepo, LocationService.instance, pusher),
        ),
        ChangeNotifierProvider(create: (_) => EarningsProvider(earningsRepo)),
        ChangeNotifierProvider(create: (_) => WalletProvider(walletRepo)),
        ChangeNotifierProvider(create: (_) => DocumentsProvider(documentsRepo)),
        ChangeNotifierProvider(create: (_) => PerformanceProvider()),
        ChangeNotifierProvider(create: (_) => HistoryProvider(historyRepo)),
        ChangeNotifierProvider(
          create: (_) => ProfileProvider(profileRepo, authRepo),
        ),
        ChangeNotifierProvider(
          create: (_) => NotificationProvider(notificationRepo),
        ),
        ChangeNotifierProvider(
          create: (_) => ChatProvider(chatRepo, pusher),
        ),
        ChangeNotifierProvider(create: (_) => ComplaintProvider()),
        ChangeNotifierProvider(create: (_) => RechargeProvider(rechargeRepo)),
        ChangeNotifierProvider(create: (_) => PageProvider(PageRepository())),
      ],
      child: ScreenUtilInit(
        designSize: const Size(375, 812),
        minTextAdapt: true,
        splitScreenMode: true,
        builder: (context, _) => MaterialApp.router(
          title: AppConstants.appName,
          debugShowCheckedModeBanner: false,
          localizationsDelegates: context.localizationDelegates,
          supportedLocales: context.supportedLocales,
          locale: context.locale,
          theme: AppTheme.light,
          darkTheme: AppTheme.dark,
          themeMode: context.watch<ThemeProvider>().themeMode,
          routerConfig: AppRouter.router,
        ),
      ),
    );
  }
}
