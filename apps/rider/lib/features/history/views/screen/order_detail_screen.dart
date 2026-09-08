import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/loading_widget.dart';
import '../../../../core/widgets/primary_action_button.dart';
import '../../model/order_model.dart';
import '../../provider/history_provider.dart';

class OrderDetailScreen extends StatefulWidget {
  final int orderId;

  const OrderDetailScreen({super.key, required this.orderId});

  @override
  State<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends State<OrderDetailScreen> {
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
      // keep null → not-found state
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: Text('history.trip_detail'.tr())),
      body: _loading
          ? const LoadingWidget()
          : _detail == null
              ? Center(child: Text('history.detail_not_found'.tr()))
              : _content(_detail!),
    );
  }

  ({Color color, String label}) _status(String status) {
    final cat = HistoryProvider.statusCategory(status);
    return switch (cat) {
      'completed' => (
          color: AppColors.success,
          label: 'history.status_completed'.tr()
        ),
      'cancelled' => (
          color: AppColors.danger,
          label: 'history.status_cancelled'.tr()
        ),
      _ => (color: AppColors.primary, label: 'history.status_upcoming'.tr()),
    };
  }

  String _date(String raw) {
    final dt = DateTime.tryParse(raw);
    return dt == null ? raw : Helpers.formatDateTime(dt.toLocal());
  }

  Widget _content(OrderDetailModel d) {
    final theme = Theme.of(context);
    final status = _status(d.status);
    final isParcel = d.type == 'parcel';

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // ── Status header ──
        _card(
          theme,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text('#${d.orderNumber}',
                        style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                            color: theme.colorScheme.onSurface)),
                  ),
                  _badge(status.label, status.color),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.calendar_today_outlined,
                      size: 14, color: AppColors.textSecondary),
                  const SizedBox(width: 6),
                  Text(_date(d.createdAt),
                      style: const TextStyle(
                          fontSize: 13, color: AppColors.textSecondary)),
                  const Spacer(),
                  _badge(
                      isParcel
                          ? 'history.type_courier'.tr()
                          : 'history.type_ride'.tr(),
                      isParcel ? const Color(0xFF7C3AED) : AppColors.primary),
                ],
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),

        // ── Route ──
        _card(
          theme,
          child: Column(
            children: [
              _routeRow(theme, isStart: true, text: d.pickupAddress),
              Padding(
                padding: const EdgeInsetsDirectional.only(start: 4),
                child: Align(
                  alignment: AlignmentDirectional.centerStart,
                  child: _dashedConnector(theme),
                ),
              ),
              SizedBox(height: 5,),
              _routeRow(theme, isStart: false, text: d.dropAddress),
               Divider(height: 22,color: AppColors.border,),

              Row(
                children: [
                  _metaChip(theme, Icons.straighten, '${d.distanceKm} km'),
                  const SizedBox(width: 10),
                  _metaChip(theme, Icons.access_time,
                      'ride.min_away'.tr(namedArgs: {'number': '${d.durationMinutes}'})),
                ],
              ),
            ],
          ),
        ),

        // ── Driver ──
        if (d.driver != null) ...[
          const SizedBox(height: 12),
          _card(
            theme,
            child: Row(
              children: [
                CircleAvatar(
                  radius: 24,
                  backgroundColor: AppColors.primary.withValues(alpha: 0.12),
                  child: Text(
                    d.driver!.name.isNotEmpty
                        ? d.driver!.name[0].toUpperCase()
                        : '?',
                    style: const TextStyle(
                        color: AppColors.primary,
                        fontWeight: FontWeight.bold,
                        fontSize: 18),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(d.driver!.name,
                          style: TextStyle(
                              fontWeight: FontWeight.w600,
                              fontSize: 15,
                              color: theme.colorScheme.onSurface)),
                      if (d.driver!.vehicle.isNotEmpty) ...[
                        const SizedBox(height: 2),
                        Text(d.driver!.vehicle,
                            style: const TextStyle(
                                fontSize: 13, color: AppColors.textSecondary)),
                      ],
                    ],
                  ),
                ),
                Row(
                  children: [
                    const Icon(Icons.star, size: 16, color: AppColors.warning),
                    const SizedBox(width: 2),
                    Text(d.driver!.rating.toStringAsFixed(1),
                        style: const TextStyle(fontWeight: FontWeight.w600)),
                  ],
                ),
              ],
            ),
          ),
        ],

        // ── Fare breakdown ──
        const SizedBox(height: 12),
        _card(
          theme,
          child: Column(
            children: [
              ...d.fare.lines.map((l) => _fareRow(
                    theme,
                    l.labelKey.tr(),
                    '${l.discount ? '- ' : ''}${Helpers.currency(l.amount)}',
                    color: l.discount ? AppColors.success : null,
                  )),
              const Divider(height: 22,color: AppColors.border,),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('history.total'.tr(),
                      style: const TextStyle(
                          fontWeight: FontWeight.bold, fontSize: 16)),
                  Text(Helpers.currency(d.fare.total),
                      style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 18,
                          color: AppColors.primary)),
                ],
              ),
            ],
          ),
        ),

        // ── Payment ──
        const SizedBox(height: 12),
        _card(
          theme,
          child: Row(
            children: [
              Icon(
                  d.paymentMethod == 'wallet'
                      ? Icons.account_balance_wallet_outlined
                      : d.paymentMethod == 'online' || d.paymentMethod == 'card'
                          ? Icons.credit_card
                          : Icons.payments_outlined,
                  color: AppColors.primary),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  d.paymentMethod.isEmpty
                      ? 'history.payment'.tr()
                      : '${d.paymentMethod[0].toUpperCase()}${d.paymentMethod.substring(1)}',
                  style: TextStyle(
                      fontWeight: FontWeight.w600,
                      color: theme.colorScheme.onSurface),
                ),
              ),
              _badge(
                d.paymentStatus == 'paid'
                    ? 'history.paid'.tr()
                    : 'history.pending'.tr(),
                d.paymentStatus == 'paid'
                    ? AppColors.success
                    : AppColors.warning,
              ),
            ],
          ),
        ),

        const SizedBox(height: 24),
        // Ongoing → track; otherwise → invoice.
        if (d.isOngoing)
          PrimaryActionButton(
            label: 'history.track_order'.tr(),
            onPressed: () => context.push(
              isParcel ? RouteNames.parcelTracking : RouteNames.rideTracking,
              extra: d.id,
            ),
          )
        else
          PrimaryActionButton(
            label: 'history.view_invoice'.tr(),
            onPressed: () => context.push('/invoice', extra: d.id),
          ),
      ],
    );
  }

  // ── Small helpers ──
  Widget _card(ThemeData theme, {required Widget child}) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(16),
       // border: Border.all(color: theme.dividerColor),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ]
      ),
      child: child,
    );
  }

  Widget _badge(String label, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(label,
          style: TextStyle(
              fontSize: 11, color: color, fontWeight: FontWeight.w600)),
    );
  }

  Widget _routeRow(ThemeData theme,
      {required bool isStart, required String text}) {
    final dot = isStart
        ? Container(
            width: 12,
            height: 12,
            decoration: const BoxDecoration(
                color: AppColors.primary, shape: BoxShape.circle))
        :  Container(
      width: 11,
      height: 11,
      decoration: BoxDecoration(
        color: AppColors.textPrimary,
        borderRadius: BorderRadius.circular(2),
      ),
      child: Padding(
        padding: const EdgeInsets.all(3.0),
        child: Container(

          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(1),
          ),
        ),
      ),
    );
    return Row(
      children: [
        SizedBox(width: 11, child: Center(child: dot)),
        const SizedBox(width: 12),
        Expanded(
          child: Text(text.isEmpty ? '—' : text,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                  fontSize: 14, color: theme.colorScheme.onSurface)),
        ),
      ],
    );
  }

  Widget _dashedConnector(ThemeData theme) {
    return Column(
      children: List.generate(
        3,
        (_) => Container(
          width: 2,
          height: 3,
          margin: const EdgeInsets.symmetric(vertical: 1.5),
          color: theme.dividerColor.withValues(alpha: 0.3),
        ),
      ),
    );
  }

  Widget _metaChip(ThemeData theme, IconData icon, String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: theme.scaffoldBackgroundColor,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: theme.dividerColor),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: AppColors.primary),
          const SizedBox(width: 6),
          Text(text, style: const TextStyle(fontSize: 12)),
        ],
      ),
    );
  }

  Widget _fareRow(ThemeData theme, String label, String value, {Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: AppColors.textSecondary)),
          Text(value,
              style: TextStyle(
                  fontWeight: FontWeight.w500,
                  color: color ?? theme.colorScheme.onSurface)),
        ],
      ),
    );
  }
}
