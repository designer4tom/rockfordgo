import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../home/provider/home_provider.dart';
import '../../../ride/provider/ride_provider.dart';
import '../../../tracking/provider/tracking_provider.dart';
import '../../model/service_model.dart';
import '../../provider/service_provider.dart';

/// Full "Our services" listing, opened from the home "See all" action.
class OurServicesScreen extends StatefulWidget {
  const OurServicesScreen({super.key});

  @override
  State<OurServicesScreen> createState() => _OurServicesScreenState();
}

class _OurServicesScreenState extends State<OurServicesScreen> {
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final services = context.watch<ServiceProvider>().services;
    // Fall back to the built-in services until the API responds (or if it's
    // empty / fails), mirroring the home screen behaviour.
    final items = services.isNotEmpty ? services : _fallbackServices();

    return Scaffold(
      appBar: AppBar(title: Text('home.our_services'.tr())),
      body: GridView.builder(
        padding: const EdgeInsets.all(16),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 0.85,
        ),
        itemCount: items.length,
        itemBuilder: (context, i) => _serviceCard(theme, items[i]),
      ),
    );
  }

  Widget _serviceCard(ThemeData theme, ServiceModel s) {
    final isParcel = s.type == 'parcel';
    return Material(
      color: theme.cardColor,
      borderRadius: BorderRadius.circular(16),
      child: GestureDetector(
        onTap: () => _startBooking(_bookingTypeFor(s)),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              SizedBox(
                width: 100,
                height: 100,
                child: (s.icon != null && s.icon!.isNotEmpty)
                    ? Image.network(
                        s.icon!,
                        width: 90,
                        height: 90,
                        fit: BoxFit.contain,
                        errorBuilder: (context, error, stack) =>
                            Icon(_serviceIcon(s.type), color: AppColors.primary),
                      )
                    : Icon(_serviceIcon(s.type), color: AppColors.primary),
              ),
              const SizedBox(height: 5),
              Text(
                s.name,
                style: TextStyle(
                  color: isParcel
                      ? AppColors.primary
                      : theme.colorScheme.onSurface,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                (s.description != null && s.description!.isNotEmpty)
                    ? s.description!
                    : _serviceDesc(s.type),
                textAlign: TextAlign.center,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: theme.colorScheme.onSurface.withValues(alpha: 0.5),
                  fontSize: 11,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _bookingTypeFor(ServiceModel service) {
    final type = service.type.toLowerCase();
    final slug = service.slug.toLowerCase();
    final name = service.name.toLowerCase();
    if (type == 'parcel' || slug == 'parcel') return 'parcel';
    if (type == 'bike' || slug == 'bike' || name.contains('bike')) {
      return 'bike';
    }
    return 'ride';
  }

  // Booking navigation — identical behaviour to the home screen.
  Future<void> _startBooking(String type) async {
    final home = context.read<HomeProvider>();

    if (type == 'parcel') {
      home.selectService('parcel');
      context.push(RouteNames.parcelBooking);
      return;
    }

    final active = await context.read<TrackingProvider>().checkActiveOrder();
    if (!mounted) return;
    if (active != null && !active.isParcel) {
      SnackbarHelper.showInfo(context, 'home.ongoing_ride_redirect'.tr());
      if (active.isSearching) {
        context.read<RideProvider>().resumeSearching(active.orderId);
        context.push('/searching-driver');
      } else {
        context.push(RouteNames.rideTracking, extra: active.orderId);
      }
      return;
    }

    home.selectService(type);
    context.push(RouteNames.setDestination);
  }

  IconData _serviceIcon(String type) {
    switch (type) {
      case 'parcel':
        return Icons.inventory_2_outlined;
      case 'bike':
        return Icons.two_wheeler;
      case 'ride':
        return Icons.directions_car;
      default:
        return Icons.category_outlined;
    }
  }

  String _serviceDesc(String type) {
    switch (type) {
      case 'parcel':
        return 'home.courier_desc'.tr();
      case 'bike':
        return 'home.bike_desc'.tr();
      case 'ride':
        return 'home.ride_desc'.tr();
      default:
        return '';
    }
  }

  List<ServiceModel> _fallbackServices() => [
        ServiceModel(
            id: -1,
            name: 'home.ride'.tr(),
            slug: 'ride',
            type: 'ride'),
        ServiceModel(
            id: -2,
            name: 'home.bike'.tr(),
            slug: 'bike',
            type: 'bike'),
        ServiceModel(
            id: -3,
            name: 'home.courier'.tr(),
            slug: 'parcel',
            type: 'parcel'),
      ];
}
