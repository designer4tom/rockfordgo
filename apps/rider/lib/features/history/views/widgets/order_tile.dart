import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/order_model.dart';
import '../../provider/history_provider.dart';

class OrderTile extends StatelessWidget {
  final OrderModel order;
  final VoidCallback onTap;

  const OrderTile({super.key, required this.order, required this.onTap});

  static const Color _purple = Color(0xFF7C3AED);

  bool get _isParcel =>
      order.type == 'parcel' || order.type == 'courier';

  /// Dynamic accent colour for the type badge / icon, driven by API `type`.
  Color get _typeColor => switch (order.type) {
        'ride' => AppColors.primary,
        'bike' => AppColors.success,
        'parcel' || 'courier' => _purple,
        _ => AppColors.textSecondary,
      };

  IconData get _typeIcon => switch (order.type) {
        'bike' => Icons.two_wheeler,
        'parcel' || 'courier' => Icons.inventory_2_outlined,
        _ => Icons.directions_car,
      };

  String _typeLabel() => switch (order.type) {
        'ride' => 'history.type_ride'.tr(),
        'bike' => 'history.type_bike'.tr(),
        'parcel' || 'courier' => 'history.type_courier'.tr(),
        _ => order.type.isEmpty
            ? 'history.type_ride'.tr()
            : '${order.type[0].toUpperCase()}${order.type.substring(1)}',
      };

  String _title() {
    if (_isParcel) return 'history.send_package'.tr();
    final drop = order.dropAddress.split(',').first.trim();
    if (drop.isEmpty) return _typeLabel();
    return '${_typeLabel()} ${'history.to_dest'.tr()} $drop';
  }

  ({Color color, String label}) _status() {
    final cat = HistoryProvider.statusCategory(order.status);
    return switch (cat) {
      'completed' => (
          color: AppColors.success,
          label: 'history.status_completed'.tr()
        ),
      'cancelled' => (
          color: AppColors.danger,
          label: 'history.status_cancelled'.tr()
        ),
      _ => (
          color: AppColors.primary,
          label: 'history.status_upcoming'.tr()
        ),
    };
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final amount = double.tryParse(order.totalAmount) ?? 0;
    final status = _status();

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Service icon avatar (API service_icon, else type icon).
                  Container(
                    width: 52,
                    height: 52,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: _typeColor.withValues(alpha: 0.12),
                      shape: BoxShape.circle,
                    ),
                    child: (order.serviceIcon != null &&
                            order.serviceIcon!.isNotEmpty)
                        ? ClipOval(
                            child: Image.network(
                              order.serviceIcon!,
                              width: 30,
                              height: 30,
                              fit: BoxFit.contain,
                              errorBuilder: (context, error, stack) =>
                                  Icon(_typeIcon, color: _typeColor, size: 26),
                            ),
                          )
                        : Icon(_typeIcon, color: _typeColor, size: 26),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            _badge(_typeLabel(), _typeColor),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                _title(),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 15,
                                  color: theme.colorScheme.onSurface,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          Helpers.currency(amount),
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 15,
                            color: theme.colorScheme.onSurface,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const Icon(Icons.chevron_right,
                      color: AppColors.textSecondary),
                ],
              ),
              const SizedBox(height: 12),
              // Route: pickup → drop with connector + status badge.
              Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Expanded(child: _route(theme)),
                  _badge(status.label, status.color, filled: true),
                ],
              ),
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Divider(height: 1, color: theme.dividerColor.withValues(alpha: 0.3)),
              ),
              // Footer: date • passenger/weight info.
              Row(
                children: [
                  const Icon(Icons.calendar_today_outlined,
                      size: 14, color: AppColors.textSecondary),
                  const SizedBox(width: 6),
                  Text(
                    _date(),
                    style: const TextStyle(
                        fontSize: 12, color: AppColors.textSecondary),
                  ),
                  const SizedBox(width: 10),
                  Container(width: 1, height: 14, color: theme.dividerColor),
                  const SizedBox(width: 10),
                  Icon(_isParcel ? Icons.inventory_2_outlined : Icons.person_outline,
                      size: 14, color: AppColors.textSecondary),
                  const SizedBox(width: 6),
                  Text(
                    _isParcel
                        ? 'history.parcel'.tr()
                        : 'history.one_passenger'.tr(),
                    style: const TextStyle(
                        fontSize: 12, color: AppColors.textSecondary),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _route(ThemeData theme) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _routeLine(
          theme,
          dot: Container(
            width: 11,
            height: 11,
            decoration: BoxDecoration(
                color: _typeColor, shape: BoxShape.circle),
          ),
          text: order.pickupAddress,
        ),
        // Padding(
        //   padding: const EdgeInsetsDirectional.only(start: 5),
        //   child: SizedBox(
        //     height: 14,
        //     child: VerticalDivider(
        //         width: 1, thickness: 1, color: theme.dividerColor.withValues(alpha: 0.3)),
        //   ),
        // ),

        Padding(
          padding: const EdgeInsetsDirectional.only(start: 4),
          child: Align(
            alignment: AlignmentDirectional.centerStart,
            child: _dashedConnector(theme),
          ),
        ),

        _routeLine(
          theme,
          dot: Container(
            width: 11,
            height: 11,
            decoration: BoxDecoration(
              color: theme.colorScheme.onSurface,
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
          ),
          text: order.dropAddress.isEmpty ? '—' : order.dropAddress,
        ),
      ],
    );
  }

  Widget _routeLine(ThemeData theme,
      {required Widget dot, required String text}) {
    return Row(
      children: [
        SizedBox(width: 11, child: Center(child: dot)),
        const SizedBox(width: 10),
        Expanded(
          child: Text(
            text,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
                fontSize: 13, color: theme.colorScheme.onSurface),
          ),
        ),
      ],
    );
  }

  Widget _dashedConnector(ThemeData theme) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 0),
      child: Column(
        children: List.generate(
          3,
              (_) => Container(
            width: 2,
            height: 3,
            margin: const EdgeInsets.symmetric(vertical: 1.5),
            color: theme.dividerColor.withValues(alpha: 0.3),
          ),
        ),
      ),
    );
  }

  Widget _badge(String label, Color color, {bool filled = false}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: filled ? 0.14 : 0.12),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label,
        style: TextStyle(
            fontSize: 11, color: color, fontWeight: FontWeight.w600),
      ),
    );
  }

  String _date() {
    final dt = DateTime.tryParse(order.createdAt);
    if (dt == null) return order.createdAt;
    return Helpers.formatDateTime(dt.toLocal());
  }
}
