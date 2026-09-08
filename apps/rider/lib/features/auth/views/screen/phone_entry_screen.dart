import 'package:dotted_border/dotted_border.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter/gestures.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/services/config_service.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/utils/validators.dart';
import '../../provider/auth_provider.dart';

class PhoneEntryScreen extends StatefulWidget {
  const PhoneEntryScreen({super.key});

  @override
  State<PhoneEntryScreen> createState() => _PhoneEntryScreenState();
}

class _PhoneEntryScreenState extends State<PhoneEntryScreen> {
  // Drop the isolated login illustration (faint map + route + pin, no text)
  // here. Until the file exists the screen still renders cleanly.
  static const String _bgAsset = 'assets/images/login_bg_image.png';

  final _formKey = GlobalKey<FormState>();
  final _phoneController = TextEditingController();
  late final TapGestureRecognizer _termsTap;
  late final TapGestureRecognizer _privacyTap;

  @override
  void initState() {
    super.initState();
    _termsTap = TapGestureRecognizer()
      ..onTap = () => context.push('/page/terms-conditions');
    _privacyTap = TapGestureRecognizer()
      ..onTap = () => context.push('/page/privacy-policy');
  }

  @override
  void dispose() {
    _phoneController.dispose();
    _termsTap.dispose();
    _privacyTap.dispose();
    super.dispose();
  }

