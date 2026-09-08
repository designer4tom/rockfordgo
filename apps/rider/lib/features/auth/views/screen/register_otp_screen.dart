import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/theme/app_text_styles.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../provider/auth_provider.dart';
import '../widgets/otp_auth_shared.dart';
import '../widgets/otp_input_widget.dart';

/// OTP screen shown to NEW users (register flow) — full-screen layout with
/// an "Edit number" affordance and a "Verify & Continue" action.
class RegisterOtpScreen extends StatefulWidget {
  const RegisterOtpScreen({super.key});

  @override
  State<RegisterOtpScreen> createState() => _RegisterOtpScreenState();
}

class _RegisterOtpScreenState extends State<RegisterOtpScreen> {
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

    return Scaffold(
      appBar: AppBar(),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('auth.verify_phone'.tr(), style: AppTextStyles.h2),
              const SizedBox(height: 8),
              Text('auth.otp_subtitle'.tr(),
                  style: AppTextStyles.bodySecondary),
              const SizedBox(height: 16),
              Row(
                children: [
                  Text(
                    OtpAuth.phoneDisplay(provider.phone),
                    style: const TextStyle(
                      color: AppColors.primary,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(width: 12),
                  InkWell(
                    onTap: () => context.pop(), // back to phone entry
                    child: Row(
                      children: [
                        const Icon(Icons.edit, size: 14, color: AppColors.primary),
                        const SizedBox(width: 4),
                        Text(
                          'auth.edit_number'.tr(),
                          style: const TextStyle(
                            color: AppColors.primary,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 28),
              Center(
                child: OtpInputWidget(
                  controller: _otpController,
                  onCompleted: (_) => _verify(),
                ),
              ),
              if (provider.devOtp != null) ...[
                const SizedBox(height: 12),
                Align(
                    alignment: Alignment.center,
                    child: OtpAuth.devOtpHint(provider.devOtp!)),
              ],
              const SizedBox(height: 20),
              Center(child: OtpAuth.resendRow(context, provider, _resend)),
              const Spacer(),
              SizedBox(
                width: double.infinity,
                child: OtpAuth.primaryButton(
                  label: 'auth.verify_continue'.tr(),
                  isLoading: provider.isLoading,
                  onPressed: _verify,
                ),
              ),
              const SizedBox(height: 20),
              Center(child: OtpAuth.safeFooter()),
            ],
          ),
        ),
      ),
    );
  }
}
