import 'package:flutter/material.dart';
import 'package:pinput/pinput.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_constants.dart';

class OtpInputWidget extends StatelessWidget {
  final TextEditingController controller;
  final ValueChanged<String>? onCompleted;

  /// Number of OTP digits. Defaults to the 6-digit login OTP; pass
  /// [AppConstants.rideOtpLength] (4) for ride/parcel pickup OTPs.
  final int? length;

  const OtpInputWidget({
    super.key,
    required this.controller,
    this.onCompleted,
    this.length,
  });

  @override
  Widget build(BuildContext context) {
    final defaultTheme = PinTheme(
      width: 52,
      height: 56,
      textStyle: TextStyle(
        fontSize: 20,
        fontWeight: FontWeight.w600,
        color: Theme.of(context).colorScheme.onSurface,
      ),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: Theme.of(context).dividerColor.withValues(alpha: 0.4),
        ),
      ),
    );

    return Pinput(
      length: length ?? AppConstants.otpLength,
      controller: controller,
      defaultPinTheme: defaultTheme,
      focusedPinTheme: defaultTheme.copyWith(
        decoration: defaultTheme.decoration!.copyWith(
          border: Border.all(color: AppColors.primary, width: 1.5),
        ),
      ),
      onCompleted: onCompleted,
    );
  }
}
