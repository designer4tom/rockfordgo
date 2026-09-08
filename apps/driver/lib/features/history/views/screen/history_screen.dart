import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/widgets/empty_state.dart';
import '../../../../core/widgets/loading_indicator.dart';
import '../../provider/history_provider.dart';
import '../widgets/order_tile.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<HistoryProvider>().loadOrders(refresh: true);
    });
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<HistoryProvider>();
    return Scaffold(
      appBar: AppBar(title: Text('history.title'.tr())),
      body: SafeArea(
        top: false,
        child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              children: ['all', 'ride', 'parcel'].map((f) {
                final sel = p.filterType == f;
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ChoiceChip(
                    label: Text('history.filter_$f'.tr()),
                    selected: sel,
                    onSelected: (_) => p.setFilter(f),
                    selectedColor: AppColors.primary.withValues(alpha: 0.2),
                  ),
                );
              }).toList(),
            ),
          ),
          Expanded(
            child: p.loadingOrders && p.orders.isEmpty
                ? const LoadingIndicator()
                : p.orders.isEmpty
                    ? EmptyState(
                        icon: Icons.history, title: 'history.empty'.tr())
                    : RefreshIndicator(
                        onRefresh: () => p.loadOrders(refresh: true),
                        child: ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: p.orders.length,
                          itemBuilder: (_, i) {
                            final o = p.orders[i];
                            return OrderTile(
                              order: o,
                              onTap: () => context.push(RouteNames.orderDetail,
                                  extra: o.id),
                            );
                          },
                        ),
                      ),
          ),
        ],
        ),
      ),
    );
  }
}
