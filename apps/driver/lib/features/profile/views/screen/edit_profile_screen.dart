import 'dart:io';

import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/widgets/custom_textfield.dart';
import '../../../home/provider/home_provider.dart';
import '../../provider/profile_provider.dart';

class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({super.key});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  File? _avatar;

  @override
  void initState() {
    super.initState();
    final d = context.read<ProfileProvider>().driver;
    _nameController.text = d?.name ?? '';
    _emailController.text = d?.email ?? '';
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _pickAvatar() async {
    final x = await ImagePicker().pickImage(
        source: ImageSource.gallery, imageQuality: 70, maxWidth: 800);
    if (x != null) setState(() => _avatar = File(x.path));
  }

  Future<void> _save() async {
    final p = context.read<ProfileProvider>();
    final ok = await p.updateProfile(
      name: _nameController.text.trim(),
      email: _emailController.text.trim(),
      avatar: _avatar,
    );
    if (!mounted) return;
    if (ok) {
      // ProfileProvider has the freshly-reloaded driver; push it into
      // HomeProvider so the drawer header reflects the update immediately.
      context.read<HomeProvider>().setDriver(p.driver);
      AppSnackbar.success(context, 'profile.profile_updated'.tr());
      context.pop();
    } else {
      AppSnackbar.error(context, p.error ?? 'profile.update_failed'.tr());
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<ProfileProvider>();
    final avatarUrl = Helpers.imageUrl(p.driver?.avatar);
    return Scaffold(
      appBar: AppBar(title: Text('profile.edit_profile'.tr())),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Center(
            child: GestureDetector(
              onTap: _pickAvatar,
              child: CircleAvatar(
                radius: 48,
                backgroundColor: AppColors.primary.withValues(alpha: 0.12),
                backgroundImage: _avatar != null
                    ? FileImage(_avatar!)
                    : (avatarUrl?.isNotEmpty ?? false)
                        ? NetworkImage(avatarUrl!) as ImageProvider
                        : null,
                child: (_avatar == null && !(avatarUrl?.isNotEmpty ?? false))
                    ? const Icon(Icons.add_a_photo,
                        color: AppColors.primary, size: 28)
                    : null,
              ),
            ),
          ),
          const SizedBox(height: 24),
          CustomTextField(
            controller: _nameController,
            label: 'profile.name'.tr(),
            prefixIcon: Icons.person_outline,
          ),
          const SizedBox(height: 16),
          CustomTextField(
            controller: _emailController,
            label: 'profile.email'.tr(),
            prefixIcon: Icons.email_outlined,
            keyboardType: TextInputType.emailAddress,
          ),
          const SizedBox(height: 24),
          CustomButton(
            label: 'profile.save_changes'.tr(),
            loading: p.submitting,
            onPressed: _save,
          ),
        ],
      ),
    );
  }
}
