import 'package:flutter/material.dart';
import '../constants/app_colors.dart';

class AppTextStyles {
  static const String fontFamily = 'Roboto';

  static const TextStyle h1 = TextStyle(
    fontSize: 28,
    fontWeight: FontWeight.bold,
    color: AppColors.textPrimary,
  );

  static const TextStyle h2 = TextStyle(
    fontSize: 22,
    fontWeight: FontWeight.bold,
    color: AppColors.textPrimary,
  );

  static const TextStyle h3 = TextStyle(
    fontSize: 18,
    fontWeight: FontWeight.w600,
    color: AppColors.textPrimary,
  );

  static const TextStyle title = TextStyle(
    fontSize: 16,
    fontWeight: FontWeight.w600,
    color: AppColors.textPrimary,
  );

  static const TextStyle body = TextStyle(
    fontSize: 14,
    fontWeight: FontWeight.normal,
    color: AppColors.textPrimary,
  );

  static const TextStyle bodySecondary = TextStyle(
    fontSize: 14,
    fontWeight: FontWeight.normal,
    color: AppColors.textSecondary,
  );

  static const TextStyle caption = TextStyle(
    fontSize: 12,
    fontWeight: FontWeight.normal,
    color: AppColors.textSecondary,
  );

  static const TextStyle button = TextStyle(
    fontSize: 16,
    fontWeight: FontWeight.w600,
    color: Colors.white,
  );

  // ── Design-system named styles ──
  // Color omitted on heading/cardTitle/price so the ambient theme (light/dark)
  // drives it; widgets can still override per use.

  /// Screen title in app bars.
  static const TextStyle heading =
      TextStyle(fontSize: 18, fontWeight: FontWeight.bold);

  /// Card / list-item title.
  static const TextStyle cardTitle =
      TextStyle(fontSize: 16, fontWeight: FontWeight.w600);

  /// Muted secondary line under a title.
  static const TextStyle cardSubtitle =
      TextStyle(fontSize: 14, color: AppColors.textSecondary);

  /// Bold price / amount.
  static const TextStyle price =
      TextStyle(fontSize: 16, fontWeight: FontWeight.bold);

  /// Small accent label (e.g. "Pickup location", "See all").
  static const TextStyle label = TextStyle(
      fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.primary);
}
