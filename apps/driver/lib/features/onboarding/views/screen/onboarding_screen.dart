import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_constants.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/widgets/custom_button.dart';

class _Slide {
  final String image;
  final String title;
  final String subtitle;
  const _Slide(this.image, this.title, this.subtitle);
}

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final _controller = PageController();
  int _index = 0;

  static const _slides = [
    _Slide('assets/images/onboarding_1.png', 'onboarding.slide1_title',
        'onboarding.slide1_subtitle'),
    _Slide('assets/images/onboarding_2.png', 'onboarding.slide2_title',
        'onboarding.slide2_subtitle'),
    _Slide('assets/images/onboarding_3.png', 'onboarding.slide3_title',
        'onboarding.slide3_subtitle'),
  ];

  bool get _isLast => _index == _slides.length - 1;

  Future<void> _finish() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(AppConstants.onboardingKey, true);
    if (mounted) context.go(RouteNames.phoneEntry);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        fit: StackFit.expand,
        children: [
          PageView.builder(
            controller: _controller,
            itemCount: _slides.length,
            onPageChanged: (i) => setState(() => _index = i),
            itemBuilder: (_, i) => Image.asset(
              _slides[i].image,
              fit: BoxFit.cover,
            ),
          ),
          Positioned.fill(
            child: IgnorePointer(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.black.withValues(alpha: 0.15),
                      Colors.transparent,
                      Colors.black.withValues(alpha: 0.75),
                    ],
                    stops: const [0.0, 0.45, 1.0],
                  ),
                ),
              ),
            ),
          ),
          SafeArea(
            child: Column(
              children: [
                Align(
                  alignment: Alignment.centerRight,
                  child: TextButton(
                    onPressed: _finish,
                    child: Text(
                      'onboarding.skip'.tr(),
                      style: const TextStyle(color: Colors.white),
                    ),
                  ),
                ),
                const Spacer(),
                // Padding(
                //   padding: const EdgeInsets.symmetric(horizontal: 32),
                //   child: Text(
                //     _slides[_index].title.tr(),
                //     textAlign: TextAlign.center,
                //     style: const TextStyle(
                //       fontSize: 24,
                //       fontWeight: FontWeight.bold,
                //       color: Colors.white,
                //     ),
                //   ),
                // ),
                const SizedBox(height: 12),
                // Padding(
                //   padding: const EdgeInsets.symmetric(horizontal: 32),
                //   child: Text(
                //     _slides[_index].subtitle.tr(),
                //     textAlign: TextAlign.center,
                //     style: TextStyle(
                //       fontSize: 15,
                //       color: Colors.white.withValues(alpha: 0.85),
                //       height: 1.5,
                //     ),
                //   ),
                // ),
                const SizedBox(height: 24),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: List.generate(
                    _slides.length,
                    (i) => AnimatedContainer(
                      duration: const Duration(milliseconds: 250),
                      margin: const EdgeInsets.symmetric(horizontal: 4),
                      height: 8,
                      width: i == _index ? 24 : 8,
                      decoration: BoxDecoration(
                        color: i == _index
                            ? AppColors.primary
                            : Colors.white.withValues(alpha: 0.5),
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.all(24),
                  child: CustomButton(
                    label: _isLast
                        ? 'onboarding.get_started'.tr()
                        : 'common.next'.tr(),
                    onPressed: () {
                      debugPrint('ONBOARD_DEBUG: next tapped, isLast=$_isLast');
                      if (_isLast) {
                        _finish();
                      } else {
                        _controller.nextPage(
                          duration: const Duration(milliseconds: 300),
                          curve: Curves.easeInOut,
                        );
                      }
                    },
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
