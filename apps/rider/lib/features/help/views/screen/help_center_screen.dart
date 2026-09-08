import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/services/config_service.dart';
import '../../provider/help_provider.dart';

class HelpCenterScreen extends StatefulWidget {
  const HelpCenterScreen({super.key});

  @override
  State<HelpCenterScreen> createState() => _HelpCenterScreenState();
}

class _HelpCenterScreenState extends State<HelpCenterScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => context.read<HelpProvider>().loadFaqs(),
    );
  }

  Future<void> _launch(String uri) async {
    final u = Uri.parse(uri);
    if (await canLaunchUrl(u)) {
      await launchUrl(u, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final help = context.watch<HelpProvider>();
    final cfg = ConfigService.getCached();

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      appBar: AppBar(title: Text('help.title'.tr())),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // ── Contact ──
          _sectionTitle(theme, 'help.contact_us'.tr()),
          const SizedBox(height: 8),
          if (cfg.supportPhone.isNotEmpty)
            _contactTile(theme, Icons.phone_outlined, 'help.call_us'.tr(),
                cfg.supportPhone, () => _launch('tel:${cfg.supportPhone}')),
          if (cfg.supportPhone.isNotEmpty)
            _contactTile(
                theme,
                Icons.chat_outlined,
                'help.whatsapp'.tr(),
                cfg.supportPhone,
                () => _launch(
                    'https://wa.me/${cfg.supportPhone.replaceAll(RegExp(r'[^0-9]'), '')}')),
          if (cfg.supportEmail.isNotEmpty)
            _contactTile(theme, Icons.mail_outline, 'help.email_us'.tr(),
                cfg.supportEmail, () => _launch('mailto:${cfg.supportEmail}')),
          if (cfg.supportPhone.isEmpty && cfg.supportEmail.isEmpty)
            Text('help.no_contact'.tr(),
                style: const TextStyle(color: AppColors.textSecondary)),

          const SizedBox(height: 24),

          // ── Support tickets ──
          _sectionTitle(theme, 'help.support_tickets'.tr()),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => context.push('/complaints'),
                  icon: const Icon(Icons.confirmation_number_outlined, size: 18),
                  label: Text('help.my_tickets'.tr()),
                  style: OutlinedButton.styleFrom(
                      minimumSize: const Size.fromHeight(48)),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => context.push('/create-complaint'),
                  icon: const Icon(Icons.add, size: 18),
                  label: Text('help.new_ticket'.tr()),
                  style: ElevatedButton.styleFrom(
                      minimumSize: const Size.fromHeight(48)),
                ),
              ),
            ],
          ),

          const SizedBox(height: 24),

          // ── FAQ ──
          _sectionTitle(theme, 'help.faq'.tr()),
          const SizedBox(height: 8),
          if (help.isLoadingFaqs)
            const Padding(
              padding: EdgeInsets.all(24),
              child: Center(child: CircularProgressIndicator()),
            )
          else if (help.faqs.isEmpty)
            Text('help.no_faqs'.tr(),
                style: const TextStyle(color: AppColors.textSecondary))
          else
            Container(
              decoration: BoxDecoration(
                color: theme.cardColor,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: theme.dividerColor),
              ),
              child: Column(
                children: [
                  for (var i = 0; i < help.faqs.length; i++) ...[
                    ExpansionTile(
                      shape: const Border(),
                      collapsedShape: const Border(),
                      title: Text(help.faqs[i].question,
                          style: const TextStyle(
                              fontWeight: FontWeight.w600, fontSize: 14)),
                      childrenPadding:
                          const EdgeInsets.fromLTRB(16, 0, 16, 16),
                      expandedAlignment: Alignment.centerLeft,
                      expandedCrossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(help.faqs[i].answer,
                            style: const TextStyle(
                                color: AppColors.textSecondary, height: 1.4)),
                      ],
                    ),
                    if (i != help.faqs.length - 1)
                      Divider(height: 1, color: theme.dividerColor),
                  ],
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _sectionTitle(ThemeData theme, String title) => Text(title,
      style: TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.bold,
          color: theme.colorScheme.onSurface));

  Widget _contactTile(ThemeData theme, IconData icon, String label,
      String value, VoidCallback onTap) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        tileColor: theme.cardColor,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: BorderSide(color: theme.dividerColor),
        ),
        leading: CircleAvatar(
          backgroundColor: AppColors.primary.withValues(alpha: 0.12),
          child: Icon(icon, color: AppColors.primary, size: 20),
        ),
        title: Text(label,
            style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
        subtitle: Text(value,
            style: const TextStyle(
                fontSize: 12, color: AppColors.textSecondary)),
        trailing: const Icon(Icons.chevron_right, color: AppColors.textSecondary),
        onTap: onTap,
      ),
    );
  }
}
