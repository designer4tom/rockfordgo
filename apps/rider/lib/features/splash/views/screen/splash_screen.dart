import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_constants.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/storage/secure_storage.dart';
import '../../../auth/provider/auth_provider.dart';
import '../../../ride/provider/ride_provider.dart';
import '../../../tracking/provider/tracking_provider.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    final storage = context.read<SecureStorage>();

    // Show the logo for at least 3s while we resolve where to go.
    final results = await Future.wait([
      Future<void>.delayed(const Duration(seconds: 3)),
      storage.hasToken(),
      SharedPreferences.getInstance(),
    ]);

    if (!mounted) return;

    final hasToken = results[1] as bool;
    final prefs = results[2] as SharedPreferences;
    final onboardingDone = prefs.getBool(AppConstants.onboardingKey) ?? false;

    if (!hasToken) {
      context.go(
        onboardingDone ? RouteNames.phoneEntry : RouteNames.onboarding,
      );
      return;
    }

    // Logged in → restore the cached user profile so screens that pre-fill
    // from it (e.g. parcel sender info) have the data after a restart.
    await context.read<AuthProvider>().loadStoredUser();
    if (!mounted) return;

    // Resume an in-progress ride/parcel if there is one, so the tracking
    // screen is never lost after an app kill/restart.
    final active = await context.read<TrackingProvider>().checkActiveOrder();
    if (!mounted) return;

    if (active == null) {
      context.go(RouteNames.home);
    } else if (active.isParcel) {
      context.go(RouteNames.parcelTracking, extra: active.orderId);
    } else if (active.isSearching) {
      context.read<RideProvider>().resumeSearching(active.orderId);
      context.go('/searching-driver');
    } else {
      context.go(RouteNames.rideTracking, extra: active.orderId);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SizedBox.expand(
        child: Image.asset(
          'assets/images/splash.gif',
          fit: BoxFit.cover,
          errorBuilder: (context, error, stackTrace) => Center(
            child: Container(
              height: 96,
              width: 96,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(24),
              ),
              child: const Icon(
                Icons.local_taxi_rounded,
                size: 56,
                color: AppColors.primary,
              ),
            ),
          ),
        ),
      ),
    );
  }
}
