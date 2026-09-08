import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../banner/provider/banner_provider.dart';
import '../../../banner/views/widgets/banner_carousel.dart';
import '../../../history/provider/history_provider.dart';
import '../../../location/provider/location_provider.dart';
import '../../../ride/provider/ride_provider.dart';
import '../../../service/model/service_model.dart';
import '../../../service/provider/service_provider.dart';
import '../../../tracking/provider/tracking_provider.dart';
import '../../../wallet/provider/wallet_provider.dart';
import '../../provider/home_provider.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _initLocation());
  }

  Future<void> _initLocation() async {
    final location = context.read<LocationProvider>();
    final history = context.read<HistoryProvider>();

    context.read<BannerProvider>().loadBanners();
    context.read<ServiceProvider>().loadServices();
    // Keep the drawer's wallet balance fresh.
    context.read<WalletProvider>().loadBalance();
    if (history.orders.isEmpty) {
      history.loadOrders(refresh: true);
    }

    // Resolve the current place for the pickup header. The new dashboard
    // home has no map, so nearby-driver polling is no longer started.
    await location.getCurrentPlace();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final banners = context.watch<BannerProvider>().banners;
    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      body: ListView(
        padding: const EdgeInsetsDirectional.fromSTEB(16, 8, 16, 24),
        children: [
          _locationCard(theme),
          const SizedBox(height: 20),
          banners.isNotEmpty
              ? BannerCarousel(banners: banners)
              : _promoBanner(theme),
          const SizedBox(height: 24),
          _sectionHeader(
            theme,
            title: 'home.our_services'.tr(),
            trailing: 'home.see_all'.tr(),
            onTrailing: () => context.push(RouteNames.ourServices),
          ),
          const SizedBox(height: 12),
          _serviceCards(theme),
          const SizedBox(height: 20),
          _quickActions(theme),
          const SizedBox(height: 24),
          _sectionHeader(
            theme,
            title: 'home.recent_activity'.tr(),
            trailing: 'home.view_all'.tr(),
            onTrailing: () => context.go(RouteNames.history),
          ),
          const SizedBox(height: 12),
          _recentActivity(theme),
        ],
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // 2. Location card
  // ---------------------------------------------------------------------------
  Widget _locationCard(ThemeData theme) {
    final address = context.select<LocationProvider, String?>(
      (p) => p.currentPlace?.address,
    );
    final isLocating = context.select<LocationProvider, bool>(
      (p) => p.isLoading,
    );
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 10)],
      ),
      child: Column(
        children: [
          InkWell(
            borderRadius: BorderRadius.circular(12),
            // Re-resolve the GPS address on tap (also the retry path when
            // the first fix at startup failed).
            onTap: isLocating
                ? null
                : () => context.read<LocationProvider>().getCurrentPlace(),
            child: Row(
              children: [
                Container(
                  width: 13,
                  height: 13,
                  decoration: const BoxDecoration(
                    color: AppColors.primary,
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'home.pickup_location'.tr(),
                        style: TextStyle(
                          color: theme.colorScheme.onSurface.withValues(
                            alpha: 0.5,
                          ),
                          fontSize: 12,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        address ??
                            (isLocating
                                ? 'home.locating'.tr()
                                : 'home.tap_to_locate'.tr()),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: theme.colorScheme.onSurface,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 8,
                  ),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(12),
                    color: AppColors.surface,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.08),
                        blurRadius: 6,
                        offset: const Offset(0, 3),
                      ),
                    ],
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(
                        Icons.gps_fixed,
                        size: 14,
                        color: AppColors.primary,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        'home.now'.tr(),
                        style: const TextStyle(
                          color: AppColors.primary,
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const Icon(
                        Icons.keyboard_arrow_down,
                        size: 16,
                        color: AppColors.primary,
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          // Vertical connector under the dot.
          Padding(
            padding: const EdgeInsetsDirectional.only(start: 6),
            child: Align(
              alignment: AlignmentDirectional.centerStart,
              child: _dashedConnector(theme),
            ),
          ),
          InkWell(
            borderRadius: BorderRadius.circular(12),
            onTap: () => _startBooking('ride'),
            child: Row(
              children: [
                // Container(
                //   width: 30,
                //   height: 30,
                //   alignment: Alignment.center,
                //   decoration: BoxDecoration(
                //     color: theme.colorScheme.onSurface,
                //     borderRadius: BorderRadius.circular(8),
                //   ),
                //   child: Icon(Icons.place,
                //       size: 18, color: theme.scaffoldBackgroundColor),
                // ),
                Container(
                  width: 14,
                  height: 14,
                  decoration: BoxDecoration(
                    color: AppColors.textPrimary,
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(4.0),
                    child: Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(1),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'home.where_to'.tr(),
                        style: TextStyle(
                          color: theme.colorScheme.onSurface,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'home.search_destination'.tr(),
                        style: TextStyle(
                          color: theme.colorScheme.onSurface.withValues(
                            alpha: 0.5,
                          ),
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
                Icon(
                  Icons.chevron_right,
                  color: theme.colorScheme.onSurface.withValues(alpha: 0.5),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _dashedConnector(ThemeData theme) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 0),
      child: Column(
        children: List.generate(
          5,
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

  // ---------------------------------------------------------------------------
  // 3. Promo banner
  // ---------------------------------------------------------------------------
  Widget _promoBanner(ThemeData theme) {
    return Column(
      children: [
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            gradient: const LinearGradient(
              colors: [AppColors.primary, AppColors.primaryDark],
            ),
          ),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'home.banner_title'.tr(),
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 20,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'home.banner_subtitle'.tr(),
                      style: const TextStyle(
                        color: Colors.white70,
                        fontSize: 13,
                      ),
                    ),
                    const SizedBox(height: 14),
                    Material(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(8),
                      child: InkWell(
                        borderRadius: BorderRadius.circular(8),
                        onTap: () => SnackbarHelper.showInfo(
                          context,
                          'home.learn_more'.tr(),
                        ),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 14,
                            vertical: 8,
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                'home.learn_more'.tr(),
                                style: const TextStyle(
                                  color: AppColors.primary,
                                  fontWeight: FontWeight.w600,
                                  fontSize: 13,
                                ),
                              ),
                              const SizedBox(width: 4),
                              const Icon(
                                Icons.arrow_forward,
                                size: 14,
                                color: AppColors.primary,
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const Icon(Icons.local_taxi, size: 64, color: Colors.white24),
            ],
          ),
        ),
        const SizedBox(height: 12),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: List.generate(1, (i) {
            return Container(
              width: 6,
              height: 6,
              margin: const EdgeInsets.symmetric(horizontal: 3),
              decoration: BoxDecoration(
                color: i == 0 ? AppColors.primary : theme.dividerColor,
                shape: BoxShape.circle,
              ),
            );
          }),
        ),
      ],
    );
  }

  // ---------------------------------------------------------------------------
  // 4. Section header
  // ---------------------------------------------------------------------------
  Widget _sectionHeader(
    ThemeData theme, {
    required String title,
    required String trailing,
    required VoidCallback onTrailing,
  }) {
    return Row(
      children: [
        Text(
          title,
          style: TextStyle(
            color: theme.colorScheme.onSurface,
            fontSize: 17,
            fontWeight: FontWeight.bold,
          ),
        ),
        const Spacer(),
        InkWell(
          borderRadius: BorderRadius.circular(8),
          onTap: onTrailing,
          child: Row(
            children: [
              Text(
                trailing,
                style: const TextStyle(
                  color: AppColors.primary,
                  fontWeight: FontWeight.w600,
                  fontSize: 13,
                ),
              ),
              const Icon(
                Icons.chevron_right,
                size: 18,
                color: AppColors.primary,
              ),
            ],
          ),
        ),
      ],
    );
  }

  // ---------------------------------------------------------------------------
  // 5. Service cards
  // ---------------------------------------------------------------------------
  Widget _serviceCards(ThemeData theme) {
    final services = context.watch<ServiceProvider>().services;
    // Fall back to the built-in cards until the API responds (or if it's empty
    // / fails), so the home never shows a blank services row.
    if (services.isEmpty) return _staticServiceCards(theme);

    final children = <Widget>[];
    for (var i = 0; i < services.length; i++) {
      if (i > 0) children.add(const SizedBox(width: 12));
      children.add(Expanded(child: _serviceCardFor(theme, services[i])));
    }
    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: children,
      ),
    );
  }

  /// Build a service card from an API [ServiceModel]. Routes by `type`:
  /// parcel → parcel booking, anything else → ride flow.
  Widget _serviceCardFor(ThemeData theme, ServiceModel s) {
    final isParcel = s.type == 'parcel';
    return _serviceCard(
      theme,
      icon: _serviceIcon(s.type),
      iconUrl: s.icon,
      title: s.name,
      desc: (s.description != null && s.description!.isNotEmpty)
          ? s.description!
          : _serviceDesc(s.type),
      titleColor: isParcel ? AppColors.primary : null,
      onTap: () => _startBooking(_bookingTypeFor(s)),
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

  /// Parcels can always be ordered (multiple deliveries allowed). A new ride
  /// is blocked only while a ride is already in progress — in that case we
  /// redirect to the ongoing ride instead.
  Future<void> _startBooking(String type) async {
    final home = context.read<HomeProvider>();

    if (type == 'parcel') {
      home.selectService('parcel');
      context.push(RouteNames.parcelBooking);
      return;
    }

    // Ride: redirect only if there's an ongoing RIDE (an active parcel doesn't
    // block taking a ride).
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

  /// Original hard-coded services, used as a fallback before/without the API.
  Widget _staticServiceCards(ThemeData theme) {
    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Expanded(
            child: _serviceCard(
              theme,
              icon: Icons.directions_car,
              title: 'home.ride'.tr(),
              desc: 'home.ride_desc'.tr(),
              onTap: () => _startBooking('bike'),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: _serviceCard(
              theme,
              icon: Icons.two_wheeler,
              title: 'home.bike'.tr(),
              desc: 'home.bike_desc'.tr(),
              onTap: () => _startBooking('ride'),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: _serviceCard(
              theme,
              icon: Icons.inventory_2_outlined,
              title: 'home.courier'.tr(),
              desc: 'home.courier_desc'.tr(),
              titleColor: AppColors.primary,
              onTap: () => _startBooking('parcel'),
            ),
          ),
        ],
      ),
    );
  }

  Widget _serviceCard(
    ThemeData theme, {
    required IconData icon,
    required String title,
    required String desc,
    required VoidCallback onTap,
    String? iconUrl,
    Color? titleColor,
  }) {
    return Material(
      color: theme.cardColor,

      borderRadius: BorderRadius.circular(16),
      child: GestureDetector(
        // borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            children: [
              Container(
                width: 100,
                height: 100,
                alignment: Alignment.center,

                // decoration: BoxDecoration(
                //   color: AppColors.primary.withValues(alpha: 0.1),
                //   shape: BoxShape.circle,
                // ),
                child: (iconUrl != null && iconUrl.isNotEmpty)
                    ? Image.network(
                        iconUrl,
                        width: 90,
                        height: 90,
                        fit: BoxFit.contain,
                        // Backend icon URL unreachable (e.g. 127.0.0.1) →
                        // fall back to the type-based icon.
                        errorBuilder: (context, error, stack) =>
                            Icon(icon, color: AppColors.primary),
                      )
                    : Icon(icon, color: AppColors.primary),
              ),
              const SizedBox(height: 5),
              Text(
                title,
                style: TextStyle(
                  color: titleColor ?? theme.colorScheme.onSurface,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                desc,
                textAlign: TextAlign.center,
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

  // ---------------------------------------------------------------------------
  // 6. Quick actions
  // ---------------------------------------------------------------------------
  Widget _quickActions(ThemeData theme) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(16),
      ),
      child: IntrinsicHeight(
        child: Row(
          children: [
            _quickAction(
              theme,
              icon: Icons.local_offer_outlined,
              label: 'home.offers'.tr(),
              onTap: () => context.push(RouteNames.offers),
            ),
            _quickDivider(theme),
            _quickAction(
              theme,
              icon: Icons.verified_user_outlined,
              label: 'home.safety'.tr(),
              onTap: () => context.push(RouteNames.safety),
            ),
            _quickDivider(theme),
            _quickAction(
              theme,
              icon: Icons.account_balance_wallet_outlined,
              label: 'home.wallet'.tr(),
              onTap: () => context.go(RouteNames.wallet),
            ),
            _quickDivider(theme),
            _quickAction(
              theme,
              icon: Icons.headset_mic_outlined,
              label: 'home.help_center'.tr(),
              onTap: () => context.push(RouteNames.helpCenter),
            ),
          ],
        ),
      ),
    );
  }

  Widget _quickDivider(ThemeData theme) => VerticalDivider(
    width: 1,
    color: theme.dividerColor.withValues(alpha: 0.1),
    indent: 4,
    endIndent: 4,
  );

  Widget _quickAction(
    ThemeData theme, {
    required IconData icon,
    required String label,
    required VoidCallback onTap,
  }) {
    return Expanded(
      child: InkWell(
        onTap: onTap,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: AppColors.primary),
            const SizedBox(height: 6),
            Text(
              label,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: theme.colorScheme.onSurface,
                fontSize: 12,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // 8. Recent activity
  // ---------------------------------------------------------------------------
  Widget _recentActivity(ThemeData theme) {
    final history = context.watch<HistoryProvider>();
    if (history.orders.isEmpty) return const SizedBox.shrink();
    final order = history.orders.first;
    final isCompleted = order.status == 'completed';
    final title = order.orderNumber.isNotEmpty
        ? '#${order.orderNumber}'
        : '${order.pickupAddress} → ${order.dropAddress}';
    return Material(
      color: theme.cardColor,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => context.push(RouteNames.orderDetail, extra: order.id),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              CircleAvatar(
                radius: 22,
                backgroundColor: AppColors.primary.withValues(alpha: 0.1),
                child: const Icon(
                  Icons.directions_car,
                  color: AppColors.primary,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: theme.colorScheme.onSurface,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      order.createdAt,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: theme.colorScheme.onSurface.withValues(
                          alpha: 0.5,
                        ),
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    Helpers.currency(double.tryParse(order.totalAmount) ?? 0),
                    style: TextStyle(
                      color: theme.colorScheme.onSurface,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  if (isCompleted) ...[
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.green.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        'home.status_completed'.tr(),
                        style: const TextStyle(
                          color: Colors.green,
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
              Icon(
                Icons.chevron_right,
                color: theme.colorScheme.onSurface.withValues(alpha: 0.5),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
