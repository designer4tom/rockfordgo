import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:smooth_page_indicator/smooth_page_indicator.dart';

import '../../../../core/routing/route_names.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../provider/onboarding_provider.dart';
import '../widgets/onboarding_page.dart';

class OnboardingScreen extends StatelessWidget {
  const OnboardingScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => OnboardingProvider(),
      child: const _OnboardingView(),
    );
  }
}

class _OnboardingView extends StatelessWidget {
  const _OnboardingView();

  Future<void> _finish(BuildContext context) async {
    final provider = context.read<OnboardingProvider>();
    await provider.complete();
    if (context.mounted) context.go(RouteNames.phoneEntry);
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<OnboardingProvider>();

    return Scaffold(
      body: Stack(
        children: [
          Positioned.fill(
            child: PageView.builder(
              controller: provider.pageController,
              onPageChanged: provider.onPageChanged,
              itemCount: provider.slides.length,
              itemBuilder: (context, index) =>
                  OnboardingPage(slide: provider.slides[index]),
            ),
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 10),
              child: Column(
                children: [
                  Align(
                    alignment: Alignment.centerRight,
                    child: TextButton(
                      onPressed: () => _finish(context),
                      style: TextButton.styleFrom(
                        foregroundColor: Colors.white,
                        backgroundColor: Colors.black.withValues(alpha: 0.22),
                        padding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 10,
                        ),
                      ),
                      child: Text('common.skip'.tr()),
                    ),
                  ),
                  const Spacer(),
                  SmoothPageIndicator(
                    controller: provider.pageController,
                    count: provider.slides.length,
                    effect: ExpandingDotsEffect(
                      activeDotColor: Colors.white,
                      dotColor: Colors.white.withValues(alpha: 0.4),
                      dotHeight: 8,
                      dotWidth: 8,
                      expansionFactor: 3,
                    ),
                  ),
                  const SizedBox(height: 20),
                  CustomButton(
                    text: provider.isLastPage
                        ? 'onboarding.get_started'.tr()
                        : 'common.next'.tr(),
                    onPressed: () {
                      if (provider.isLastPage) {
                        _finish(context);
                      } else {
                        provider.next();
                      }
                    },
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
