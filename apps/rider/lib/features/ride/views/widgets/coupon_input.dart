import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../model/coupon_model.dart';

class CouponInput extends StatefulWidget {
  final Future<bool> Function(String code) onApply;
  final CouponModel? appliedCoupon;
  final VoidCallback onRemove;

  const CouponInput({
    super.key,
    required this.onApply,
    required this.appliedCoupon,
    required this.onRemove,
  });

  @override
  State<CouponInput> createState() => _CouponInputState();
}

class _CouponInputState extends State<CouponInput> {
  final _controller = TextEditingController();
  bool _applying = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _apply() async {
    if (_controller.text.trim().isEmpty) return;
    setState(() => _applying = true);
    await widget.onApply(_controller.text.trim());
    if (mounted) setState(() => _applying = false);
  }

  @override
  Widget build(BuildContext context) {
    final applied = widget.appliedCoupon;
    if (applied != null) {
      return Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: AppColors.success.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: AppColors.success),
        ),
        child: Row(
          children: [
            const Icon(Icons.check_circle, color: AppColors.success, size: 20),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                'ride.coupon_applied_code'.tr(namedArgs: {'code': applied.code}),
                style: const TextStyle(fontWeight: FontWeight.w500),
              ),
            ),
            TextButton(
              onPressed: widget.onRemove,
              child: Text('remove'.tr(),
                  style: const TextStyle(color: AppColors.danger)),
            ),
          ],
        ),
      );
    }

    return SafeArea(
      top: false,
      child: Row(
        children: [
          Expanded(
            child: TextField(
              controller: _controller,
              textCapitalization: TextCapitalization.characters,
              decoration: InputDecoration(
                hintText: 'coupon_code'.tr(),
                prefixIcon: const Icon(Icons.local_offer_outlined),
              ),
            ),
          ),
          const SizedBox(width: 8),
          SizedBox(
            height: 52,
            child: ElevatedButton(
              onPressed: _applying ? null : _apply,
              child: _applying
                  ? const SizedBox(
                      height: 18,
                      width: 18,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white),
                    )
                  : Text('apply'.tr()),
            ),
          ),
        ],
      ),
    );
  }
}
