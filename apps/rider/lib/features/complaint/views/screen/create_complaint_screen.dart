import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/theme/app_text_styles.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../history/model/order_model.dart';
import '../../../history/provider/history_provider.dart';
import '../../provider/complaint_provider.dart';

class CreateComplaintScreen extends StatefulWidget {
  /// Preselected order when opened from an order/trip context.
  final int? orderId;

  const CreateComplaintScreen({super.key, this.orderId});

  @override
  State<CreateComplaintScreen> createState() => _CreateComplaintScreenState();
}

class _CreateComplaintScreenState extends State<CreateComplaintScreen> {
  static final _categories = [
    ('driver_behavior', 'complaint.cat_driver'.tr()),
    ('overcharging', 'complaint.cat_overcharge'.tr()),
    ('vehicle_condition', 'complaint.cat_vehicle'.tr()),
    ('safety', 'complaint.cat_safety'.tr()),
    ('other', 'complaint.cat_other'.tr()),
  ];

  int? _orderId;
  OrderModel? _order;
  String? _category;
  final _description = TextEditingController();

  @override
  void initState() {
    super.initState();
    _orderId = widget.orderId;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final history = context.read<HistoryProvider>();
      if (history.orders.isEmpty) history.loadOrders();
    });
  }

  @override
  void dispose() {
    _description.dispose();
    super.dispose();
  }

  Future<void> _pickOrder() async {
    final selected = await showModalBottomSheet<OrderModel>(
      context: context,
      showDragHandle: true,
      builder: (_) => const _OrderPickerSheet(),
    );
    if (selected != null) {
      setState(() {
        _order = selected;
        _orderId = selected.id;
      });
    }
  }

  Future<void> _submit() async {
    if (_orderId == null) {
      SnackbarHelper.showError(context, 'complaint.trip_required'.tr());
      return;
    }
    if (_category == null) {
      SnackbarHelper.showError(context, 'complaint.select'.tr());
      return;
    }
    if (_description.text.trim().isEmpty) {
      SnackbarHelper.showError(context, 'complaint.description_required'.tr());
      return;
    }
    final provider = context.read<ComplaintProvider>();
    final ok = await provider.createComplaint(
      category: _category!,
      description: _description.text.trim(),
      orderId: _orderId,
    );
    if (!mounted) return;
    if (ok) {
      SnackbarHelper.showSuccess(context, 'complaint.submitted'.tr());
      context.pop();
    } else {
      SnackbarHelper.showError(context, provider.error ?? 'complaint.submit_failed'.tr());
    }
  }

  String _orderLabel(OrderModel order) {
    final number =
        order.orderNumber.isEmpty ? '#${order.id}' : order.orderNumber;
    final drop = order.dropAddress.split(',').first.trim();
    return drop.isEmpty ? number : '$number — $drop';
  }

  @override
  Widget build(BuildContext context) {
    final isSubmitting =
        context.select<ComplaintProvider, bool>((p) => p.isSubmitting);

    return Scaffold(
      appBar: AppBar(title: Text('complaint.new'.tr())),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text('complaint.trip'.tr(), style: AppTextStyles.title),
          const SizedBox(height: 8),
          InkWell(
            onTap: widget.orderId == null ? _pickOrder : null,
            borderRadius: BorderRadius.circular(8),
            child: InputDecorator(
              decoration: InputDecoration(
                suffixIcon: widget.orderId == null
                    ? const Icon(Icons.arrow_drop_down)
                    : null,
              ),
              child: Text(
                _order != null
                    ? _orderLabel(_order!)
                    : _orderId != null
                        ? '#$_orderId'
                        : 'complaint.select_trip'.tr(),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: _orderId == null
                    ? const TextStyle(color: AppColors.textSecondary)
                    : null,
              ),
            ),
          ),
          const SizedBox(height: 20),
          Text('complaint.category'.tr(), style: AppTextStyles.title),
          const SizedBox(height: 8),
          DropdownButtonFormField<String>(
            initialValue: _category,
            decoration: InputDecoration(hintText: 'complaint.select'.tr()),
            items: _categories
                .map((c) =>
                    DropdownMenuItem(value: c.$1, child: Text(c.$2)))
                .toList(),
            onChanged: (v) => setState(() => _category = v),
          ),
          const SizedBox(height: 20),
          Text('complaint.description'.tr(), style: AppTextStyles.title),
          const SizedBox(height: 8),
          TextField(
            controller: _description,
            maxLines: 5,
            decoration: InputDecoration(
              hintText: 'complaint.description_hint'.tr(),
              alignLabelWithHint: true,
            ),
          ),
          const SizedBox(height: 24),
          CustomButton(
            text: 'common.submit'.tr(),
            isLoading: isSubmitting,
            onPressed: _submit,
          ),
        ],
      ),
    );
  }
}

class _OrderPickerSheet extends StatelessWidget {
  const _OrderPickerSheet();

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<HistoryProvider>();

    if (provider.isLoading && provider.orders.isEmpty) {
      return const SizedBox(
        height: 200,
        child: Center(child: CircularProgressIndicator()),
      );
    }
    if (provider.orders.isEmpty) {
      return SizedBox(
        height: 200,
        child: Center(child: Text('history.no_trips'.tr())),
      );
    }

    return SizedBox(
      height: MediaQuery.sizeOf(context).height * 0.6,
      child: ListView.builder(
        itemCount: provider.orders.length,
        itemBuilder: (context, index) {
          final order = provider.orders[index];
          final dt = DateTime.tryParse(order.createdAt);
          final date =
              dt != null ? Helpers.formatDateTime(dt.toLocal()) : order.createdAt;
          return ListTile(
            leading: Icon(
              order.type == 'parcel' || order.type == 'courier'
                  ? Icons.inventory_2_outlined
                  : Icons.directions_car,
              color: AppColors.primary,
            ),
            title: Text(
              order.orderNumber.isEmpty ? '#${order.id}' : order.orderNumber,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            subtitle: Text(
              order.dropAddress.isEmpty ? date : '$date · ${order.dropAddress}',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            onTap: () => Navigator.pop(context, order),
          );
        },
      ),
    );
  }
}
