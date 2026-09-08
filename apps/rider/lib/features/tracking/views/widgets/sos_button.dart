import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';

class SosButton extends StatelessWidget {
  final Future<void> Function() onTrigger;

  const SosButton({super.key, required this.onTrigger});

  Future<void> _confirm(BuildContext context) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('tracking.sos_title'.tr()),
        content: Text('tracking.sos_msg'.tr()),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text('common.no'.tr()),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
            onPressed: () => Navigator.pop(ctx, true),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 8.0),
              child: Text('tracking.sos_send'.tr()),
            ),
          ),
        ],
      ),
    );
    if (ok == true) await onTrigger();
  }

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.danger,
      shape: const CircleBorder(),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: () => _confirm(context),
        child: const Padding(
          padding: EdgeInsets.all(12),
          child: Text(
            'SOS',
            style: TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.bold,
              fontSize: 13,
            ),
          ),
        ),
      ),
    );
  }
}
