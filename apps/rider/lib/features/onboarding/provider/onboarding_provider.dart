import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../core/constants/app_constants.dart';

class OnboardingSlide {
  final IconData icon;
  final String imageAsset;
  final String title;
  final String description;

  const OnboardingSlide({
    required this.icon,
    required this.imageAsset,
    required this.title,
    required this.description,
  });
}

class OnboardingProvider extends ChangeNotifier {
  final PageController pageController = PageController();

  int _currentPage = 0;
  int get currentPage => _currentPage;

  // title/description hold i18n keys, resolved with .tr() in the page widget.
  final List<OnboardingSlide> slides = const [
    OnboardingSlide(
      icon: Icons.directions_car_filled_rounded,
      imageAsset: 'assets/images/onboarding_1.png',
      title: 'onboarding.slide1_title',
      description: 'onboarding.slide1_desc',
    ),
    OnboardingSlide(
      icon: Icons.local_shipping_rounded,
      imageAsset: 'assets/images/onboarding_2.png',
      title: 'onboarding.slide2_title',
      description: 'onboarding.slide2_desc',
    ),
    OnboardingSlide(
      icon: Icons.verified_user_rounded,
      imageAsset: 'assets/images/onboarding_3.png',
      title: 'onboarding.slide3_title',
      description: 'onboarding.slide3_desc',
    ),
  ];

  bool get isLastPage => _currentPage == slides.length - 1;

  void onPageChanged(int index) {
    _currentPage = index;
    notifyListeners();
  }

  void next() {
    if (!isLastPage) {
      pageController.nextPage(
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeOut,
      );
    }
  }

  Future<void> complete() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(AppConstants.onboardingKey, true);
  }

  @override
  void dispose() {
    pageController.dispose();
    super.dispose();
  }
}
