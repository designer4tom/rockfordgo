import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_widget_from_html/flutter_widget_from_html.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/widgets/loading_widget.dart';
import '../../provider/help_provider.dart';

/// Generic CMS page (Privacy Policy, Terms & Conditions, About…) rendered as
/// HTML. Content is app-specific (`app_type=customer`) from the admin CMS.
class PageScreen extends StatefulWidget {
  final String slug;

  const PageScreen({super.key, required this.slug});

  @override
  State<PageScreen> createState() => _PageScreenState();
}

class _PageScreenState extends State<PageScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => context.read<HelpProvider>().loadPage(widget.slug),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final help = context.watch<HelpProvider>();
    final page = help.pageFor(widget.slug);

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      appBar: AppBar(title: Text(page?.title ?? '')),
      body: SafeArea(
        child: help.isLoadingPage(widget.slug)
            ? const LoadingWidget()
            : page == null
                ? Center(child: Text('history.detail_not_found'.tr()))
                : (page.content.trim().isEmpty)
                    ? Center(
                        child: Text('safety.empty'.tr(),
                            style: const TextStyle(
                                color: AppColors.textSecondary)),
                      )
                    : SingleChildScrollView(
                        padding: const EdgeInsets.all(16),
                        child: HtmlWidget(
                          page.content,
                          textStyle: TextStyle(
                            fontSize: 14,
                            height: 1.5,
                            color: theme.colorScheme.onSurface,
                          ),
                        ),
                      ),
      ),
    );
  }
}
