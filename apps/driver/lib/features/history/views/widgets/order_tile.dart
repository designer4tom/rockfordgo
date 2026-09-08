import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';
import '../../model/order_model.dart';

class OrderTile extends StatelessWidget {
  final OrderModel order;
  final VoidCallback onTap;

  const OrderTile({super.key, required this.order, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final earning = double.tryParse(order.earning) ?? 0;
    final avatar = Helpers.imageUrl(order.customerImage);
    // Material (not a decorated Container) so the tile's ink splash is visible.
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: Theme.of(context).cardColor,
        clipBehavior: Clip.antiAlias,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: BorderSide(color: Theme.of(context).dividerColor),
        ),
        child: ListTile(
          onTap: onTap,
          leading: CircleAvatar(
            backgroundColor: AppColors.primary.withValues(alpha: 0.12),
            backgroundImage: avatar != null ? NetworkImage(avatar) : null,
            child: avatar != null
                ? null
                : Icon(
                    order.isParcel
                        ? Icons.inventory_2_outlined
                        : Icons.directions_car,
                    color: AppColors.primary,
                  ),
          ),
          title: Text(
            order.customerName?.isNotEmpty == true
                ? order.customerName!
                : '#${order.orderNumber}',
            style: const TextStyle(fontWeight: FontWeight.w600),
          ),
          subtitle: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(order.status, style: const TextStyle(fontSize: 12)),
              Text(
                Helpers.dateTime(order.createdAt),
                style: TextStyle(
                  fontSize: 11,
                  color: Theme.of(context).hintColor,
                ),
              ),
            ],
          ),
          trailing: Text(
            Helpers.money(earning),
            style: const TextStyle(
              fontWeight: FontWeight.bold,
              color: AppColors.success,
            ),
          ),
        ),
      ),
    );
  }
}
