import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/custom_button.dart';

/// Confirms collection of the COD amount (product price only) from the
/// receiver — the delivery charge is billed to the sender's wallet.
class CodCollectionSheet extends StatefulWidget {
  final double amount;

  /// Returns null on success, or an error message to show (e.g. the server's
  /// "collected less than the product price" 422 message).
  final Future<String?> Function() onCollected;

  const CodCollectionSheet({
    super.key,
    required this.amount,
    required this.onCollected,
  });

  @override
  State<CodCollectionSheet> createState() => _CodCollectionSheetState();
}

class _CodCollectionSheetState extends State<CodCollectionSheet> {
  bool _loading = false;
  String? _error;

  Future<void> _confirm() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final err = await widget.onCollected();
    if (!mounted) return;
    setState(() => _loading = false);
    if (err == null) {
      Navigator.pop(context, true);
    } else {
      setState(() => _error = err);
    }
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: Padding(
      padding: const EdgeInsets.all(20),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text('parcel.collect_cod'.tr(),
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: AppColors.warning.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Column(
              children: [
                Text('parcel.collect_from_receiver'.tr(),
                    style: TextStyle(color: Theme.of(context).hintColor)),
                const SizedBox(height: 6),
                Text(
                  Helpers.money(widget.amount),
                  style: const TextStyle(
                    fontSize: 32,
                    fontWeight: FontWeight.bold,
                    color: AppColors.warning,
                  ),
                ),
                const SizedBox(height: 4),
                Text('parcel.product_price_only'.tr(),
                    style: TextStyle(
                        fontSize: 12, color: Theme.of(context).hintColor)),
              ],
            ),
          ),
          if (_error != null) ...[
            const SizedBox(height: 12),
            Text(_error!, style: const TextStyle(color: AppColors.danger)),
          ],
          const SizedBox(height: 20),
          CustomButton(
            label: 'parcel.cod_collected'.tr(),
            icon: Icons.check,
            loading: _loading,
            onPressed: _confirm,
          ),
        ],
      ),
      ),
    );
  }
}
