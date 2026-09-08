import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../provider/tracking_provider.dart';

class RatingScreen extends StatefulWidget {
  final int orderId;

  const RatingScreen({super.key, required this.orderId});

  @override
  State<RatingScreen> createState() => _RatingScreenState();
}

class _RatingScreenState extends State<RatingScreen> {
  int _rating = 0;
  final _comment = TextEditingController();
  final _selectedTags = <String>{};
  bool _submitting = false;

  static const _tags = {
    'সময়মতো এসেছে': 'tracking.tag_on_time',
    'ভালো ব্যবহার': 'tracking.tag_polite',
    'গাড়ি পরিষ্কার': 'tracking.tag_clean',
    'নিরাপদ ড্রাইভিং': 'tracking.tag_safe',
  };

  @override
  void dispose() {
    _comment.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_rating == 0) {
      SnackbarHelper.showInfo(context, 'tracking.please_rate'.tr());
      return;
    }
    setState(() => _submitting = true);
    final ok = await context.read<TrackingProvider>().rate(
          _rating,
          _comment.text.trim().isEmpty ? null : _comment.text.trim(),
          _selectedTags.toList(),
        );
    if (!mounted) return;
    if (ok) {
      SnackbarHelper.showSuccess(context, 'tracking.rate_thanks'.tr());
      context.go(RouteNames.home);
    } else {
      setState(() => _submitting = false);
      SnackbarHelper.showError(context, 'tracking.rate_failed'.tr());
    }
  }

  @override
  Widget build(BuildContext context) {
    final driver = context.read<TrackingProvider>().tracking?.driver;
    final avatarUrl = Helpers.imageUrl(driver?.avatar);

    return Scaffold(
      appBar: AppBar(
        title: Text('tracking.rate_driver'.tr()),
        actions: [
          TextButton(
            onPressed: () => context.go(RouteNames.home),
            child: Text('common.skip'.tr()),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          Center(
            child: Column(
              children: [
                CircleAvatar(
                  radius: 36,
                  backgroundColor: Theme.of(context).scaffoldBackgroundColor,
                  backgroundImage:
                      avatarUrl != null ? NetworkImage(avatarUrl) : null,
                  child: avatarUrl == null
                      ? const Icon(Icons.person,
                          size: 40, color: AppColors.primary)
                      : null,
                ),
                const SizedBox(height: 12),
                Text(
                  driver?.name ?? 'tracking.your_driver'.tr(),
                  style: Theme.of(context).textTheme.titleLarge,
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(5, (i) {
              final filled = i < _rating;
              return IconButton(
                onPressed: () => setState(() => _rating = i + 1),
                icon: Icon(
                  filled ? Icons.star : Icons.star_border,
                  size: 40,
                  color: AppColors.accent,
                ),
              );
            }),
          ),
          const SizedBox(height: 16),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _tags.entries.map((entry) {
              final tag = entry.key;
              final selected = _selectedTags.contains(tag);
              return FilterChip(
                label: Text(entry.value.tr()),
                selected: selected,
                onSelected: (v) => setState(() {
                  if (v) {
                    _selectedTags.add(tag);
                  } else {
                    _selectedTags.remove(tag);
                  }
                }),
                selectedColor: AppColors.primary.withValues(alpha: 0.15),
              );
            }).toList(),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _comment,
            maxLines: 3,
            decoration: InputDecoration(
              hintText: 'tracking.comment_optional'.tr(),
              alignLabelWithHint: true,
            ),
          ),
          const SizedBox(height: 24),
          CustomButton(
            text: 'common.submit'.tr(),
            isLoading: _submitting,
            onPressed: _submit,
          ),
        ],
      ),
    );
  }
}
