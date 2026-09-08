import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:readyride_customer/core/constants/app_colors.dart';

import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/widgets/custom_textfield.dart';
import '../../../auth/provider/auth_provider.dart';
import '../../../location/model/place_model.dart';
import '../../../location/provider/location_provider.dart';
import '../../provider/parcel_provider.dart';
import '../widgets/parcel_location_field.dart';
import '../widgets/parcel_step_indicator.dart';

class ParcelSenderScreen extends StatefulWidget {
  const ParcelSenderScreen({super.key});

  @override
  State<ParcelSenderScreen> createState() => _ParcelSenderScreenState();
}

class _ParcelSenderScreenState extends State<ParcelSenderScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _phone;
  PlaceModel? _pickup;

  @override
  void initState() {
    super.initState();
    final parcel = context.read<ParcelProvider>();
    final user = context.read<AuthProvider>().user;
    _name = TextEditingController(
        text: parcel.senderName.isNotEmpty ? parcel.senderName : user?.name);
    _phone = TextEditingController(
        text: parcel.senderPhone.isNotEmpty ? parcel.senderPhone : user?.phone);
    _pickup = parcel.pickup ?? context.read<LocationProvider>().currentPlace;
  }

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    super.dispose();
  }

  void _next() {
    if (!_formKey.currentState!.validate()) return;
    if (_pickup == null) {
      SnackbarHelper.showError(context, 'parcel.pickup_required'.tr());
      return;
    }
    final parcel = context.read<ParcelProvider>();
    parcel.setSenderInfo(
      name: _name.text.trim(),
      phone: _phone.text.trim(),
      pickup: _pickup,
    );
    parcel.currentStep = 1;
    context.push('/parcel-receiver');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('parcel.title'.tr())),
      body: Column(
        children: [
          const ParcelStepIndicator(currentStep: 0),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('parcel.sender_info'.tr(),
                        style: Theme.of(context).textTheme.titleLarge),
                    const SizedBox(height: 16),
                    CustomTextField(
                      controller: _name,
                      label: 'parcel.sender_name'.tr(),
                      validator: Validators.name,
                      prefixIcon: Icon(Icons.person,color: AppColors.primary,),
                    ),
                    const SizedBox(height: 16),
                    CustomTextField(
                      controller: _phone,
                      label: 'parcel.sender_phone'.tr(),
                      keyboardType: TextInputType.phone,
                      validator: Validators.phone,
                      prefixIcon: Icon(Icons.phone,color: AppColors.primary,),
                    ),
                    const SizedBox(height: 16),
                    ParcelLocationField(
                      label: 'parcel.pickup_location'.tr(),
                      place: _pickup,
                      onPicked: (p) => setState(() => _pickup = p),
                    ),
                  ],
                ),
              ),
            ),
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: CustomButton(text: 'common.next'.tr(), onPressed: _next),
            ),
          ),
        ],
      ),
    );
  }
}
