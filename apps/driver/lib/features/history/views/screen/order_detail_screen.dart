import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/loading_indicator.dart';
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
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    final d = await context.read<HistoryProvider>().getOrderDetail(widget.orderId);
    if (!mounted) return;
    setState(() {
      _detail = d;
      _loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('history.order_detail'.tr())),
      body: _loading
          ? const LoadingIndicator()
          : _detail == null
              ? Center(child: Text('history.order_not_found'.tr()))
              : _content(_detail!.order),
    );
  }

  Widget _content(OrderModel o) {
    final earning = double.tryParse(o.earning) ?? 0;
    return SafeArea(
      top: false,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        children: [
          _header(o),
          const SizedBox(height: 20),
          _card([
            _row('order.type'.tr(),
                o.isParcel ? 'order.parcel'.tr() : 'order.ride'.tr()),
            _divider(),
            _row('order.date'.tr(), Helpers.dateTime(o.createdAt)),
            if (o.pickupAddress != null) ...[
              _divider(),
              _row('order.pickup'.tr(), o.pickupAddress!),
            ],
            if (o.dropAddress != null) ...[
              _divider(),
              _row('order.drop_off'.tr(), o.dropAddress!),
            ],

          ]),
          const SizedBox(height: 16),
          _earningCard(earning),
        ],
      ),
    );
  }

  Widget _header(OrderModel o) {
    final avatar = Helpers.imageUrl(o.customerImage);
    return Column(
      children: [
        CircleAvatar(
          radius: 30,
          backgroundColor: AppColors.primary.withValues(alpha: 0.12),
          backgroundImage: avatar != null ? NetworkImage(avatar) : null,
          child: avatar != null
              ? null
              : Icon(
                  o.isParcel ? Icons.inventory_2_outlined : Icons.directions_car,
                  color: AppColors.primary,
                  size: 28,
                ),
        ),
        const SizedBox(height: 12),
        if (o.customerName != null && o.customerName!.isNotEmpty) ...[
          Text(
            o.customerName!,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: Theme.of(context).colorScheme.onSurface,
            ),
          ),
          const SizedBox(height: 4),
        ],
        Text(
          '#${o.orderNumber}',
          textAlign: TextAlign.center,
          style: TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.w700,
            letterSpacing: 0.2,
            color: Theme.of(context).colorScheme.onSurface,
          ),
        ),
        const SizedBox(height: 8),
        _statusChip(o.status),
      ],
    );
  }

  Widget _statusChip(String status) {
    final color = _statusColor(status);
    final label =
        status.isEmpty ? '—' : '${status[0].toUpperCase()}${status.substring(1)}';
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(
            color: color, fontWeight: FontWeight.w600, fontSize: 11),
      ),
    );
  }

  Color _statusColor(String status) {
    final s = status.toLowerCase();
    if (s.contains('complete')) return AppColors.success;
    if (s.contains('cancel') || s.contains('reject')) return AppColors.danger;
    return AppColors.warning;
  }

  Widget _card(List<Widget> children) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(children: children),
    );
  }

  Widget _divider() => Divider(height: 1, color: Theme.of(context).dividerColor);

  Widget _earningCard(double earning) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      decoration: BoxDecoration(
        color: AppColors.success.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        children: [
          const Icon(Icons.account_balance_wallet_outlined,
              color: AppColors.success),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              'earnings.your_earning'.tr(),
              style: TextStyle(
                fontSize: 12.5,
                fontWeight: FontWeight.w500,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
          ),
          Text(
            Helpers.money(earning),
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: AppColors.success,
            ),
          ),
        ],
      ),
    );
  }

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            flex: 2,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: Theme.of(context).hintColor,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            flex: 3,
            child: Text(
              value,
              textAlign: TextAlign.end,
              style: TextStyle(
                fontSize: 12.5,
                fontWeight: FontWeight.w600,
                height: 1.35,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
