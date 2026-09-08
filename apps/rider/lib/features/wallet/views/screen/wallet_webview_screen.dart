import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:webview_flutter/webview_flutter.dart';

/// In-app payment webview for wallet top-up. Pops with 'success' or
/// 'cancelled' based on the gateway's redirect URL.
class WalletWebViewScreen extends StatefulWidget {
  final String paymentUrl;

  const WalletWebViewScreen({super.key, required this.paymentUrl});

  @override
  State<WalletWebViewScreen> createState() => _WalletWebViewScreenState();
}

class _WalletWebViewScreenState extends State<WalletWebViewScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;
  bool _resultSent = false;

  @override
  void initState() {
    super.initState();
    debugPrint('💳 WebView loading: ${widget.paymentUrl}');
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (url) {
            if (mounted) setState(() => _isLoading = true);
            _check(url);
          },
          onPageFinished: (url) {
            if (mounted) setState(() => _isLoading = false);
            _check(url);
          },
          onNavigationRequest: (req) {
            _check(req.url);
            return NavigationDecision.navigate;
          },
          onWebResourceError: (err) {
            debugPrint('💳 WebView error: ${err.errorCode} ${err.description} '
                '(${err.url})');
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.paymentUrl));
  }

  /// Detect the backend success/cancel redirect (/payment/success,
  /// /payment/cancel) and close with the result.
  void _check(String url) {
    if (_resultSent || !mounted) return;
    final u = url.toLowerCase();
    if (u.contains('payment/success') ||
        u.contains('topup/success') ||
        u.contains('status=success')) {
      _resultSent = true;
      context.pop('success');
    } else if (u.contains('payment/cancel') ||
        u.contains('payment/failed') ||
        u.contains('topup/cancel') ||
        u.contains('status=cancel') ||
        u.contains('status=failed')) {
      _resultSent = true;
      context.pop('cancelled');
    }
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop && !_resultSent) {
          _resultSent = true;
          context.pop('cancelled');
        }
      },
      child: Scaffold(
        appBar: AppBar(
          title: Text('wallet.payment'.tr()),
          leading: IconButton(
            icon: const Icon(Icons.close),
            onPressed: () {
              if (!_resultSent) {
                _resultSent = true;
                context.pop('cancelled');
              }
            },
          ),
        ),
        body: Stack(
          children: [
            WebViewWidget(controller: _controller),
            if (_isLoading) const Center(child: CircularProgressIndicator()),
          ],
        ),
      ),
    );
  }
}
