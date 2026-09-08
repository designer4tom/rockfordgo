import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/widgets/custom_textfield.dart';
import '../../model/auth_response_model.dart';
import '../../provider/auth_provider.dart';
import '../../provider/registration_provider.dart';
import '../widgets/document_upload_field.dart';
import '../widgets/registration_step.dart';

class RegistrationScreen extends StatefulWidget {
  const RegistrationScreen({super.key});

  @override
  State<RegistrationScreen> createState() => _RegistrationScreenState();
}

class _RegistrationScreenState extends State<RegistrationScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<RegistrationProvider>().loadVehicleCategories();
    });
  }

  Future<void> _pickExpiry(
      RegistrationProvider p, String field, DateTime? current) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: current ?? now.add(const Duration(days: 365)),
      firstDate: now,
      lastDate: now.add(const Duration(days: 365 * 20)),
    );
    if (picked != null) p.setExpiry(field, picked);
  }

  Future<void> _handleNextOrSubmit(RegistrationProvider p) async {
    if (p.currentStep < RegistrationProvider.lastStep) {
      p.nextStep();
      if (p.error != null && mounted) AppSnackbar.error(context, p.error!);
      return;
    }
    final result = await p.submitRegistration();
    if (!mounted) return;
    if (result == null) {
      AppSnackbar.error(context, p.error ?? 'registration.failed'.tr());
      return;
    }
    // Registration done → awaiting admin approval.
    context.read<AuthProvider>().markRegistrationCompleted();
    if (result == AuthResult.approved) {
      context.go(RouteNames.home);
    } else {
      context.go(RouteNames.pendingApproval);
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<RegistrationProvider>();
    return Scaffold(
      appBar: AppBar(title: Text('registration.title'.tr())),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
              child: RegistrationStepIndicator(
                currentStep: p.currentStep,
                totalSteps: 3,
                labels: [
                  'registration.step_personal'.tr(),
                  'registration.step_documents'.tr(),
                  'registration.step_vehicle'.tr(),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: _buildStep(p),
              ),
            ),
            _buildBottomBar(p),
          ],
        ),
      ),
    );
  }

  Widget _buildStep(RegistrationProvider p) {
    switch (p.currentStep) {
      case 0:
        return _personalStep(p);
      case 1:
        return _documentsStep(p);
      case 2:
        return _vehicleStep(p);
      default:
        return const SizedBox.shrink();
    }
  }

  // ---- Step 0: Personal ----
  Widget _personalStep(RegistrationProvider p) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _StepTitle('registration.personal_info'.tr()),
        CustomTextField(
          label: 'registration.full_name'.tr(),
          hint: 'registration.full_name_hint'.tr(),
          prefixIcon: Icons.person_outline,
          onChanged: (v) => p.name = v,
        ),
        const SizedBox(height: 16),
        CustomTextField(
          label: 'registration.email_optional'.tr(),
          hint: 'you@example.com',
          prefixIcon: Icons.email_outlined,
          keyboardType: TextInputType.emailAddress,
          onChanged: (v) => p.email = v,
        ),
      ],
    );
  }

  // ---- Step 1: Documents ----
  Widget _documentsStep(RegistrationProvider p) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _StepTitle('registration.documents'.tr()),
        DocumentUploadField(
          label: 'documents.nid_front'.tr(),
          file: p.documents[RegistrationProvider.docNidFront],
          onPick: (s) => p.pickImage(RegistrationProvider.docNidFront, s),
          onRemove: () => p.removeImage(RegistrationProvider.docNidFront),
        ),
        DocumentUploadField(
          label: 'documents.nid_back'.tr(),
          file: p.documents[RegistrationProvider.docNidBack],
          onPick: (s) => p.pickImage(RegistrationProvider.docNidBack, s),
          onRemove: () => p.removeImage(RegistrationProvider.docNidBack),
        ),
        DocumentUploadField(
          label: 'documents.driving_license'.tr(),
          file: p.documents[RegistrationProvider.docLicenseFront],
          onPick: (s) => p.pickImage(RegistrationProvider.docLicenseFront, s),
          onRemove: () => p.removeImage(RegistrationProvider.docLicenseFront),
          hasExpiry: true,
          expiryDate: p.licenseExpiry,
          onExpiryPick: () => _pickExpiry(
              p, RegistrationProvider.docLicenseFront, p.licenseExpiry),
        ),
        DocumentUploadField(
          label: 'documents.vehicle_registration'.tr(),
          file: p.documents[RegistrationProvider.docVehicleReg],
          onPick: (s) => p.pickImage(RegistrationProvider.docVehicleReg, s),
          onRemove: () => p.removeImage(RegistrationProvider.docVehicleReg),
          hasExpiry: true,
          expiryDate: p.vehicleRegExpiry,
          onExpiryPick: () => _pickExpiry(
              p, RegistrationProvider.docVehicleReg, p.vehicleRegExpiry),
        ),
        DocumentUploadField(
          label: 'documents.insurance_optional'.tr(),
          required: false,
          file: p.documents[RegistrationProvider.docInsurance],
          onPick: (s) => p.pickImage(RegistrationProvider.docInsurance, s),
          onRemove: () => p.removeImage(RegistrationProvider.docInsurance),
          hasExpiry: true,
          expiryDate: p.insuranceExpiry,
          onExpiryPick: () => _pickExpiry(
              p, RegistrationProvider.docInsurance, p.insuranceExpiry),
        ),
        DocumentUploadField(
          label: 'documents.vehicle_front_photo'.tr(),
          file: p.documents[RegistrationProvider.docVehicleFront],
          onPick: (s) => p.pickImage(RegistrationProvider.docVehicleFront, s),
          onRemove: () => p.removeImage(RegistrationProvider.docVehicleFront),
        ),
        DocumentUploadField(
          label: 'documents.vehicle_back_photo'.tr(),
          file: p.documents[RegistrationProvider.docVehicleBack],
          onPick: (s) => p.pickImage(RegistrationProvider.docVehicleBack, s),
          onRemove: () => p.removeImage(RegistrationProvider.docVehicleBack),
        ),
      ],
    );
  }

  // ---- Step 2: Vehicle ----
  Widget _vehicleStep(RegistrationProvider p) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _StepTitle('registration.vehicle_info'.tr()),
        if (p.loading)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 8),
            child: LinearProgressIndicator(),
          ),
        DropdownButtonFormField<int>(
          initialValue: p.vehicleCategoryId,
          decoration: InputDecoration(
              labelText: 'registration.vehicle_category'.tr()),
          items: p.categories
              .map((c) => DropdownMenuItem(value: c.id, child: Text(c.name)))
              .toList(),
          onChanged: p.setVehicleCategory,
        ),
        const SizedBox(height: 16),
        CustomTextField(
          label: 'registration.make'.tr(),
          hint: 'e.g. Toyota',
          onChanged: (v) => p.vehicleMake = v,
        ),
        const SizedBox(height: 16),
        CustomTextField(
          label: 'registration.model'.tr(),
          hint: 'e.g. Axio',
          onChanged: (v) => p.vehicleModel = v,
        ),
        const SizedBox(height: 16),
        CustomTextField(
          label: 'registration.year'.tr(),
          hint: 'e.g. 2019',
          keyboardType: TextInputType.number,
          onChanged: (v) => p.vehicleYear = v,
        ),
        const SizedBox(height: 16),
        CustomTextField(
          label: 'registration.color'.tr(),
          hint: 'e.g. White',
          onChanged: (v) => p.vehicleColor = v,
        ),
        const SizedBox(height: 16),
        CustomTextField(
          label: 'registration.registration_number'.tr(),
          hint: 'e.g. DHAKA-METRO-GA-12-3456',
          onChanged: (v) => p.vehicleRegNumber = v,
        ),
      ],
    );
  }

  Widget _buildBottomBar(RegistrationProvider p) {
    final isLast = p.currentStep == RegistrationProvider.lastStep;
    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            if (p.currentStep > 0)
              Expanded(
                child: CustomButton(
                  label: 'common.back'.tr(),
                  outlined: true,
                  onPressed: p.submitting ? null : p.prevStep,
                ),
              ),
            if (p.currentStep > 0) const SizedBox(width: 12),
            Expanded(
              child: CustomButton(
                label: isLast ? 'common.submit'.tr() : 'common.next'.tr(),
                loading: p.submitting,
                onPressed: () => _handleNextOrSubmit(p),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _StepTitle extends StatelessWidget {
  final String text;
  const _StepTitle(this.text);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Text(
        text,
        style: TextStyle(
          fontSize: 18,
          fontWeight: FontWeight.bold,
          color: Theme.of(context).colorScheme.onSurface,
        ),
      ),
    );
  }
}