  Future<void> _continue() async {
    if (!_formKey.currentState!.validate()) return;
    Helpers.dismissKeyboard(context);

    final provider = context.read<AuthProvider>();
    final ok = await provider.sendOtp(_phoneController.text.trim());

    if (!mounted) return;
    if (ok) {
      // Existing user → login OTP; new user (or unknown) → register OTP.
      context.push(
        provider.userExists == true
            ? RouteNames.loginOtp
            : RouteNames.registerOtp,
      );
    } else if (provider.error != null) {
      SnackbarHelper.showError(context, provider.error!);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isLoading = context.select<AuthProvider, bool>((p) => p.isLoading);
    // Rebuild once /config finishes loading so the real backend phone
    // code/flag replace the fallback shown on the first frame.
    context.watch<ConfigService>();

    return Scaffold(
      resizeToAvoidBottomInset: false,
     backgroundColor: Theme.of(context).scaffoldBackgroundColor,

      body: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                 Padding(
                   padding: const EdgeInsets.symmetric(horizontal: 16.0,vertical: 6.0),
                   child: Column(
                     crossAxisAlignment: CrossAxisAlignment.start,
                     children: [
                       Row(
                         mainAxisAlignment: MainAxisAlignment.spaceBetween,
                         children: [
                           Image.asset('assets/images/logo.png', height: 34),
                           const _LanguageChip(),
                         ],
                       ),
                       const SizedBox(height: 5),
          
                       // Hero text
                       _brandTagline(),
                     ],
                   ),
                 ),
          
          
                  Stack(
                    children: [
          
                      Image.asset(
                            _bgAsset,
                            fit: BoxFit.cover,
                            height: 220,
                            width: double.infinity,
                            errorBuilder: (context, error, stack) => const SizedBox.shrink(),
                          ),
                      Positioned(
          
                        top: 50,
                        bottom: 0,
                        left: 16,
          
          
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
          
                          children: [
                            Row(
                              children: [
                                Text(
                                  'auth.welcome_back'.tr(),
                                  style: TextStyle(
                                    fontSize: 28,
                                    fontWeight: FontWeight.bold,
                                    color: Theme.of(context).colorScheme.onSurface,
                                  ),
                                ),
                                const SizedBox(width: 6),
                                const Text('👋', style: TextStyle(fontSize: 24)),
                              ],
                            ),
          
                            const SizedBox(height: 6),
                            Text(
                              'auth.lets_get_on_road'.tr(),
                              style: const TextStyle(
                                fontSize: 15,
                                color: AppColors.textSecondary,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
          
          
                  Form(key: _formKey, child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16.0,vertical: 0),
                    child: _phoneCard(isLoading),
                  )),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _brandTagline() {
    const blue = TextStyle(
      fontSize: 14,
      fontWeight: FontWeight.w600,
      color: AppColors.primary,
    );
    const grey = TextStyle(fontSize: 14, color: AppColors.textSecondary);
    // "Safe. Fast. Reliable." with the middle word emphasised.
    final parts = 'auth.brand_tagline'.tr().split(' ');
    return Wrap(
      children: [
        for (int i = 0; i < parts.length; i++)
          Text('${parts[i]} ', style: i == 1 ? blue : grey),
      ],
    );
  }

  Widget _phoneCard(bool isLoading) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(16),
       // border: Border.all(color: Theme.of(context).dividerColor),
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
            'auth.mobile_number'.tr(),
            style: TextStyle(
              fontWeight: FontWeight.w600,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
          const SizedBox(height: 12),
          Container(
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Theme.of(context).dividerColor.withValues(alpha: 0.2)),
            ),
            child: Row(
              children: [
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 12),
                  child: Row(
                    children: [
                      Text(ConfigService.getCached().flagEmoji,
                          style: const TextStyle(fontSize: 18)),
                      const SizedBox(width: 6),
                      Text(ConfigService.getCached().phoneCode,
                          style: TextStyle(
                              color:
                                  Theme.of(context).colorScheme.onSurface)),
                    ],
                  ),
                ),
                Container(
                    width: 1,
                    height: 28,
                    color: Theme.of(context).dividerColor),
                Expanded(
                  child: TextFormField(
                    controller: _phoneController,
                    keyboardType: TextInputType.phone,
                    maxLength: ConfigService.getCached().phoneMaxLength,
                    validator: Validators.phone,
                    inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                    decoration: InputDecoration(
                      counterText: '',
                      filled: false,
                      border: InputBorder.none,
                      enabledBorder: InputBorder.none,
                      focusedBorder: InputBorder.none,
                      errorBorder: InputBorder.none,
                      focusedErrorBorder: InputBorder.none,
                      isCollapsed: true,
                      contentPadding:
                          const EdgeInsets.symmetric(vertical: 16),
                      prefixIcon: const Icon(Icons.phone_outlined,
                          size: 20, color: AppColors.textSecondary),
                      prefixIconConstraints:
                          const BoxConstraints(minWidth: 38),
                      hintText: 'auth.enter_mobile'.tr(),
                      hintStyle: TextStyle(
                        fontSize: 14,
                      )
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Stack(
            children: [
              SizedBox(
                width: double.infinity,
                height: 54,
                child: ElevatedButton(
                  onPressed: isLoading ? null : _continue,
                  style: ElevatedButton.styleFrom(
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                  ),
                  child: isLoading
                      ? const SizedBox(
                          height: 22,
                          width: 22,
                          child: CircularProgressIndicator(
                              strokeWidth: 2.5, color: Colors.white),
                        )
                      : Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text('common.continue'.tr()),
                            const SizedBox(width: 8),
                            //const Icon(Icons.arrow_forward, size: 20),
                          ],
                        ),
                ),
              ),

              isLoading ? SizedBox() : Positioned(
                right: 16,
                top: 0,
                bottom: 0,

                child: Icon(Icons.arrow_forward, size: 20,color: AppColors.background,),)

            ],
          ),
          const SizedBox(height: 20),

          // Why login
          Row(
            children: [

              Expanded(
                child: Container(
                  height: 1,
                 color:Theme.of(context).dividerColor.withValues(alpha: 0.2),
                 width: double.infinity,
                ),
              ),
              SizedBox(width: 10,),

              Center(
                child: Text(
                  'auth.why_login'.tr(),
                  style: const TextStyle(
                    color: AppColors.textSecondary,
                    fontSize: 13,
                  ),
                ),
              ),
              SizedBox(width: 10,),
              Expanded(
                child: Container(
                  height: 1,
                  color:Theme.of(context).dividerColor.withValues(alpha: 0.2),
                  width: double.infinity,
                ),
              ),
            ],
          ),
          const SizedBox(height: 30),

          // Features
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _feature(Icons.verified_user_outlined, 'auth.secure_login'.tr()),
              _feature(
                  Icons.chat_bubble_outline, 'auth.otp_verification'.tr()),
              _feature(Icons.person_outline, 'auth.no_password'.tr()),
            ],
          ),
          const SizedBox(height: 16),
          DottedBorder(
            options: CustomPathDottedBorderOptions(
              dashPattern: [6, 4],
              strokeWidth: 1,
              color: Colors.grey.withValues(alpha: 0.3),
              padding: EdgeInsets.zero,
              customPath: (size) => Path()
                ..moveTo(0, 0)
                ..lineTo(size.width, 0),
            ),
            child: const SizedBox(
              width: double.infinity,
              height: 1,
            ),
          ),
          const SizedBox(height: 30),

          // Terms
          _terms(),
        ],
      ),
    );
  }

  Widget _feature(IconData icon, String label) {
    return Expanded(
      child: Column(
        children: [
          Container(
            height: 48,
            width: 48,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: AppColors.primary, size: 22),
          ),
          const SizedBox(height: 8),
          Text(
            label,
            textAlign: TextAlign.center,
            style:
                const TextStyle(fontSize: 11, color: AppColors.textSecondary),
          ),
        ],
      ),
    );
  }

  Widget _terms() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Container(
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              shape: BoxShape.circle
            ),

            child: Padding(
              padding: const EdgeInsets.all(8.0),
              child: const Icon(Icons.lock_outline, size: 22, color: AppColors.primary),
            )),
        const SizedBox(width: 8),

  Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(
        'auth.terms_prefix'.tr(),
    style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
      ),
      Text.rich(
        TextSpan(
          // text:
          // style: const TextStyle(
          //     fontSize: 12, color: AppColors.textSecondary),
          children: [
            TextSpan(
              text: 'auth.terms_of_service'.tr(),
              style: const TextStyle(color: AppColors.primary,fontSize: 10),
              recognizer: _termsTap,
            ),
            TextSpan(text: 'auth.and'.tr(),style: TextStyle(fontSize: 10)),
            TextSpan(
              text: 'auth.privacy'.tr(),
              style: const TextStyle(color: AppColors.primary,fontSize: 10),
              recognizer: _privacyTap,
            ),
          ],
        ),
        textAlign: TextAlign.center,
      ),
    ],
  )
      ],
    );
  }
}

