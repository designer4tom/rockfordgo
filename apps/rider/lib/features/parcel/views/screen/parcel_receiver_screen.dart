import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:readyride_customer/core/constants/app_colors.dart';

import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/widgets/custom_textfield.dart';
import '../../../location/model/place_model.dart';
import '../../provider/parcel_provider.dart';
import '../widgets/parcel_location_field.dart';
import '../widgets/parcel_step_indicator.dart';

class ParcelReceiverScreen extends StatefulWidget {
  const ParcelReceiverScreen({super.key});

  @override
  State<ParcelReceiverScreen> createState() => _ParcelReceiverScreenState();
}

class _ParcelReceiverScreenState extends State<ParcelReceiverScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _phone;
  PlaceModel? _drop;

  @override
  void initState() {
    super.initState();
    final parcel = context.read<ParcelProvider>();
    _name = TextEditingController(text: parcel.receiverName);
    _phone = TextEditingController(text: parcel.receiverPhone);
    _drop = parcel.drop;
  }

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    super.dispose();
  }

  void _next() {
    if (!_formKey.currentState!.validate()) return;
    if (_drop == null) {
      SnackbarHelper.showError(context, 'parcel.drop_required'.tr());
      return;
    }
    final parcel = context.read<ParcelProvider>();
    parcel.setReceiverInfo(
      name: _name.text.trim(),
      phone: _phone.text.trim(),
      drop: _drop,
    );
    parcel.currentStep = 2;
    context.push('/parcel-details');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('parcel.title'.tr())),
      body: Column(
        children: [
          const ParcelStepIndicator(currentStep: 1),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('parcel.receiver_info'.tr(),
                        style: Theme.of(context).textTheme.titleLarge),
                    const SizedBox(height: 16),
                    CustomTextField(
                      controller: _name,
                      label: 'parcel.receiver_name'.tr(),
                      validator: Validators.name,
                      prefixIcon: Icon(Icons.person,color: AppColors.primary,),
                    ),
                    const SizedBox(height: 16),
                    CustomTextField(
                      controller: _phone,
                      label: 'parcel.receiver_phone'.tr(),
                      keyboardType: TextInputType.phone,
                      validator: Validators.phone,
                      prefixIcon: Icon(Icons.phone,color: AppColors.primary,),
                    ),
                    const SizedBox(height: 16),
                    ParcelLocationField(
                      label: 'parcel.drop_location'.tr(),
                      place: _drop,
                      onPicked: (p) => setState(() => _drop = p),
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
