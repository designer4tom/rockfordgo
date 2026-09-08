import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_widget_from_html/flutter_widget_from_html.dart';
import 'package:provider/provider.dart';

import '../../../../core/widgets/loading_indicator.dart';
import '../../provider/page_provider.dart';

/// Generic CMS page (Privacy Policy, Terms & Conditions, …) rendered as HTML.
/// Content is driver-specific (`app_type=driver`).
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
      (_) => context.read<PageProvider>().loadPage(widget.slug),
    );
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<PageProvider>();
    final page = p.pageFor(widget.slug);

    return Scaffold(
      appBar: AppBar(title: Text(page?.title ?? _fallbackTitle())),
      body: SafeArea(
        top: false,
        child: p.isLoading(widget.slug) && page == null
            ? const LoadingIndicator()
            : page == null
                ? _error(p.error)
                : page.content.trim().isEmpty
                    ? Center(
                        child: Text('page.no_content'.tr(),
                            style: TextStyle(color: Theme.of(context).hintColor)),
                      )
                    : SingleChildScrollView(
                        padding: const EdgeInsets.all(16),
                        child: HtmlWidget(
                          page.content,
                          textStyle: TextStyle(
                            fontSize: 14,
                            height: 1.5,
                            color: Theme.of(context).colorScheme.onSurface,
                          ),
                        ),
                      ),
      ),
    );
  }

  Widget _error(String? msg) => Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(msg ?? 'page.could_not_load'.tr()),
            const SizedBox(height: 12),
            OutlinedButton(
              onPressed: () => context.read<PageProvider>().loadPage(widget.slug),
              child: Text('common.retry'.tr()),
            ),
          ],
        ),
      );

  String _fallbackTitle() => widget.slug
      .split('-')
      .map((w) => w.isEmpty ? w : '${w[0].toUpperCase()}${w.substring(1)}')
      .join(' ');
}
