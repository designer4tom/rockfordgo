import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/services/route_service.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/widgets/primary_action_button.dart';
import '../../../../core/widgets/section_header.dart';
import '../../../auth/provider/auth_provider.dart';
import '../../../home/provider/home_provider.dart';
import '../../../service/model/vehicle_category_model.dart';
import '../../../service/provider/service_provider.dart';
import '../../provider/ride_provider.dart';
import '../widgets/coupon_input.dart';
import '../widgets/payment_method_selector.dart';
import '../widgets/schedule_picker.dart';
import '../widgets/vehicle_option_card.dart';

class VehicleSelectScreen extends StatefulWidget {
  const VehicleSelectScreen({super.key});

  @override
  State<VehicleSelectScreen> createState() => _VehicleSelectScreenState();
}

class _VehicleSelectScreenState extends State<VehicleSelectScreen> {
  List<LatLng> _routePoints = [];
  GoogleMapController? _mapController;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    final ride = context.read<RideProvider>();
    final service = context.read<ServiceProvider>();
    final pickup = ride.pickup;
    final drop = ride.drop;
    if (pickup == null) return;

    // Fetch the road-following route in parallel with the fare estimates.
    if (drop != null) {
      RouteService()
          .getRoutePolyline(
            LatLng(pickup.lat, pickup.lng),
            LatLng(drop.lat, drop.lng),
          )
          .then((points) {
            if (mounted) setState(() => _routePoints = points);
          });
    }