/// Language switcher chip in the top-right (🌐 English ⌄).
class _LanguageChip extends StatelessWidget {
  const _LanguageChip();

  @override
  Widget build(BuildContext context) {
    final isArabic = context.locale.languageCode == 'ar';
    return PopupMenuButton<String>(

      offset: Offset(0,40),
      elevation: 1,
      color: Theme.of(context).brightness == Brightness.light
          ? Colors.white
          : Theme.of(context).cardColor,
      onSelected: (code) => context.setLocale(Locale(code)),
      itemBuilder: (context) => [
        PopupMenuItem(
          value: 'en',
          child: Text('settings.english'.tr()),
        ),
        PopupMenuItem(
          value: 'ar',
          child: Text('settings.arabic'.tr()),
        ),
      ],
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: Theme.of(context).cardColor,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: Theme.of(context).dividerColor.withValues(alpha: 0.3)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.language,
                size: 16, color: AppColors.primary),
            const SizedBox(width: 6),
            Text(
              isArabic ? 'settings.arabic'.tr() : 'settings.english'.tr(),
              style: TextStyle(
                  fontSize: 13,
                  color: Theme.of(context).colorScheme.onSurface),
            ),
            const Icon(Icons.keyboard_arrow_down,
                size: 18, color: AppColors.textSecondary),
          ],
        ),
      ),
    );
  }
}
