import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/widgets/loading_widget.dart';
import '../../model/order_model.dart';
import '../../provider/history_provider.dart';
import '../widgets/order_filter.dart';
import '../widgets/order_tile.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  final _scroll = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => context.read<HistoryProvider>().loadOrders(refresh: true),
    );
    _scroll.addListener(() {
      if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 200) {
        context.read<HistoryProvider>().loadOrders();
      }
    });
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  /// Ongoing orders open the live tracking screen (so the user can resume);
  /// finished ones open the order detail.
  void _openOrder(OrderModel order) {
    if (order.isOngoing) {
      context.push(
        order.type == 'parcel'
            ? RouteNames.parcelTracking
            : RouteNames.rideTracking,
        extra: order.id,
      );
    } else {
      context.push(RouteNames.orderDetail, extra: order.id);
    }
  }

  @override
  Widget build(BuildContext context) {
    final history = context.watch<HistoryProvider>();
    final theme = Theme.of(context);
    final orders = history.filteredOrders;

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      body: SafeArea(
        child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
            child: Align(
              alignment: AlignmentDirectional.centerStart,
              child: Text('home.my_bookings'.tr(),
                  style: TextStyle(
                      fontSize: 26,
                      fontWeight: FontWeight.bold,
                      color: theme.colorScheme.onSurface)),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
            child: OrderFilter(
              selected: history.statusFilter,
              onChanged: history.setStatusFilter,
            ),
          ),
          Expanded(
            child: history.isLoading
                ? const LoadingWidget()
                : orders.isEmpty
                    ? _emptyState()
                    : RefreshIndicator(
                        onRefresh: () => history.loadOrders(refresh: true),
                        child: ListView(
                          controller: _scroll,
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                          children: [
                            ...orders.map((order) => OrderTile(
                                  order: order,
                                  onTap: () => _openOrder(order),
                                )),
                            if (history.isLoadingMore)
                              const Padding(
                                padding: EdgeInsets.all(16),
                                child: Center(
                                    child: CircularProgressIndicator()),
                              ),
                            const SizedBox(height: 8),
                            _helpBanner(theme),
                          ],
                        ),
                      ),
          ),
        ],
        ),
      ),
    );
  }

  Widget _emptyState() {
    return ListView(
      padding: const EdgeInsets.all(24),
      children: [
        const SizedBox(height: 60),
        Icon(Icons.receipt_long_outlined,
            size: 72, color: AppColors.textSecondary.withValues(alpha: 0.5)),
        const SizedBox(height: 16),
        Text('history.no_trips'.tr(),
            textAlign: TextAlign.center,
            style: const TextStyle(
                fontSize: 18, fontWeight: FontWeight.bold)),
        const SizedBox(height: 6),
        Text('history.no_trips_msg'.tr(),
            textAlign: TextAlign.center,
            style: const TextStyle(color: AppColors.textSecondary)),
        const SizedBox(height: 20),
        Center(
          child: ElevatedButton.icon(
            onPressed: () => context.go(RouteNames.home),
            icon: Padding(
              padding: const EdgeInsets.only(left: 16.0),
              child: const Icon(Icons.add),
            ),
            label: Padding(
              padding: const EdgeInsets.only(right: 16.0),
              child: Text('history.book_a_ride'.tr()),
            ),
          ),
        ),
      ],
    );
  }

  Widget _helpBanner(ThemeData theme) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: theme.dividerColor),
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.12),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.headset_mic_outlined,
                color: AppColors.primary, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('history.need_help'.tr(),
                    style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                        color: theme.colorScheme.onSurface)),
                const SizedBox(height: 2),
                Text('history.help_subtitle'.tr(),
                    style: const TextStyle(
                        fontSize: 12, color: AppColors.textSecondary)),
              ],
            ),
          ),
          SizedBox(
            height: 40,
            child: OutlinedButton(
              onPressed: () => context.push(RouteNames.helpCenter),
              style: OutlinedButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                minimumSize: const Size(0, 32),
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                visualDensity: VisualDensity.compact,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    'home.help_center'.tr(),
                    style: const TextStyle(fontSize: 12),
                  ),
                  const SizedBox(width: 4),
                  const Icon(Icons.arrow_forward, size: 14),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
