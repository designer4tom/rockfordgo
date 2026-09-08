import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_constants.dart';
import '../../../../core/providers/config_provider.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/services/auth_session.dart';
import '../../../../core/storage/secure_storage.dart';
import '../../../auth/model/auth_response_model.dart';
import '../../../auth/provider/auth_provider.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _bootstrap());
  }

  Future<void> _bootstrap() async {
    final config = context.read<ConfigProvider>();
    final auth = context.read<AuthProvider>();

    // Logo dwell + config cache + persisted auth state in parallel.
    await Future.wait([
      Future.delayed(const Duration(seconds: 3)),
      config.load(),
      AuthSession.instance.load(),
    ]);
    if (!mounted) return;

    final hasToken = await SecureStorage.instance.hasToken();
    if (!hasToken) {
      _go(RouteNames.onboarding);
      return;
    }

    final result = await auth.checkStatus();
    if (!mounted) return;

    switch (result) {
      case AuthResult.approved:
        _go(RouteNames.home);
        break;
      case AuthResult.pendingApproval:
        _go(RouteNames.pendingApproval);
        break;
      case AuthResult.rejected:
        _go(RouteNames.registration); // re-submit with rejection reason
        break;
      case AuthResult.needsRegistration:
        _go(RouteNames.registration);
        break;
      case AuthResult.blocked:
        _go(RouteNames.pendingApproval);
        break;
      case AuthResult.failed:
        // Token invalid/expired → start over.
        await SecureStorage.instance.clearAll();
        _go(RouteNames.onboarding);
        break;
    }
  }

  void _go(String route) {
    if (mounted) context.go(route);
  }

  @override
  Widget build(BuildContext context) {
    final textColor = Theme.of(context).colorScheme.onSurface;

    return Scaffold(
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Image.asset(
              'assets/images/splash.gif',
              width: 300,
              fit: BoxFit.contain,
            ),
            const SizedBox(height: 16),
            Text(
              AppConstants.appName,
              style: TextStyle(
                color: textColor,
                fontSize: 24,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
