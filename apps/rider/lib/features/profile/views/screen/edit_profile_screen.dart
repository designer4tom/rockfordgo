import 'dart:io';

import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/services/config_service.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/utils/validators.dart';
import '../../provider/profile_provider.dart';

class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({super.key});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _email;
  File? _avatar;

  @override
  void initState() {
    super.initState();
    final user = context.read<ProfileProvider>().user;
    _name = TextEditingController(text: user?.name);
    _email = TextEditingController(text: user?.email);
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    super.dispose();
  }

  Future<void> _pickAvatar() async {
    final picked = await ImagePicker().pickImage(
        source: ImageSource.gallery, imageQuality: 70, maxWidth: 1280);
    if (picked != null) setState(() => _avatar = File(picked.path));
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    final provider = context.read<ProfileProvider>();
    final ok = await provider.updateProfile(
      name: _name.text.trim(),
      email: _email.text.trim(),
      avatar: _avatar,
    );
    if (!mounted) return;
    if (ok) {
      SnackbarHelper.showSuccess(context, 'profile.edit_success'.tr());
      context.pop();
    } else {
      SnackbarHelper.showError(
          context, provider.error ?? 'profile.update_failed'.tr());
    }
  }

  String _displayPhone(String phone) {
    if (phone.isEmpty) return '';
    if (phone.startsWith('+')) return phone;
    return '${ConfigService.getCached().phoneCode} $phone';
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final user = context.read<ProfileProvider>().user;
    final isLoading = context.select<ProfileProvider, bool>((p) => p.isLoading);

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      appBar: AppBar(
        backgroundColor: theme.scaffoldBackgroundColor,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: true,
        title: Text('profile.edit'.tr(),
            style: TextStyle(
                fontWeight: FontWeight.bold,
                color: theme.colorScheme.onSurface)),
      ),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
            children: [
              // Header: avatar + title + subtitle
              Row(
                children: [
                  _avatarPicker(user?.avatar),
                  const SizedBox(width: 18),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('profile.edit'.tr(),
                            style: TextStyle(
                                fontSize: 22,
                                fontWeight: FontWeight.bold,
                                color: theme.colorScheme.onSurface)),
                        const SizedBox(height: 6),
                        Text('profile.edit_subtitle'.tr(),
                            style: const TextStyle(
                                fontSize: 13,
                                height: 1.35,
                                color: AppColors.textSecondary)),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 24),

              // Form card
              Container(
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  color: theme.cardColor,
                  borderRadius: BorderRadius.circular(18),
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
                    _field(
                      theme,
                      label: 'auth.full_name'.tr(),
                      required: true,
                      icon: Icons.person_outline,
                      controller: _name,
                      hint: 'auth.full_name_hint'.tr(),
                      validator: Validators.name,
                      textInputAction: TextInputAction.next,
                    ),
                    const SizedBox(height: 20),
                    _field(
                      theme,
                      label: 'auth.email_address'.tr(),
                      required: false,
                      icon: Icons.mail_outline,
                      controller: _email,
                      hint: 'auth.email_hint'.tr(),
                      keyboardType: TextInputType.emailAddress,
                      validator: Validators.email,
                    ),
                    const SizedBox(height: 20),
                    // Phone — read-only (changing the number isn't supported here).
                    _field(
                      theme,
                      label: 'auth.mobile_number'.tr(),
                      required: false,
                      icon: Icons.phone_outlined,
                      readOnly: true,
                      initialValue: _displayPhone(user?.phone ?? ''),
                      hint: '',
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),

              Row(
                children: [
                  const Icon(Icons.verified_user_outlined,
                      size: 18, color: AppColors.primary),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text('auth.info_secure'.tr(),
                        style: const TextStyle(
                            fontSize: 13, color: AppColors.textSecondary)),
                  ),
                ],
              ),
              const SizedBox(height: 24),

              Stack(
                children: [
                  SizedBox(
                    height: 56,
                    child: ElevatedButton(
                      onPressed: isLoading ? null : _save,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        disabledBackgroundColor:
                            AppColors.primary.withValues(alpha: 0.4),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(14)),
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
                                Text('common.save'.tr(),
                                    style: const TextStyle(
                                        fontSize: 16, fontWeight: FontWeight.w600)),
                                const SizedBox(width: 8),

                              ],
                            ),
                    ),
                  ),

                  Positioned(
                      top: 0,
                      bottom: 0,
                      right: 16,
                      child:  Icon(Icons.arrow_forward,color: AppColors.surface, size: 20)),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _avatarPicker(String? networkAvatar) {
    final hasNetwork = networkAvatar != null && networkAvatar.isNotEmpty;
    return Stack(
      clipBehavior: Clip.none,
      children: [
        CircleAvatar(
          radius: 50,
          backgroundColor: AppColors.primary.withValues(alpha: 0.12),
          backgroundImage: _avatar != null
              ? FileImage(_avatar!)
              : (hasNetwork ? NetworkImage(networkAvatar) : null)
                  as ImageProvider?,
          child: (_avatar == null && !hasNetwork)
              ? const Icon(Icons.person, size: 52, color: AppColors.primary)
              : null,
        ),
        PositionedDirectional(
          bottom: 0,
          end: 0,
          child: GestureDetector(
            onTap: _pickAvatar,
            child: Container(
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                color: AppColors.primary,
                shape: BoxShape.circle,
                border: Border.all(
                    color: Theme.of(context).scaffoldBackgroundColor, width: 3),
              ),
              child: const Icon(Icons.camera_alt, size: 16, color: Colors.white),
            ),
          ),
        ),
      ],
    );
  }

  Widget _field(
    ThemeData theme, {
    required String label,
    required bool required,
    required IconData icon,
    required String hint,
    TextEditingController? controller,
    String? initialValue,
    String? Function(String?)? validator,
    TextInputType? keyboardType,
    TextInputAction? textInputAction,
    bool readOnly = false,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text.rich(
          TextSpan(
            text: label,
            style: TextStyle(
                fontWeight: FontWeight.bold,
                fontSize: 15,
                color: theme.colorScheme.onSurface),
            children: required
                ? const [
                    TextSpan(
                        text: ' *',
                        style: TextStyle(color: AppColors.danger)),
                  ]
                : null,
          ),
        ),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          initialValue: controller == null ? initialValue : null,
          readOnly: readOnly,
          enabled: !readOnly,
          keyboardType: keyboardType,
          textInputAction: textInputAction,
          validator: validator,
          decoration: InputDecoration(
            hintText: hint,
            prefixIcon: Icon(icon, size: 20, color: AppColors.textSecondary),
            filled: true,
            fillColor: readOnly
                ? theme.dividerColor.withValues(alpha: 0.25)
                : theme.scaffoldBackgroundColor,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: theme.dividerColor),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: theme.dividerColor),
            ),
            disabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: theme.dividerColor),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: AppColors.primary, width: 1.4),
            ),
          ),
        ),
      ],
    );
  }
}
