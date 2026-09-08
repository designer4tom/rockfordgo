import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/utils/app_snackbar.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/widgets/custom_textfield.dart';
import '../../provider/profile_provider.dart';

class EmergencyContactScreen extends StatefulWidget {
  const EmergencyContactScreen({super.key});

  @override
  State<EmergencyContactScreen> createState() => _EmergencyContactScreenState();
}

class _EmergencyContactScreenState extends State<EmergencyContactScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _relationController = TextEditingController();

  @override
  void initState() {
    super.initState();
    // Fetch the saved contact and prefill the form on open.
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      final p = context.read<ProfileProvider>();
      await p.loadEmergencyContact();
      if (!mounted) return;
      final c = p.emergencyContact;
      if (c != null) {
        _nameController.text = c.name ?? '';
        _phoneController.text = c.phone ?? '';
        _relationController.text = c.relationship ?? '';
      }
    });
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _relationController.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    final p = context.read<ProfileProvider>();
    final ok = await p.updateEmergencyContact(
      name: _nameController.text.trim(),
      phone: _phoneController.text.trim(),
      relation: _relationController.text.trim(),
    );
    if (!mounted) return;
    if (ok) {
      AppSnackbar.success(context, 'profile.emergency_contact_saved'.tr());
      context.pop();
    } else {
      AppSnackbar.error(context, p.error ?? 'common.save_failed'.tr());
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<ProfileProvider>();
    return Scaffold(
      appBar: AppBar(title: Text('profile.emergency_contact'.tr())),
      body: (p.loadingContact && p.emergencyContact == null)
          ? const Center(child: CircularProgressIndicator())
          : Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  CustomTextField(
                    controller: _nameController,
                    label: 'profile.contact_name'.tr(),
                    hint: 'profile.contact_name_hint'.tr(),
                    prefixIcon: Icons.person_outline,
                    validator: (v) => Validators.required(v, field: 'Name'),
                  ),
                  const SizedBox(height: 16),
                  CustomTextField(
                    controller: _phoneController,
                    label: 'common.phone'.tr(),
                    hint: 'profile.contact_phone_hint'.tr(),
                    prefixIcon: Icons.phone_outlined,
                    keyboardType: TextInputType.phone,
                    validator: Validators.phone,
                  ),
                  const SizedBox(height: 16),
                  CustomTextField(
                    controller: _relationController,
                    label: 'profile.relation_optional'.tr(),
                    hint: 'profile.relation_hint'.tr(),
                    prefixIcon: Icons.group_outlined,
                  ),
                  const SizedBox(height: 24),
                  CustomButton(
                    label: 'common.save'.tr(),
                    loading: p.submitting,
                    onPressed: _save,
                  ),
                ],
              ),
            ),
    );
  }
}
