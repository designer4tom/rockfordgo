import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/providers/config_provider.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/widgets/custom_textfield.dart';
import '../../provider/auth_provider.dart';

class PhoneEntryScreen extends StatefulWidget {
  const PhoneEntryScreen({super.key});

  @override
  State<PhoneEntryScreen> createState() => _PhoneEntryScreenState();
}

class _PhoneEntryScreenState extends State<PhoneEntryScreen> {
  final _formKey = GlobalKey<FormState>();
  final _phoneController = TextEditingController();

  @override
  void dispose() {
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final auth = context.read<AuthProvider>();
    final ok = await auth.sendOtp(_phoneController.text.trim());
    if (!mounted) return;
    if (ok) {
      context.push(RouteNames.otpVerify);
    } else {
      AppSnackbar.error(context, auth.error ?? 'auth.send_otp_failed'.tr());
    }
  }

  Widget _countryCodePrefix(
    BuildContext context, {
    required String countryFlag,
    required String phoneCode,
  }) {
    return Padding(
      padding: const EdgeInsets.only(left: 12, right: 8),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(countryFlag, style: const TextStyle(fontSize: 18)),
          const SizedBox(width: 6),
          Text(
            phoneCode,
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w600,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
          const SizedBox(width: 8),
          Container(
            width: 1,
            height: 20,
            color: Theme.of(context).dividerColor,
          ),
          const SizedBox(width: 8),
          Icon(
            Icons.phone_outlined,
            size: 20,
            color: Theme.of(context).hintColor,
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final loading = context.watch<AuthProvider>().loading;
    final config = context.watch<ConfigProvider>().config;
    final countryFlag = config?.countryFlag ?? '🇧🇩';
    final phoneCode = config?.phoneCode ?? '+880';
    final phoneExample = config?.phoneExample ?? '01XXXXXXXXX';
    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 32),
                const Icon(
                  Icons.local_taxi_rounded,
                  size: 64,
                  color: AppColors.primary,
                ),
                const SizedBox(height: 24),
                Text(
                  'auth.join_as_driver'.tr(),
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.onSurface,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'auth.phone_entry_subtitle'.tr(),
                  style: TextStyle(color: Theme.of(context).hintColor),
                ),
                const SizedBox(height: 32),
                CustomTextField(
                  controller: _phoneController,
                  label: 'auth.phone_number'.tr(),
                  hint: phoneExample,
                  prefix: _countryCodePrefix(
                    context,
                    countryFlag: countryFlag,
                    phoneCode: phoneCode,
                  ),
                  keyboardType: TextInputType.phone,
                  inputFormatters: [
                    FilteringTextInputFormatter.digitsOnly,
                    LengthLimitingTextInputFormatter(14),
                  ],
                  validator: Validators.phone,
                ),
                const SizedBox(height: 24),
                CustomButton(
                  label: 'auth.send_otp'.tr(),
                  loading: loading,
                  onPressed: _submit,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
