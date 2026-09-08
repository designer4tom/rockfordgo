import 'package:flutter/widgets.dart';

/// App-wide spacing / sizing tokens. Use these everywhere instead of magic
/// numbers so every screen shares the same rhythm (8/12/16/20 scale).
class AppDimensions {
  AppDimensions._();

  // Spacing scale
  static const double xs = 4;
  static const double sm = 8;
  static const double md = 12;
  static const double lg = 16;
  static const double xl = 20;
  static const double xxl = 24;

  // Screen
  static const double screenPadding = 16;

  // Cards
  static const double cardRadius = 16;
  static const double cardPadding = 16;
  static const double cardGap = 12;

  // Inputs
  static const double inputRadius = 12;

  // Buttons
  static const double buttonRadius = 14;
  static const double buttonHeight = 56;

  // Common edge insets
  static const EdgeInsets screenInset = EdgeInsets.all(screenPadding);
  static const EdgeInsets cardInset = EdgeInsets.all(cardPadding);
}
