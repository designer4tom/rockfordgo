import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/constants/api_endpoints.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/constants/app_constants.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/loading_widget.dart';
import '../../../../core/widgets/primary_action_button.dart';
import '../../model/order_model.dart';
import '../../provider/history_provider.dart';

class InvoiceScreen extends StatefulWidget {
  final int orderId;

  const InvoiceScreen({super.key, required this.orderId});

  @override
  State<InvoiceScreen> createState() => _InvoiceScreenState();
}

class _InvoiceScreenState extends State<InvoiceScreen> {
  OrderDetailModel? _detail;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final detail =
          await context.read<HistoryProvider>().getOrderDetail(widget.orderId);
      if (mounted) setState(() => _detail = detail);
    } catch (_) {
      // not-found state
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String get _pdfUrl =>
      '${AppConstants.baseUrl}${ApiEndpoints.invoice(widget.orderId)}';

  Future<void> _openPdf() async {
    final uri = Uri.parse(_pdfUrl);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  Future<void> _share() async {
    final d = _detail;
    await SharePlus.instance.share(
      ShareParams(
        text: 'ReadyRide Invoice #${d?.orderNumber ?? widget.orderId}\n'
            'Amount: ${Helpers.currency(d?.fare.total ?? 0)}',
      ),
    );
  }

  String _date(String raw) {
    final dt = DateTime.tryParse(raw);
    return dt == null ? raw : Helpers.formatDate(dt.toLocal());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: Text('history.invoice'.tr())),
      body: _loading
          ? const LoadingWidget()
          : _detail == null
              ? Center(child: Text('history.invoice_not_found'.tr()))
              : _content(_detail!),
    );
  }

  Widget _content(OrderDetailModel d) {
    final theme = Theme.of(context);
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: theme.cardColor,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: theme.dividerColor),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Column(
                  children: [
                    Text.rich(TextSpan(children: [
                      TextSpan(
                          text: 'Ready',
                          style: TextStyle(
                              fontSize: 22,
                              fontWeight: FontWeight.bold,
                              color: theme.colorScheme.onSurface)),
                      const TextSpan(
                          text: 'Ride',
                          style: TextStyle(
                              fontSize: 22,
                              fontWeight: FontWeight.bold,
                              color: AppColors.primary)),
                    ])),
                    const SizedBox(height: 2),
                    Text('history.invoice'.tr().toUpperCase(),
                        style: const TextStyle(
                            letterSpacing: 2,
                            color: AppColors.textSecondary,
                            fontSize: 12)),
                  ],
                ),
              ),
              const Divider(height: 24),
              _row('history.invoice'.tr(), '#${d.orderNumber}'),
              _row('history.date'.tr(), _date(d.createdAt)),
              _row('history.type'.tr(),
                  d.type == 'parcel' ? 'history.type_courier'.tr() : 'history.type_ride'.tr()),
              const Divider(height: 24),
              _row('history.from'.tr(), d.pickupAddress),
              _row('history.to'.tr(), d.dropAddress),
              _row('history.distance'.tr(), '${d.distanceKm} km'),
              _row('history.duration'.tr(),
                  'ride.min_away'.tr(namedArgs: {'number': '${d.durationMinutes}'})),
              if (d.driver != null) ...[
                const Divider(height: 24),
                _row('history.driver'.tr(), d.driver!.name),
                if (d.driver!.vehicle.isNotEmpty)
                  _row('history.vehicle'.tr(), d.driver!.vehicle),
              ],
              const Divider(height: 24),
              ...d.fare.lines.map((l) => _row(
                    l.labelKey.tr(),
                    '${l.discount ? '- ' : ''}${Helpers.currency(l.amount)}',
                  )),
              const Divider(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('history.total'.tr(),
                      style: const TextStyle(
                          fontWeight: FontWeight.bold, fontSize: 16)),
                  Text(Helpers.currency(d.fare.total),
                      style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                          color: AppColors.primary)),
                ],
              ),
              const SizedBox(height: 6),
              _row('history.payment'.tr(),
                  '${d.paymentMethod} (${d.paymentStatus})'),
              const Divider(height: 24),
              Center(
                child: Text('history.invoice_thanks'.tr(),
                    style: const TextStyle(color: AppColors.textSecondary)),
              ),
            ],
          ),
        ),
        const SizedBox(height: 20),
        PrimaryActionButton(
            label: 'history.download_pdf'.tr(), onPressed: _openPdf),
        const SizedBox(height: 12),
        OutlinedButton.icon(
          onPressed: _share,
          icon: const Icon(Icons.share, size: 18),
          label: Text('common.share'.tr()),
          style: OutlinedButton.styleFrom(
            minimumSize: const Size.fromHeight(52),
          ),
        ),
      ],
    );
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 96,
            child: Text(label,
                style: const TextStyle(color: AppColors.textSecondary)),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(value.isEmpty ? '—' : value,
                style: const TextStyle(fontWeight: FontWeight.w500)),
          ),
        ],
      ),
    );
  }
}
