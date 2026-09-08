import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/services/config_service.dart';
import '../../provider/auth_provider.dart';

/// Small shared building blocks for the register/login OTP screens so both
/// stay visually consistent while keeping separate layouts.
class OtpAuth {
  OtpAuth._();

  /// "+880 1714 231625" from a raw "01714231625".
  static String phoneDisplay(String phone) {
    final code = ConfigService.getCached().phoneCode;
    var p = phone.trim();
    if (p.startsWith('0')) p = p.substring(1);
    if (p.length > 4) p = '${p.substring(0, 4)} ${p.substring(4)}';
    return '$code $p';
  }

  static String _mmss(int s) {
    final m = (s ~/ 60).toString().padLeft(2, '0');
    final sec = (s % 60).toString().padLeft(2, '0');
    return '$m:$sec';
  }

  /// Countdown + "Didn't receive the code? Resend OTP".
  static Widget resendRow(
    BuildContext context,
    AuthProvider provider,
    VoidCallback onResend,
  ) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        if (!provider.canResend)
          Text.rich(
            TextSpan(
              style: const TextStyle(
                  fontSize: 13, color: AppColors.textSecondary),
              children: [
                TextSpan(
                  text: 'auth.resend_code_in'.tr(namedArgs: {'time': ''}),
                ),
                TextSpan(
                  text: _mmss(provider.resendSeconds),
                  style: const TextStyle(
                      color: AppColors.primary, fontWeight: FontWeight.w600),
                ),
              ],
            ),
          ),
        const SizedBox(height: 6),
        Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              '${'auth.didnt_receive'.tr()} ',
              style: const TextStyle(
                  fontSize: 13, color: AppColors.textSecondary),
            ),
            InkWell(
              onTap: provider.canResend ? onResend : null,
              child: Text(
                'auth.resend_otp'.tr(),
                style: TextStyle(
                  fontSize: 13,
                  color: provider.canResend
                      ? AppColors.primary
                      : AppColors.textSecondary,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
      ],
    );
  }

  static Widget primaryButton({
    required String label,
    required bool isLoading,
    required VoidCallback onPressed,
  }) {
    return Stack(
      children: [
        SizedBox(
          height: 54,
          width: double.infinity,
          child: ElevatedButton(
            onPressed: isLoading ? null : onPressed,
            style: ElevatedButton.styleFrom(
              shape:
                  RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            child: isLoading
                ? const SizedBox(
                    height: 22,
                    width: 22,
                    child: CircularProgressIndicator(
                        strokeWidth: 2.5, color: Colors.white),
                  )
                : Stack(
                    alignment: Alignment.center,
                    children: [
                      Text(label,
                          style: const TextStyle(
                              fontSize: 16, fontWeight: FontWeight.w600)),

                    ],
                  ),
          ),
        ),
        Positioned(

            right: 16,
            top: 0,
            bottom: 0,
            child: Icon(Icons.arrow_forward, size: 20,color: AppColors.surface,)),

      ],
    );
  }

  static Widget safeFooter() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const Icon(Icons.shield_outlined, size: 16, color: AppColors.primary),
        const SizedBox(width: 8),
        Flexible(
          child: Text(
            'auth.code_safe'.tr(),
            style: const TextStyle(
                fontSize: 12, color: AppColors.textSecondary),
          ),
        ),
      ],
    );
  }

  static Widget devOtpHint(String otp) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: AppColors.danger.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.danger),
      ),
      child: Text.rich(
        TextSpan(
          text: 'auth.test_otp'.tr(),
          style: const TextStyle(color: AppColors.danger, fontSize: 13),
          children: [
            TextSpan(
              text: otp,
              style: const TextStyle(
                color: AppColors.danger,
                fontWeight: FontWeight.bold,
                letterSpacing: 2,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
