import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../provider/auth_provider.dart';

class RegistrationSuccessScreen extends StatelessWidget {
  const RegistrationSuccessScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final user = context.select<AuthProvider, dynamic>((p) => p.user);
    final avatar = user?.avatar as String?;

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      body: Stack(
        children: [
          Positioned(
            left: 0,
            right: 0,
            bottom: 10,
            child: IgnorePointer(
              child: Image.asset(
                'assets/images/regi_car.png',
                fit: BoxFit.fill,
              ),
            ),
          ),
          SafeArea(
            child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 10),
          children: [
            const SizedBox(height: 24),
            _successBadge(),
            const SizedBox(height: 24),
            Text(
              'auth.registration_successful'.tr(),
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: theme.colorScheme.onSurface,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'auth.account_created'.tr(),
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 14,
                color: AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 28),
            _welcomeCard(theme, avatar: avatar),
            const SizedBox(height: 28),
            Stack(
              children: [
                SizedBox(
                  height: 56,
                  child: ElevatedButton(
                    onPressed: () =>   context.push(RouteNames.setDestination),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.directions_car_filled, size: 20),
                        const SizedBox(width: 8),
                        Text(
                          'auth.start_riding'.tr(),
                          style: const TextStyle(
                              fontSize: 16, fontWeight: FontWeight.w600),
                        ),

                      ],
                    ),
                  ),
                ),

                 Positioned(

                     top: 0,
                     bottom: 0,
                     right: 16,
                     child: Icon(Icons.arrow_forward, size: 20,color: AppColors.surface,)),
              ],
            ),
            const SizedBox(height: 12),
            SizedBox(
              height: 56,
              child: OutlinedButton(
                onPressed: () => context.go(RouteNames.home),
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppColors.primary,
                  side: const BorderSide(color: AppColors.primary),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.home_outlined, size: 20),
                    const SizedBox(width: 8),
                    Text(
                      'auth.go_to_home'.tr(),
                      style: const TextStyle(
                          fontSize: 16, fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ),
            ),

          ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _successBadge() {
    return Center(
      child: Container(
        width: 100,
        height: 100,
        decoration: BoxDecoration(
          color: AppColors.success.withValues(alpha: 0.15),
          shape: BoxShape.circle,
        ),
        alignment: Alignment.center,
        child: Container(
          width: 60,
          height: 60,
          decoration: const BoxDecoration(
            color: AppColors.success,
            shape: BoxShape.circle,
          ),
          child: const Icon(Icons.check, color: Colors.white, size: 30),
        ),
      ),
    );
  }

  Widget _welcomeCard(ThemeData theme, {String? avatar}) {
    return Container(
      padding: const EdgeInsets.fromLTRB(18, 24, 18, 18),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ]
        //border: Border.all(color: theme.dividerColor),
      ),
      child: Column(
        children: [
          Stack(
            clipBehavior: Clip.none,
            children: [
              CircleAvatar(
                radius: 40,
                backgroundColor: AppColors.primary.withValues(alpha: 0.12),
                backgroundImage:
                    (avatar != null && avatar.isNotEmpty) ? NetworkImage(avatar) : null,
                child: (avatar == null || avatar.isEmpty)
                    ? const Icon(Icons.person,
                        size: 50, color: AppColors.primary)
                    : null,
              ),
              PositionedDirectional(
                bottom: 0,
                end: 0,
                child: Container(
                  width: 28,
                  height: 28,
                  decoration: BoxDecoration(
                    color: AppColors.success,
                    shape: BoxShape.circle,
                    border: Border.all(color: theme.cardColor, width: 2),
                  ),
                  child: const Icon(Icons.check,
                      size: 16, color: Colors.white),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            'auth.welcome_to_app'.tr(),
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: theme.colorScheme.onSurface,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'auth.ready_first_ride'.tr(),
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 13,
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: _infoTile(
                  background: AppColors.success.withValues(alpha: 0.10),
                  iconBg: AppColors.success,
                  icon: Icons.verified_user,
                  titleColor: AppColors.success,
                  title: 'auth.account_verified'.tr(),
                  subtitle: 'auth.account_verified_sub'.tr(),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: _infoTile(
                  background: AppColors.primary.withValues(alpha: 0.08),
                  iconBg: AppColors.primary,
                  icon: Icons.account_balance_wallet,
                  titleColor: AppColors.primary,
                  title: 'auth.wallet_ready'.tr(),
                  subtitle: 'auth.wallet_ready_sub'.tr(),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _infoTile({
    required Color background,
    required Color iconBg,
    required IconData icon,
    required Color titleColor,
    required String title,
    required String subtitle,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 12,horizontal: 10),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: iconBg.withValues(alpha: 0.9),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, size: 18, color: Colors.white),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: titleColor,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  maxLines: 3,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 8.8,
                    color: AppColors.textSecondary,
                    height: 1.3,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
