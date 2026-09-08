import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_constants.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../auth/views/widgets/otp_input_widget.dart';

/// Bottom sheet where the driver enters the customer's pickup OTP.
class OtpVerifySheet extends StatefulWidget {
  /// Returns the entered OTP via callback; parent performs the API verify.
  final Future<bool> Function(String otp) onVerify;

  const OtpVerifySheet({super.key, required this.onVerify});

  @override
  State<OtpVerifySheet> createState() => _OtpVerifySheetState();
}

class _OtpVerifySheetState extends State<OtpVerifySheet> {
  final _controller = TextEditingController();
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_controller.text.length != AppConstants.rideOtpLength) {
      setState(() => _error = 'ride.enter_otp_digits'
          .tr(namedArgs: {'count': '${AppConstants.rideOtpLength}'}));
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    final ok = await widget.onVerify(_controller.text);
    if (!mounted) return;
    setState(() => _loading = false);
    if (ok) {
      Navigator.pop(context, true);
    } else {
      setState(() => _error = 'ride.invalid_otp'.tr());
    }
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: Padding(
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('ride.verify_pickup_otp'.tr(),
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 4),
          Text('ride.ask_customer_otp'.tr(),
              style: TextStyle(color: Theme.of(context).hintColor)),
          const SizedBox(height: 20),
          Center(
            child: OtpInputWidget(
              controller: _controller,
              length: AppConstants.rideOtpLength,
              onCompleted: (_) => _submit(),
            ),
          ),
          if (_error != null) ...[
            const SizedBox(height: 12),
            Text(_error!, style: const TextStyle(color: AppColors.danger)),
          ],
          const SizedBox(height: 20),
          CustomButton(
            label: 'ride.verify_confirm_pickup'.tr(),
            loading: _loading,
            onPressed: _submit,
          ),
        ],
      ),
      ),
    );
  }
}
