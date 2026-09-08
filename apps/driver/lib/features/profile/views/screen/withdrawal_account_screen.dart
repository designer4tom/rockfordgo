import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/utils/app_snackbar.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../../core/widgets/custom_textfield.dart';
import '../../provider/profile_provider.dart';

/// Payout/withdrawal account — collected here (not during registration).
class WithdrawalAccountScreen extends StatefulWidget {
  const WithdrawalAccountScreen({super.key});

  @override
  State<WithdrawalAccountScreen> createState() =>
      _WithdrawalAccountScreenState();
}

class _WithdrawalAccountScreenState extends State<WithdrawalAccountScreen> {
  final _formKey = GlobalKey<FormState>();
  final _accountController = TextEditingController();
  String _method = 'bkash';

  @override
  void dispose() {
    _accountController.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    final p = context.read<ProfileProvider>();
    final ok = await p.updateWithdrawalInfo(
      method: _method,
      account: _accountController.text.trim(),
    );
    if (!mounted) return;
    if (ok) {
      AppSnackbar.success(context, 'wallet.withdrawal_account_saved'.tr());
      context.pop();
    } else {
      AppSnackbar.error(context, p.error ?? 'common.save_failed'.tr());
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<ProfileProvider>();
    return Scaffold(
      appBar: AppBar(title: Text('wallet.withdrawal_account'.tr())),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Text('wallet.method'.tr(),
                style: const TextStyle(fontWeight: FontWeight.w600)),
            const SizedBox(height: 6),
            SegmentedButton<String>(
              segments: const [
                ButtonSegment(value: 'bkash', label: Text('bKash')),
                ButtonSegment(value: 'nagad', label: Text('Nagad')),
                ButtonSegment(value: 'bank', label: Text('Bank')),
              ],
              selected: {_method},
              onSelectionChanged: (s) => setState(() => _method = s.first),
            ),
            const SizedBox(height: 16),
            CustomTextField(
              controller: _accountController,
              label: _method == 'bank'
                  ? 'wallet.account_number'.tr()
                  : 'wallet.account_or_number'.tr(),
              hint: 'wallet.enter_account_number'.tr(),
              prefixIcon: Icons.account_balance_outlined,
              validator: (v) => (v == null || v.trim().isEmpty)
                  ? 'wallet.account_required'.tr()
                  : null,
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
