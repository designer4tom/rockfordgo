import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/utils/app_snackbar.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/widgets/custom_textfield.dart';
import '../../provider/complaint_provider.dart';

class CreateComplaintScreen extends StatefulWidget {
  const CreateComplaintScreen({super.key});

  @override
  State<CreateComplaintScreen> createState() => _CreateComplaintScreenState();
}

class _CreateComplaintScreenState extends State<CreateComplaintScreen> {
  final _formKey = GlobalKey<FormState>();
  final _descController = TextEditingController();
  final _orderController = TextEditingController();
  String _category = 'Payment';

  static const _categories = [
    'Payment',
    'Customer behavior',
    'App issue',
    'Order issue',
    'Other',
  ];

  @override
  void dispose() {
    _descController.dispose();
    _orderController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final p = context.read<ComplaintProvider>();
    final ok = await p.createComplaint(
      orderId: int.tryParse(_orderController.text.trim()),
      category: _category,
      description: _descController.text.trim(),
    );
    if (!mounted) return;
    if (ok) {
      AppSnackbar.success(context, 'complaint.submitted'.tr());
      context.pop();
    } else {
      AppSnackbar.error(context, p.error ?? 'complaint.submit_failed'.tr());
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<ComplaintProvider>();
    return Scaffold(
      appBar: AppBar(title: Text('complaint.new_complaint'.tr())),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            CustomTextField(
              controller: _orderController,
              label: 'complaint.order_id_optional'.tr(),
              hint: 'e.g. 1234',
              keyboardType: TextInputType.number,
            ),
            const SizedBox(height: 16),
            DropdownButtonFormField<String>(
              initialValue: _category,
              decoration: InputDecoration(labelText: 'complaint.category'.tr()),
              items: _categories
                  .map((c) => DropdownMenuItem(value: c, child: Text(c)))
                  .toList(),
              onChanged: (v) => setState(() => _category = v!),
            ),
            const SizedBox(height: 16),
            CustomTextField(
              controller: _descController,
              label: 'complaint.description'.tr(),
              hint: 'complaint.describe_your_issue'.tr(),
              maxLines: 5,
              validator: (v) => (v == null || v.trim().isEmpty)
                  ? 'complaint.description_required'.tr()
                  : null,
            ),
            const SizedBox(height: 24),
            CustomButton(
              label: 'complaint.submit'.tr(),
              loading: p.submitting,
              onPressed: _submit,
            ),
          ],
        ),
      ),
    );
  }
}
