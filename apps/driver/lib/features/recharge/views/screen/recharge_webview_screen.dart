import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../../../../core/constants/app_colors.dart';

/// Loads the payment gateway URL and detects completion via the redirect URL.
/// Pops with `'success'` or `'cancelled'`.
class RechargeWebViewScreen extends StatefulWidget {
  final String paymentUrl;
  const RechargeWebViewScreen({super.key, required this.paymentUrl});

  @override
  State<RechargeWebViewScreen> createState() => _RechargeWebViewScreenState();
}

class _RechargeWebViewScreenState extends State<RechargeWebViewScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;
  bool _done = false; // guard against double-pop

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (url) {
            if (mounted) setState(() => _isLoading = true);
            _checkResult(url);
          },
          onPageFinished: (url) {
            if (mounted) setState(() => _isLoading = false);
            _checkResult(url);
          },
          onNavigationRequest: (request) {
            _checkResult(request.url);
            return NavigationDecision.navigate;
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.paymentUrl));
  }

  /// Detect success/cancel from the gateway's redirect URL.
  /// Match these patterns to your backend's success_url / cancel_url.
  void _checkResult(String url) {
    if (_done) return;
    final u = url.toLowerCase();
    if (u.contains('payment/success') ||
        u.contains('recharge/success') ||
        u.contains('status=success')) {
      _finish('success');
    } else if (u.contains('payment/cancel') ||
        u.contains('recharge/cancel') ||
        u.contains('status=cancel') ||
        u.contains('payment/failed') ||
        u.contains('status=failed')) {
      _finish('cancelled');
    }
  }

  void _finish(String result) {
    if (_done || !mounted) return;
    _done = true;
    context.pop(result);
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) _finish('cancelled'); // hardware back = cancel
      },
      child: Scaffold(
        appBar: AppBar(
          title: Text('recharge.payment'.tr()),
          leading: IconButton(
            icon: const Icon(Icons.close),
            onPressed: () => _finish('cancelled'), // manual close = cancel
          ),
        ),
        body: Stack(
          children: [
            WebViewWidget(controller: _controller),
            if (_isLoading)
              const Center(
                child: CircularProgressIndicator(color: AppColors.primary),
              ),
          ],
        ),
      ),
    );
  }
}
