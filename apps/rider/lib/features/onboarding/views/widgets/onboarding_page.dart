import 'package:flutter/material.dart';

import '../../provider/onboarding_provider.dart';

class OnboardingPage extends StatelessWidget {
  final OnboardingSlide slide;

  const OnboardingPage({super.key, required this.slide});

  @override
  Widget build(BuildContext context) {
    return Stack(
      fit: StackFit.expand,
      children: [
        Image.asset(
          slide.imageAsset,
          fit: BoxFit.cover,
          alignment: const Alignment(0, -0.92),
          errorBuilder: (context, error, stackTrace) => DecoratedBox(
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [Color(0xFFEEF4FF), Color(0xFFD7E5FF)],
              ),
            ),
            child: Center(
              child: Icon(
                slide.icon,
                size: 120,
                color: const Color(0xFF2F6BFF),
              ),
            ),
          ),
        ),
        DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                Colors.black.withValues(alpha: 0.18),
                Colors.transparent,
                Colors.black.withValues(alpha: 0.78),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
