import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_constants.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../model/auth_response_model.dart';
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
    if (_otpController.text.length != AppConstants.otpLength) {
      AppSnackbar.error(
        context,
        'auth.enter_digit_code'.tr(namedArgs: {'count': '${AppConstants.otpLength}'}),
      );
      return;
    }
    final auth = context.read<AuthProvider>();
    final result = await auth.verifyOtp(_otpController.text);
    if (!mounted) return;

    switch (result) {
      case AuthResult.approved:
        context.go(RouteNames.home);
        break;
      case AuthResult.needsRegistration:
        context.go(RouteNames.registration);
        break;
      case AuthResult.pendingApproval:
        context.go(RouteNames.pendingApproval);
        break;
      case AuthResult.rejected:
        context.go(RouteNames.registration);
        AppSnackbar.error(
          context,
          auth.driver?.rejectionReason ?? 'auth.application_rejected_resubmit'.tr(),
        );
        break;
      case AuthResult.blocked:
        _showBlockedDialog();
        break;
      case AuthResult.failed:
        AppSnackbar.error(context, auth.error ?? 'auth.verification_failed'.tr());
        break;
    }
  }

  Future<void> _resend() async {
    final auth = context.read<AuthProvider>();
    final ok = await auth.sendOtp(auth.phone);
    if (!mounted) return;
    AppSnackbar.show(
      context,
      ok ? 'auth.otp_resent'.tr() : (auth.error ?? 'common.failed'.tr()),
    );
  }

  void _showBlockedDialog() {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('auth.account_blocked'.tr()),
        content: Text('auth.account_blocked_message'.tr()),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text('common.ok'.tr()),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(24, 24, 24, 0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'auth.verify_otp'.tr(),
                      style: TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        color: Theme.of(context).colorScheme.onSurface,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'auth.enter_digit_code'.tr(
                          namedArgs: {'count': '${AppConstants.otpLength}'}),
                      style: TextStyle(
                        fontSize: 13,
                        color: Theme.of(context).hintColor,
                      ),
                    ),
                    const SizedBox(height: 16),
                    GestureDetector(
                      onTap: () => context.pop(),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            auth.phone,
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              color: AppColors.primary,
                            ),
                          ),
                          const SizedBox(width: 8),
                          const Icon(Icons.edit_outlined,
                              size: 14, color: AppColors.primary),
                          const SizedBox(width: 4),
                          Text(
                            'auth.edit_number'.tr(),
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              color: AppColors.primary,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 28),
                    Center(
                      child: OtpInputWidget(
                        controller: _otpController,
                        onCompleted: (_) => _verify(),
                      ),
                    ),
                    if (auth.devOtp != null && auth.devOtp!.isNotEmpty) ...[
                      const SizedBox(height: 14),
                      Center(child: _DevOtpChip(otp: auth.devOtp!)),
                    ],
                    const SizedBox(height: 20),
                    Center(
                      child: auth.canResend
                          ? RichText(
                              text: TextSpan(
                                style: TextStyle(
                                  fontSize: 13,
                                  color: Theme.of(context).hintColor,
                                ),
                                children: [
                                  TextSpan(
                                      text:
                                          '${'auth.didnt_receive_code'.tr()} '),
                                  TextSpan(
                                    text: 'auth.resend_otp'.tr(),
                                    style: const TextStyle(
                                      color: AppColors.primary,
                                      fontWeight: FontWeight.w600,
                                    ),
                                    recognizer: TapGestureRecognizer()
                                      ..onTap = _resend,
                                  ),
                                ],
                              ),
                            )
                          : Text(
                              'auth.resend_after_seconds'.tr(
                                  namedArgs: {
                                  'seconds': '${auth.resendSeconds}'
                                }),
                              style: TextStyle(
                                fontSize: 13,
                                color: Theme.of(context).hintColor,
                              ),
                            ),
                    ),
                  ],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 12, 24, 20),
              child: Column(
                children: [
                  CustomButton(
                    label: 'auth.verify_and_continue'.tr(),
                    trailingIcon: Icons.arrow_forward,
                    loading: auth.loading,
                    onPressed: _verify,
                  ),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.shield_outlined,
                          size: 14, color: Theme.of(context).primaryColor),
                      const SizedBox(width: 6),
                      Text(
                        'auth.otp_safe_with_us'.tr(),
                        style: TextStyle(
                          fontSize: 12,
                          color: Theme.of(context).hintColor,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Dev-only helper: shows the OTP echoed by the API (non-production).
/// Hidden automatically in production where the response omits the OTP.
class _DevOtpChip extends StatelessWidget {
  final String otp;
  const _DevOtpChip({required this.otp});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.danger.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.danger.withValues(alpha: 0.5)),
      ),
      child: RichText(
        text: TextSpan(
          style: const TextStyle(
            color: AppColors.danger,
            fontSize: 13,
            fontWeight: FontWeight.w500,
          ),
          children: [
            const TextSpan(text: 'Test OTP: '),
            TextSpan(
              text: otp,
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                letterSpacing: 1,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
