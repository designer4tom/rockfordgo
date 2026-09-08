import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/services/config_service.dart';
import '../../../../core/theme/app_text_styles.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../provider/auth_provider.dart';
import '../widgets/otp_input_widget.dart';

class OtpVerifyScreen extends StatefulWidget {
  const OtpVerifyScreen({super.key});

  @override
  State<OtpVerifyScreen> createState() => _OtpVerifyScreenState();
}

class _OtpVerifyScreenState extends State<OtpVerifyScreen> {
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
          context,
          provider.error ?? 'auth.verify_failed'.tr(),
        );
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
      resizeToAvoidBottomInset: false,

      appBar: AppBar(),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('auth.otp_title'.tr(), style: AppTextStyles.h2),
            const SizedBox(height: 8),
            Text(
              'auth.otp_sent'.tr(namedArgs: {
                'phone': '${ConfigService.getCached().phoneCode} ${provider.phone}'
              }),
              style: AppTextStyles.bodySecondary,
            ),
            if (provider.devOtp != null) ...[
              const SizedBox(height: 16),
              _DevOtpCard(otp: provider.devOtp!),
            ],
            const SizedBox(height: 32),
            Center(
              child: OtpInputWidget(
                controller: _otpController,
                onCompleted: (_) => _verify(),
              ),
            ),
            const SizedBox(height: 24),
            Center(
              child: provider.canResend
                  ? TextButton(
                      onPressed: _resend,
                      child: Text('auth.resend'.tr()),
                    )
                  : Text(
                      'auth.resend_in'.tr(
                          namedArgs: {'seconds': '${provider.resendSeconds}'}),
                      style: AppTextStyles.bodySecondary,
                    ),
            ),
            const Spacer(),
            CustomButton(
              text: 'auth.verify_otp'.tr(),
              isLoading: provider.isLoading,
              onPressed: _verify,
            ),
            const SizedBox(height: 12),
            Center(
              child: TextButton(
                onPressed: () => context.pop(),
                child: Text(
                  'auth.change_number'.tr(),
                  style: const TextStyle(color: AppColors.textSecondary),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Shown only when the API returns the OTP in its response (dev/testing).
/// In production the OTP is never returned, so this card never appears.
class _DevOtpCard extends StatelessWidget {
  final String otp;

  const _DevOtpCard({required this.otp});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.danger.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.danger),
      ),
      child: Row(
        children: [
          const Icon(Icons.bug_report_outlined,
              color: AppColors.danger, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text.rich(
              TextSpan(
                text: 'auth.test_otp'.tr(),
                style: const TextStyle(color: AppColors.danger),
                children: [
                  TextSpan(
                    text: otp,
                    style: const TextStyle(
                      color: AppColors.danger,
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                      letterSpacing: 2,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
