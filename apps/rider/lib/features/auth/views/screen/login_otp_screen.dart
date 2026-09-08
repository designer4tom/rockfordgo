import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/services/config_service.dart';
import '../../../../core/theme/app_text_styles.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../provider/auth_provider.dart';
import '../widgets/otp_auth_shared.dart';
import '../widgets/otp_input_widget.dart';

/// OTP screen shown to EXISTING users (login flow) — card-style layout with
/// a country selector and a "Verify OTP" action.
class LoginOtpScreen extends StatefulWidget {
  const LoginOtpScreen({super.key});

  @override
  State<LoginOtpScreen> createState() => _LoginOtpScreenState();
}

class _LoginOtpScreenState extends State<LoginOtpScreen> {
  final _otpController = TextEditingController();

  @override
  void dispose() {
    _otpController.dispose();
    super.dispose();
  }

  Future<void> _verify() async {
    final provider = context.read<AuthProvider>();
    FocusScope.of(context).unfocus();
    final result = await provider.verifyOtp(_otpController.text.trim());
    if (!mounted) return;
    switch (result) {
      case AuthResult.success:
        context.go(RouteNames.home);
      case AuthResult.needsProfile:
        context.go(RouteNames.completeProfile);
      case AuthResult.failed:
        SnackbarHelper.showError(
            context, provider.error ?? 'auth.verify_failed'.tr());
    }
  }

  Future<void> _resend() async {
    final provider = context.read<AuthProvider>();
    final ok = await provider.resendOtp();
    if (!mounted) return;
    if (ok) {
      SnackbarHelper.showInfo(context, 'auth.send_otp'.tr());
    } else if (provider.error != null) {
      SnackbarHelper.showError(context, provider.error!);
    }
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<AuthProvider>();
    final theme = Theme.of(context);

    return Scaffold(
      resizeToAvoidBottomInset: false,
      appBar: AppBar(
        centerTitle: false,
        title: Text('auth.otp_short'.tr()),
        actions: [
          // Container(
          //   margin: const EdgeInsetsDirectional.only(end: 16),
          //   padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          //   decoration: BoxDecoration(
          //     borderRadius: BorderRadius.circular(8),
          //     border: Border.all(color: theme.dividerColor),
          //   ),
          //   child: Row(
          //     mainAxisSize: MainAxisSize.min,
          //     children: [
          //       Text(ConfigService.getCached().flagEmoji,
          //           style: const TextStyle(fontSize: 16)),
          //       const Icon(Icons.keyboard_arrow_down,
          //           size: 18, color: AppColors.textSecondary),
          //     ],
          //   ),
          // ),
        ],
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('auth.enter_otp'.tr(), style: AppTextStyles.h2),
              const SizedBox(height: 8),
              Text('auth.login_otp_subtitle'.tr(),
                  style: AppTextStyles.bodySecondary),
              const SizedBox(height: 6),
              Text(
                OtpAuth.phoneDisplay(provider.phone),
                style: const TextStyle(
                  color: AppColors.primary,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 24),

              // Card
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: theme.cardColor,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: theme.dividerColor),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.04),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'auth.enter_6_digit'.tr(),
                      style: TextStyle(
                        fontSize: 13,
                        color: theme.colorScheme.onSurface
                            .withValues(alpha: 0.6),
                      ),
                    ),
                    const SizedBox(height: 14),
                    Center(
                      child: OtpInputWidget(
                        controller: _otpController,
                        onCompleted: (_) => _verify(),
                      ),
                    ),
                    if (provider.devOtp != null) ...[
                      const SizedBox(height: 12),
                      Center(child: OtpAuth.devOtpHint(provider.devOtp!)),
                    ],
                    const SizedBox(height: 16),
                    Center(
                      child: OtpAuth.resendRow(context, provider, _resend),
                    ),
                    const SizedBox(height: 16),
                    SizedBox(
                      width: double.infinity,
                      child: OtpAuth.primaryButton(
                        label: 'auth.verify_otp'.tr(),
                        isLoading: provider.isLoading,
                        onPressed: _verify,
                      ),
                    ),
                  ],
                ),
              ),
              const Spacer(),
              Center(child: OtpAuth.safeFooter()),
            ],
          ),
        ),
      ),
    );
  }
}
