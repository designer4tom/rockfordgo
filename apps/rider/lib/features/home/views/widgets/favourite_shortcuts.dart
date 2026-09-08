import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';

class FavouriteShortcuts extends StatelessWidget {
  final VoidCallback? onHome;
  final VoidCallback? onWork;

  const FavouriteShortcuts({super.key, this.onHome, this.onWork});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        _chip(context, icon: Icons.home_outlined, label: 'home.home_location'.tr(), onTap: onHome),
        const SizedBox(width: 12),
        _chip(context, icon: Icons.work_outline, label: 'home.office'.tr(), onTap: onWork),
      ],
    );
  }

  Widget _chip(
    BuildContext context, {
    required IconData icon,
    required String label,
    VoidCallback? onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Theme.of(context).dividerColor),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 18, color: AppColors.primary),
            const SizedBox(width: 6),
            Text(label, style: const TextStyle(fontSize: 13)),
          ],
        ),
      ),
    );
  }
}