    await service.loadVehicleCategories(pickup.lat, pickup.lng);
    if (!mounted) return;
    final categories = service.vehicleCategories;
    // Match the initial vehicle selection to the service card the customer
    // tapped (Ride or Bike). The remaining choices are still available.
    if (categories.isNotEmpty) {
      final selectedService = context.read<HomeProvider>().selectedService;
      final preferred = _preferredCategory(categories, selectedService);
      ride.selectCategory(preferred.id);
    }
    final ids = categories.map((c) => c.id).toList();
    await ride.loadFareEstimates(ids);
  }

  VehicleCategoryModel _preferredCategory(
    List<VehicleCategoryModel> categories,
    String service,
  ) {
    final wantsBike = service.toLowerCase() == 'bike';
    return categories.firstWhere(
      (category) => _isBikeCategory(category) == wantsBike,
      orElse: () => categories.first,
    );
  }

  bool _isBikeCategory(VehicleCategoryModel category) {
    final name = category.name.toLowerCase();
    return name.contains('bike') ||
        name.contains('motor') ||
        name.contains('scooter') ||
        name.contains('two wheeler') ||
        name.contains('two_wheeler');
  }

  @override
  void dispose() {
    _mapController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final ride = context.watch<RideProvider>();
    final service = context.watch<ServiceProvider>();
    final categories = service.vehicleCategories;
    final theme = Theme.of(context);

    final walletBalance =
        double.tryParse(
          context.select<AuthProvider, String>(
            (p) => p.user?.walletBalance ?? '0.00',
          ),
        ) ??
        0;

    return Scaffold(
      appBar: AppBar(centerTitle: true, title: Text('ride.title'.tr())),
      body: Column(
        children: [
          // The map + location card + header now scroll together with the
          // vehicle list so the screen never overflows on shorter devices.
          // The bottom bar stays pinned.
          Expanded(
            child: ListView(
              padding: const EdgeInsets.only(bottom: 8),
              children: [
                // Keep the route preview in its own layout slot. Previously
                // the location card was stacked over the map, which could
                // obscure the preview after the trip details refreshed.
                _locationCard(theme, ride),
                const SizedBox(height: 8),
                _mapPreview(theme, ride),
                _chooseHeader(theme),
                if (service.isLoading)
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 40),
                    child: Center(child: CircularProgressIndicator()),
                  )
                else
                  ...categories.map((c) {
                    final fare = ride.fareForCategory(c.id);
                    return Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: VehicleOptionCard(
                        category: c,
                        selected: ride.selectedCategoryId == c.id,
                        fareLoading: ride.isFareLoading,
                        fareText: Helpers.currency(
                          double.tryParse(fare?.finalFare ?? '0') ?? 0,
                        ),
                        surge: fare?.surgeActive == true,
                        onTap: () => ride.selectCategory(c.id),
                      ),
                    );
                  }),
                const SizedBox(height: 4),
              ],
            ),
          ),
          _bottomBar(theme, ride, walletBalance),
        ],
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // 1. Location card
  // ---------------------------------------------------------------------------
  Widget _locationCard(ThemeData theme, RideProvider ride) {
    final onSurface = theme.colorScheme.onSurface;
    return Container(
      height: 140,
      margin: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 6,
            offset: const Offset(0, 3),
          ),
        ],
        // border: Border.all(color: theme.dividerColor),
      ),
      padding: const EdgeInsetsDirectional.fromSTEB(16, 14, 14, 14),
      child: Column(
        children: [
          // Pickup row
          Row(
            children: [
              Container(
                width: 14,
                height: 14,
                decoration: const BoxDecoration(
                  color: AppColors.primary,
                  shape: BoxShape.circle,
                ),
                child: Center(
                  child: Container(
                    width: 5,
                    height: 5,
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'ride.pickup_location'.tr(),
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      ride.pickup?.address ?? '—',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontWeight: FontWeight.w500,
                        color: onSurface,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(8),
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
                    const SizedBox(width: 6),
                    Text(
                      'ride.now'.tr(),
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                        color: onSurface,
                      ),
                    ),
                    const Icon(
                      Icons.keyboard_arrow_down,
                      size: 16,
                      color: AppColors.textSecondary,
                    ),
                  ],
                ),
              ),
            ],
          ),
          // Dashed connector
          Padding(
            padding: const EdgeInsetsDirectional.only(start: 6),
            child: Align(
              alignment: AlignmentDirectional.centerStart,
              child: _dashedConnector(theme),
            ),
          ),
          // Drop row
          Row(
            children: [
              Container(
                width: 14,
                height: 14,
                decoration: BoxDecoration(
                  color: onSurface,
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
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'ride.drop_location'.tr(),
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      ride.drop?.address ?? '—',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontWeight: FontWeight.w500,
                        color: onSurface,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              InkWell(
                onTap: () => context.push(RouteNames.setDestination),
                customBorder: const CircleBorder(),
                child: Container(
                  width: 32,
                  height: 32,
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.08),
                        blurRadius: 6,
                        offset: const Offset(0, 3),
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.add,
                    size: 20,
                    color: AppColors.primary,
                  ),
                ),
              ),
            ],
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
  // 2. Map
  // ---------------------------------------------------------------------------
  Widget _mapPreview(ThemeData theme, RideProvider ride) {
    final pickup = ride.pickup;
    final drop = ride.drop;
    if (pickup == null || drop == null) return const SizedBox.shrink();

    final pickupLatLng = LatLng(pickup.lat, pickup.lng);
    final dropLatLng = LatLng(drop.lat, drop.lng);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: SizedBox(
          height: 240,
          child: Stack(
            children: [
              Positioned.fill(
                child: GoogleMap(
                  key: ValueKey(
                    'trip-map-${pickup.lat}-${pickup.lng}-${drop.lat}-${drop.lng}',
                  ),
                  initialCameraPosition: CameraPosition(
                    target: pickupLatLng,
                    zoom: 13,
                  ),
                  onMapCreated: (controller) {
                    _mapController = controller;
                    _showEntireTrip(pickupLatLng, dropLatLng);
                  },
                  markers: {
                    Marker(
                      markerId: const MarkerId('pickup'),
                      position: pickupLatLng,
                    ),
                    Marker(
                      markerId: const MarkerId('drop'),
                      position: dropLatLng,
                      icon: BitmapDescriptor.defaultMarkerWithHue(
                        BitmapDescriptor.hueRed,
                      ),
                    ),
                  },
                  polylines: {
                    Polyline(
                      polylineId: const PolylineId('route'),
                      points: _routePoints.isNotEmpty
                          ? _routePoints
                          : [pickupLatLng, dropLatLng],
                      color: AppColors.primary,
                      width: 4,
                    ),
                  },
                  zoomControlsEnabled: false,
                  myLocationButtonEnabled: false,
                ),
              ),
              PositionedDirectional(
                start: 12,
                bottom: 12,
                child: Material(
                  color: Colors.white,
                  shape: const CircleBorder(),
                  elevation: 2,
                  child: InkWell(
                    customBorder: const CircleBorder(),
                    onTap: () {},
                    child: const SizedBox(
                      width: 40,
                      height: 40,
                      child: Icon(
                        Icons.my_location,
                        size: 20,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _showEntireTrip(LatLng pickup, LatLng drop) async {
    final controller = _mapController;
    if (controller == null) return;

    // A small delay lets the platform map finish laying out before its camera
    // is moved. It also keeps both markers visible for very short trips.
    await Future<void>.delayed(const Duration(milliseconds: 200));
    if (!mounted || controller != _mapController) return;

    final south = pickup.latitude < drop.latitude
        ? pickup.latitude
        : drop.latitude;
    final north = pickup.latitude > drop.latitude
        ? pickup.latitude
        : drop.latitude;
    final west = pickup.longitude < drop.longitude
        ? pickup.longitude
        : drop.longitude;
    final east = pickup.longitude > drop.longitude
        ? pickup.longitude
        : drop.longitude;

    // Google Maps requires non-zero bounds. Padding prevents a coincident or
    // near-coincident pickup/drop pair from producing an invalid camera move.
    const padding = 0.002;
    try {
      await controller.animateCamera(
        CameraUpdate.newLatLngBounds(
          LatLngBounds(
            southwest: LatLng(south - padding, west - padding),
            northeast: LatLng(north + padding, east + padding),
          ),
          48,
        ),
      );
    } catch (_) {
      // The initial pickup-centred camera remains usable if the platform map
      // is temporarily unavailable while the screen is being laid out.
    }
  }

  // ---------------------------------------------------------------------------
  // 3. Choose-ride header
  // ---------------------------------------------------------------------------
  Widget _chooseHeader(ThemeData theme) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      child: SectionHeader(
        title: 'ride.choose_ride'.tr(),
        action: 'ride.see_all'.tr(),
        onAction: _openAllVehicles,
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // 5. Promo code card
  // ---------------------------------------------------------------------------
  Widget _promoCard(ThemeData theme, RideProvider ride) {
    final onSurface = theme.colorScheme.onSurface;
    final coupon = ride.appliedCoupon;
    final applied = coupon != null;
    return GestureDetector(
      onTap: () => _openCoupon(ride),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: applied
              ? AppColors.success.withValues(alpha: 0.06)
              : theme.cardColor,
          borderRadius: BorderRadius.circular(12),

          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              blurRadius: 6,
              offset: const Offset(0, 3),
            ),
          ],

          // border: Border.all(
          //     color: applied ? AppColors.success : theme.dividerColor),
        ),
        child: Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: (applied ? AppColors.success : AppColors.primary)
                    .withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(
                Icons.local_offer_outlined,
                color: applied ? AppColors.success : AppColors.primary,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    applied
                        ? 'ride.coupon_applied_code'.tr(
                            namedArgs: {'code': coupon.code},
                          )
                        : 'ride.promo_title'.tr(),
                    style: TextStyle(
                      fontWeight: FontWeight.w600,
                      color: onSurface,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    applied
                        ? '- ${Helpers.currency(ride.couponDiscount)}'
                        : 'ride.promo_subtitle'.tr(),
                    style: TextStyle(
                      fontSize: 12,
                      color: applied
                          ? AppColors.success
                          : AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 8),
            applied
                ? TextButton(
                    onPressed: ride.removeCoupon,
                    child: Text(
                      'remove'.tr(),
                      style: const TextStyle(color: AppColors.danger),
                    ),
                  )
                : Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        'ride.add_code'.tr(),
                        style: const TextStyle(
                          color: AppColors.primary,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const Icon(
                        Icons.chevron_right,
                        size: 18,
                        color: AppColors.primary,
                      ),
                    ],
                  ),
          ],
        ),
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // 6. Wallet + Schedule row
  // ---------------------------------------------------------------------------
  Widget _walletScheduleRow(
    ThemeData theme,
    RideProvider ride,
    double walletBalance,
  ) {
    final onSurface = theme.colorScheme.onSurface;
    final isWallet = ride.paymentMethod == 'wallet';
    final scheduled = ride.scheduledAt != null;
    return Container(
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(12),
        // border: Border.all(color: theme.dividerColor),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 6,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: IntrinsicHeight(
        child: Row(
          children: [
            Expanded(
              child: InkWell(
                onTap: () => _openPayment(ride, walletBalance),
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Row(
                    children: [
                      Icon(
                        _paymentIcon(ride.paymentMethod),
                        size: 20,
                        color: AppColors.primary,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _paymentLabel(ride.paymentMethod),
                              style: const TextStyle(
                                fontSize: 12,
                                color: AppColors.textSecondary,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              isWallet
                                  ? Helpers.currency(walletBalance)
                                  : 'ride.tap_to_change'.tr(),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontWeight: FontWeight.w600,
                                color: onSurface,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const Icon(
                        Icons.keyboard_arrow_down,
                        size: 18,
                        color: AppColors.textSecondary,
                      ),
                    ],
                  ),
                ),
              ),
            ),
            VerticalDivider(
              width: 1,
              color: theme.dividerColor.withValues(alpha: 0.3),
            ),
            Expanded(
              child: InkWell(
                onTap: () => _openSchedule(ride),
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Row(
                    children: [
                      const Icon(
                        Icons.calendar_today_outlined,
                        size: 18,
                        color: AppColors.primary,
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          scheduled
                              ? Helpers.formatDateTime(ride.scheduledAt!)
                              : 'ride.schedule_ride'.tr(),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontWeight: FontWeight.w500,
                            color: onSurface,
                          ),
                        ),
                      ),
                      const Icon(
                        Icons.keyboard_arrow_down,
                        size: 18,
                        color: AppColors.textSecondary,
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  IconData _paymentIcon(String method) => switch (method) {
    'wallet' => Icons.account_balance_wallet_outlined,
    'online' => Icons.credit_card,
    _ => Icons.payments_outlined,
  };

  String _paymentLabel(String method) => switch (method) {
    'wallet' => 'ride.wallet'.tr(),
    'online' => 'online'.tr(),
    _ => 'cash'.tr(),
  };

  // ---------------------------------------------------------------------------
  // Inline bottom sheets (coupon / payment / schedule) — replaces the old
  // separate Booking Confirm screen.
  // ---------------------------------------------------------------------------
  Future<void> _sheet(Widget child) {
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        top: false,
        child: Padding(
          padding: EdgeInsets.only(
            left: 16,
            right: 16,
            top: 16,
            bottom: 16 + MediaQuery.of(ctx).viewInsets.bottom,
          ),
          child: child,
        ),
      ),
    );
  }

  void _openCoupon(RideProvider ride) {
    _sheet(
      Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'coupon'.tr(),
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Consumer<RideProvider>(
            builder: (ctx, r, _) => CouponInput(
              appliedCoupon: r.appliedCoupon,
              onApply: (code) async {
                final ok = await r.applyCoupon(code);
                if (!ctx.mounted) return false;
                if (ok) {
                  SnackbarHelper.showSuccess(ctx, 'coupon_applied'.tr());
                  Navigator.pop(ctx);
                } else {
                  SnackbarHelper.showError(
                    ctx,
                    r.error ?? 'coupon_invalid'.tr(),
                  );
                }
                return r.appliedCoupon != null;
              },
              onRemove: () {
                r.removeCoupon();
                Navigator.pop(ctx);
              },
            ),
          ),
        ],
      ),
    );
  }

  void _openPayment(RideProvider ride, double walletBalance) {
    _sheet(
      Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'payment_method'.tr(),
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Consumer<RideProvider>(
            builder: (ctx, r, _) => PaymentMethodSelector(
              selected: r.paymentMethod,
              walletBalance: walletBalance,
              payableAmount: r.payableAmount,
              onChanged: (m) {
                r.setPaymentMethod(m);
                Navigator.pop(ctx);
              },
              onTopUp: () {
                Navigator.pop(ctx);
                context.push('/topup');
              },
            ),
          ),
        ],
      ),
    );
  }

  void _openSchedule(RideProvider ride) {
    _sheet(
      Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'schedule'.tr(),
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Consumer<RideProvider>(
            builder: (ctx, r, _) => SchedulePicker(
              scheduledAt: r.scheduledAt,
              onScheduled: r.setSchedule,
            ),
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () => Navigator.pop(context),
              child: Text('common.done'.tr()),
            ),
          ),
        ],
      ),
    );
  }

  // Bottom sheet listing every available ride so the user can pick one.
  void _openAllVehicles() {
    final service = context.read<ServiceProvider>();
    _sheet(
      Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'ride.choose_ride'.tr(),
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Flexible(
            child: Consumer<RideProvider>(
              builder: (ctx, r, _) {
                final categories = service.vehicleCategories;
                return ListView(
                  shrinkWrap: true,
                  children: [
                    ...categories.map((c) {
                      final fare = r.fareForCategory(c.id);
                      return VehicleOptionCard(
                        category: c,
                        selected: r.selectedCategoryId == c.id,
                        fareLoading: r.isFareLoading,
                        fareText: Helpers.currency(
                          double.tryParse(fare?.finalFare ?? '0') ?? 0,
                        ),
                        surge: fare?.surgeActive == true,
                        onTap: () {
                          r.selectCategory(c.id);
                          Navigator.pop(ctx);
                        },
                      );
                    }),
                  ],
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // 7. Bottom bar
  // ---------------------------------------------------------------------------
  Widget _bottomBar(ThemeData theme, RideProvider ride, double walletBalance) {
    final enabled = ride.selectedCategoryId != null;
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            _promoCard(theme, ride),
            const SizedBox(height: 12),
            _walletScheduleRow(theme, ride, walletBalance),
            const SizedBox(height: 12),
            _priceBreakdown(theme, ride),
            PrimaryActionButton(
              label: ride.scheduledAt != null
                  ? 'ride.schedule_ride'.tr()
                  : 'ride.confirm_ride'.tr(),
              isLoading: ride.isLoading,
              onPressed: enabled ? _confirm : null,
              trailing: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    Helpers.currency(ride.payableAmount),
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(width: 6),
                  const Icon(Icons.arrow_forward, size: 20),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ---------------------------------------------------------------------------
  // Live price breakdown — shown once a coupon discount applies, so the rider
  // sees subtotal → discount → total update in real time.
  // ---------------------------------------------------------------------------
  Widget _priceBreakdown(ThemeData theme, RideProvider ride) {
    final fare = ride.selectedFare;
    final coupon = ride.appliedCoupon;
    final discount = ride.couponDiscount;
    if (fare == null || coupon == null || discount <= 0) {
      return const SizedBox.shrink();
    }
    final subtotal = double.tryParse(fare.finalFare) ?? 0;
    final onSurface = theme.colorScheme.onSurface;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: theme.cardColor,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 6,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        children: [
          _priceRow(
            'ride.subtotal'.tr(),
            Helpers.currency(subtotal),
            color: AppColors.textSecondary,
          ),
          const SizedBox(height: 6),
          _priceRow(
            '${'ride.coupon_discount'.tr()} (${coupon.code})',
            '- ${Helpers.currency(discount)}',
            color: AppColors.success,
            valueColor: AppColors.success,
          ),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 8),
            child: Divider(height: 1, color: theme.dividerColor),
          ),
          _priceRow(
            'ride.total'.tr(),
            Helpers.currency(ride.payableAmount),
            color: onSurface,
            valueColor: AppColors.primary,
            bold: true,
          ),
        ],
      ),
    );
  }

  Widget _priceRow(
    String label,
    String value, {
    Color? color,
    Color? valueColor,
    bool bold = false,
  }) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            color: color,
            fontSize: bold ? 15 : 13,
            fontWeight: bold ? FontWeight.bold : FontWeight.w500,
          ),
        ),
        Text(
          value,
          style: TextStyle(
            color: valueColor ?? color,
            fontSize: bold ? 16 : 13,
            fontWeight: bold ? FontWeight.bold : FontWeight.w600,
          ),
        ),
      ],
    );
  }

  Future<void> _confirm() async {
    final ride = context.read<RideProvider>();
    final ok = await ride.bookRide();
    if (!mounted) return;
    if (ok) {
      context.push('/searching-driver');
    } else {
      SnackbarHelper.showError(
        context,
        ride.error ?? 'ride.booking_failed'.tr(),
      );
    }
  }
}
