import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/widgets/loading_widget.dart';
import '../../provider/referral_provider.dart';

class ReferralScreen extends StatefulWidget {
  const ReferralScreen({super.key});

  @override
  State<ReferralScreen> createState() => _ReferralScreenState();
}

class _ReferralScreenState extends State<ReferralScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => context.read<ReferralProvider>().loadReferral(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<ReferralProvider>();
    final referral = provider.referral;

    return Scaffold(
      appBar: AppBar(title: Text('referral.title'.tr())),
      body: provider.isLoading
          ? const LoadingWidget()
          : referral == null
              ? Center(child: Text('referral.not_found'.tr()))
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    Container(
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: AppColors.primary.withValues(alpha: 0.06),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.primary),
                      ),
                      child: Column(
                        children: [
                          Text('referral.your_code'.tr(),
                              style: const TextStyle(
                                  color: AppColors.textSecondary)),
                          const SizedBox(height: 8),
                          Text(
                            referral.code,
                            style: const TextStyle(
                              fontSize: 28,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 3,
                              color: AppColors.primary,
                            ),
                          ),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Expanded(
                                child: OutlinedButton.icon(
                                  onPressed: () {
                                    Clipboard.setData(
                                        ClipboardData(text: referral.code));
                                    SnackbarHelper.showSuccess(
                                        context, 'referral.copied'.tr());
                                  },
                                  icon: const Icon(Icons.copy, size: 18),
                                  label: Text('referral.copy'.tr()),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: () => SharePlus.instance.share(
                                    ShareParams(
                                      text: 'referral.share_text'.tr(
                                          namedArgs: {'code': referral.code}),
                                    ),
                                  ),
                                  icon: const Icon(Icons.share, size: 18),
                                  label: Text('referral.share'.tr()),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        _stat('referral.total_referred'.tr(),
                            '${referral.totalReferred}'),
                        const SizedBox(width: 12),
                        _stat(
                            'referral.total_earned'.tr(),
                            Helpers.currency(
                                double.tryParse(referral.totalEarned) ?? 0)),
                      ],
                    ),
                    if (referral.referredUsers.isNotEmpty) ...[
                      const SizedBox(height: 24),
                      Text('referral.referred_users'.tr(),
                          style: Theme.of(context).textTheme.titleMedium),
                      const SizedBox(height: 8),
                      ...referral.referredUsers.map((u) => ListTile(
                            leading: const Icon(Icons.person_outline),
                            title: Text(u),
                          )),
                    ],
                  ],
                ),
    );
  }

  Widget _stat(String label, String value) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Theme.of(context).cardColor,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: Theme.of(context).dividerColor),
        ),
        child: Column(
          children: [
            Text(value,
                style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: AppColors.primary)),
            const SizedBox(height: 4),
            Text(label,
                style: const TextStyle(
                    fontSize: 12, color: AppColors.textSecondary)),
          ],
        ),
      ),
    );
  }
}
