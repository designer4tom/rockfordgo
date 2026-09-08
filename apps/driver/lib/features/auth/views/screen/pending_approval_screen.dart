import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../model/auth_response_model.dart';
import '../../provider/auth_provider.dart';

class PendingApprovalScreen extends StatefulWidget {
  const PendingApprovalScreen({super.key});

  @override
  State<PendingApprovalScreen> createState() => _PendingApprovalScreenState();
}

class _PendingApprovalScreenState extends State<PendingApprovalScreen> {
  bool _checking = false;

  Future<void> _refresh() async {
    setState(() => _checking = true);
    final auth = context.read<AuthProvider>();
    final result = await auth.checkStatus();
    if (!mounted) return;
    setState(() => _checking = false);

    switch (result) {
      case AuthResult.approved:
        context.go(RouteNames.home);
        break;
      case AuthResult.needsRegistration:
        context.go(RouteNames.registration);
        break;
      case AuthResult.rejected:
        context.go(RouteNames.registration);
        break;
      case AuthResult.pendingApproval:
        AppSnackbar.show(context, 'auth.still_under_review'.tr());
        break;
      case AuthResult.blocked:
      case AuthResult.failed:
        break;
    }
  }

  Future<void> _logout() async {
    await context.read<AuthProvider>().logout();
    if (mounted) context.go(RouteNames.onboarding);
  }

  @override
  Widget build(BuildContext context) {
    final driver = context.watch<AuthProvider>().driver;
    final isRejected = driver?.isRejected ?? false;

    return Scaffold(
      appBar: AppBar(
        automaticallyImplyLeading: false,
        actions: [
          IconButton(
            onPressed: _logout,
            icon: const Icon(Icons.logout),
            tooltip: 'auth.logout'.tr(),
          ),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SizedBox(height: 24),
              CircleAvatar(
                radius: 56,
                backgroundColor: (isRejected ? AppColors.danger : AppColors.warning)
                    .withValues(alpha: 0.12),
                child: Icon(
                  isRejected ? Icons.cancel_outlined : Icons.hourglass_top_rounded,
                  size: 56,
                  color: isRejected ? AppColors.danger : AppColors.warning,
                ),
              ),
              const SizedBox(height: 24),
              Text(
                isRejected
                    ? 'auth.application_rejected'.tr()
                    : 'auth.application_under_review'.tr(),
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  color: Theme.of(context).colorScheme.onSurface,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                isRejected
                    ? 'auth.application_rejected_message'.tr()
                    : 'auth.application_review_message'.tr(),
                textAlign: TextAlign.center,
                style: TextStyle(color: Theme.of(context).hintColor, height: 1.5),
              ),
              if (isRejected && (driver?.rejectionReason?.isNotEmpty ?? false)) ...[
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: AppColors.danger.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.danger.withValues(alpha: 0.3)),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Icon(Icons.info_outline,
                          color: AppColors.danger, size: 20),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          driver!.rejectionReason!,
                          style: const TextStyle(color: AppColors.danger),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 32),
              if (isRejected)
                CustomButton(
                  label: 'auth.resubmit'.tr(),
                  icon: Icons.refresh,
                  onPressed: () => context.go(RouteNames.registration),
                )
              else
                CustomButton(
                  label: 'auth.check_status'.tr(),
                  icon: Icons.refresh,
                  loading: _checking,
                  onPressed: _refresh,
                ),
            ],
          ),
        ),
      ),
    );
  }
}
