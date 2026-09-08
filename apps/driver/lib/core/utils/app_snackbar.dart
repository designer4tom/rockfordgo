import 'package:flutter/material.dart';

import '../constants/app_colors.dart';

class AppSnackbar {
  static void show(
    BuildContext context,
    String message, {
    Color? background,
    IconData? icon,
  }) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: Row(
            children: [
              if (icon != null) ...[
                Icon(icon, color: Colors.white, size: 20),
                const SizedBox(width: 10),
              ],
              Expanded(
                child: Text(
                  message,
                  style: const TextStyle(color: Colors.white),
                ),
              ),
            ],
          ),
          backgroundColor: background ?? AppColors.textPrimary,
          behavior: SnackBarBehavior.floating,
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      );
  }

  static void success(BuildContext context, String message) => show(
        context,
        message,
        background: AppColors.success,
        icon: Icons.check_circle_outline,
      );

  static void error(BuildContext context, String message) => show(
        context,
        message,
        background: AppColors.danger,
        icon: Icons.error_outline,
      );
}
