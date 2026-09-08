import 'package:cached_network_image/cached_network_image.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/widgets/empty_state.dart';
import '../../../../core/widgets/loading_widget.dart';
import '../../model/faq_model.dart';
import '../../provider/help_provider.dart';

/// Safety tips — loaded from `/safety-tips` (admin managed list).
class SafetyScreen extends StatefulWidget {
  const SafetyScreen({super.key});

  @override
  State<SafetyScreen> createState() => _SafetyScreenState();
}

class _SafetyScreenState extends State<SafetyScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => context.read<HelpProvider>().loadSafetyTips(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final help = context.watch<HelpProvider>();
    final tips = help.safetyTips;

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      appBar: AppBar(title: Text('safety.title'.tr())),
      body: help.isLoadingSafetyTips
          ? const LoadingWidget()
          : tips.isEmpty
              ? EmptyState(
                  icon: Icons.shield_outlined,
                  title: 'safety.empty'.tr(),
                )
              : RefreshIndicator(
                  onRefresh: () => help.loadSafetyTips(),
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
                    children: [
                      // Header banner
                      Container(
                        padding: const EdgeInsets.all(16),
                        margin: const EdgeInsets.only(bottom: 16),
                        decoration: BoxDecoration(
                          color: AppColors.primary.withValues(alpha: 0.08),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.shield_outlined,
                                color: AppColors.primary, size: 28),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Text('safety.header'.tr(),
                                  style: TextStyle(
                                      fontWeight: FontWeight.w600,
                                      color: theme.colorScheme.onSurface)),
                            ),
                          ],
                        ),
                      ),
                      ...tips.map((t) => _tipCard(theme, t)),
                    ],
                  ),
                ),
    );
  }

  Widget _tipCard(ThemeData theme, SafetyTipModel tip) {
    final hasIcon = tip.icon != null && tip.icon!.isNotEmpty;
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: theme.dividerColor),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 44,
            height: 44,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.12),
              shape: BoxShape.circle,
            ),
            child: hasIcon
                ? ClipOval(
                    child: CachedNetworkImage(
                      imageUrl: tip.icon!,
                      width: 24,
                      height: 24,
                      fit: BoxFit.contain,
                      errorWidget: (c, u, e) => const Icon(
                          Icons.verified_user_outlined,
                          color: AppColors.primary, size: 22),
                    ),
                  )
                : const Icon(Icons.verified_user_outlined,
                    color: AppColors.primary, size: 22),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(tip.title,
                    style: TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 15,
                        color: theme.colorScheme.onSurface)),
                if (tip.description.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(tip.description,
                      style: const TextStyle(
                          fontSize: 13,
                          height: 1.4,
                          color: AppColors.textSecondary)),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}
