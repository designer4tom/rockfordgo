import 'package:flutter/material.dart';

import '../constants/app_colors.dart';
import '../theme/app_dimensions.dart';

/// Full-width primary button with a left label and optional right-aligned
/// trailing (e.g. price + arrow). Matches the design's Confirm button.
class PrimaryActionButton extends StatelessWidget {
  final String label;
  final Widget? trailing;
  final VoidCallback? onPressed;
  final bool isLoading;

  const PrimaryActionButton({
    super.key,
    required this.label,
    this.trailing,
    this.onPressed,
    this.isLoading = false,
  });

  @override
  Widget build(BuildContext context) {
    final enabled = onPressed != null && !isLoading;
    return Column(
      children: [
        SafeArea(
          child: SizedBox(
            height: AppDimensions.buttonHeight,
            width: double.infinity,
            child: ElevatedButton(
              onPressed: enabled ? onPressed : null,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                disabledBackgroundColor: AppColors.primary.withValues(alpha: 0.4),
                disabledForegroundColor: Colors.white70,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(AppDimensions.buttonRadius),
                ),
              ),
              child: isLoading
                  ? const SizedBox(
                      height: 22,
                      width: 22,
                      child: CircularProgressIndicator(
                          strokeWidth: 2.5, color: Colors.white),
                    )
                  : Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16.0),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          Text(label,
                              style: const TextStyle(
                                  fontSize: 16, fontWeight: FontWeight.w600)),
                          // const Spacer(),
                          // ?trailing,
                        ],
                      ),
                  ),
            ),
          ),
        ),
      ],
    );
  }
}
