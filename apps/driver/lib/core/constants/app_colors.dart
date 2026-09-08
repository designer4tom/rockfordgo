import 'package:flutter/material.dart';

/// Driver app theme — accent color amber/orange feel (different from customer app)
class AppColors {
 // static const Color primary = Color(0xFFD97706); // amber
  static const Color primaryDark = Color(0xFF92400E);
  // static const Color secondary = Color(0xFF1A56DB);
  static const Color online = Color(0xFF057A55); // green
  static const Color offline = Color(0xFF6B7280);


  static const Color primary = Color(0xFF8154DA);
  static const Color secondary = Color(0xFF8154DA);

  static const Color primaryColor = Color(0xFF8154DA);
  static const Color buttonTextColor = Color(0xFFFFFFFF);

  static const Color success = Color(0xFF057A55);
  static const Color warning = Color(0xFFC27803);
  static const Color danger = Color(0xFFC81E1E);

  static const Color background = Color(0xFFF9FAFB);
  static const Color surface = Color(0xFFFFFFFF);
  static const Color textPrimary = Color(0xFF111928);
  static const Color textSecondary = Color(0xFF6B7280);
  static const Color border = Color(0xFFE5E7EB);

  // ---- Drawer (Figma) palette ----
  // Drawer-scoped blue accent — intentionally separate from the app's amber
  // [primary] branding so the rest of the app is unaffected.
  static const Color drawerAccent = Color(0xFF2563EB); // blue
  static const Color drawerAccentSoft = Color(0xFFEAF1FF); // active row tint
  static const Color drawerHeaderGlow = Color(0xFFE6EEFF); // header glow blob
  static const Color drawerName = Color(0xFF111827); // driver name
  static const Color drawerSubtitle = Color(0xFF9CA3AF); // phone / labels
  static const Color drawerLabel = Color(0xFF1F2937); // menu text
  static const Color drawerIcon = Color(0xFF374151); // inactive menu icon
  static const Color drawerChevron = Color(0xFFCBD2DC); // trailing chevron
  static const Color drawerDivider = Color(0xFFF1F2F4); // group divider
  static const Color drawerCardShadow = Color(0x14101828); // soft card shadow

  // ---- Dark variants ----
  static const Color darkBackground = Color(0xFF111928);
  static const Color darkSurface = Color(0xFF1F2937);
  static const Color darkTextPrimary = Color(0xFFF9FAFB);
  static const Color darkTextSecondary = Color(0xFF9CA3AF);
  static const Color darkBorder = Color(0xFF374151);
}
