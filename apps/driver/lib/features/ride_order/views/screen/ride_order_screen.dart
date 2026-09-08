import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/models/place_info.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/services/sos_service.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../../core/utils/helpers.dart';
import '../../../chat/provider/chat_provider.dart';
import '../../../home/provider/home_provider.dart';
import '../../model/active_ride_model.dart';
import '../../provider/ride_order_provider.dart';
import '../widgets/customer_info_card.dart';
import '../widgets/navigation_button.dart';
import '../widgets/order_map.dart';
import '../widgets/otp_verify_sheet.dart';
import '../widgets/status_action_button.dart';

class RideOrderScreen extends StatefulWidget {
  final int orderId;
  const RideOrderScreen({super.key, required this.orderId});

  @override
  State<RideOrderScreen> createState() => _RideOrderScreenState();
}

class _RideOrderScreenState extends State<RideOrderScreen> {
  bool _cancelHandled = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<RideOrderProvider>().loadActiveOrder(widget.orderId);
      context.read<ChatProvider>().loadForOrder(widget.orderId);
    });
  }

  void _onCancelled(RideOrderProvider p) {
    if (_cancelHandled || !mounted) return;
    _cancelHandled = true;
    p.clear();
    context.read<ChatProvider>().clear();
    context.read<HomeProvider>().setTripActive(false);
    AppSnackbar.show(context, 'ride.order_cancelled'.tr());
    context.go(RouteNames.home);
  }

  bool get _beforePickup {
    final s = context.read<RideOrderProvider>().currentStatus;
    return s == RideStatus.accepted ||
        s == RideStatus.goToPickup ||
        s == RideStatus.confirmArrival;
  }

  Future<void> _arrived(RideOrderProvider p) =>
      p.updateStatus(RideStatus.confirmArrival);

  Future<void> _verifyPickup(RideOrderProvider p) async {
    if (!(p.activeRide?.otpRequired ?? true)) {
      // OTP disabled by admin → skip straight to picked_up.
      await p.updateStatus(RideStatus.pickedUp);
      return;
    }
    final ok = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Theme.of(context).cardColor,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => OtpVerifySheet(onVerify: p.verifyOtpAndPickup),
    );
    if (ok == true && mounted) {
      AppSnackbar.success(context, 'ride.pickup_confirmed'.tr());
    }
  }

  Future<void> _complete(RideOrderProvider p) async {
    final ok = await p.completeRide();
    if (!mounted) return;
    if (ok) {
      context.read<HomeProvider>().setTripActive(false);
      context.read<ChatProvider>().clear();
      context.go('/ride-complete', extra: widget.orderId);
    } else {
      AppSnackbar.error(context, p.error ?? 'ride.could_not_complete'.tr());
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<RideOrderProvider>();
    final ride = p.activeRide;

    if (p.cancelledByCustomer) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _onCancelled(p));
    }

    if (p.loading && ride == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (ride == null) {
      return Scaffold(
        appBar: AppBar(),
        body: Center(child: Text(p.error ?? 'ride.order_not_found'.tr())),
      );
    }

    final driverPos = context.watch<HomeProvider>().currentPosition;
    final chat = context.watch<ChatProvider>();
    final target = _beforePickup ? ride.pickup : ride.drop;

    return Scaffold(
      body: Stack(
        children: [
          Positioned.fill(
            child: OrderMap(
              driver: driverPos,
              target: target,
              targetLabel:
                  _beforePickup ? 'ride.pickup'.tr() : 'ride.drop_off'.tr(),
            ),
          ),
          // Navigate + SOS row.
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  _sosButton(),
                  NavigationButton(
                    onTap: p.openNavigation,
                    label: _beforePickup
                        ? 'ride.to_pickup'.tr()
                        : 'ride.to_drop'.tr(),
                  ),
                ],
              ),
            ),
          ),
          Align(
            alignment: Alignment.bottomCenter,
            child: _bottomSheet(p, ride, target, chat),
          ),
        ],
      ),
    );
  }

  Widget _sosButton() {
    return Material(
      color: AppColors.danger,
      shape: const CircleBorder(),
      elevation: 3,
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: _triggerSos,
        child: const Padding(
          padding: EdgeInsets.all(12),
          child: Icon(Icons.sos, color: Colors.white),
        ),
      ),
    );
  }

  void _onMessageTap(ChatProvider chat) {
    if (chat.loadingIcon) return;
    if (!chat.hasChat) {
      AppSnackbar.show(context, chat.error ?? 'ride.chat_not_ready'.tr());
      return;
    }
    context.push(RouteNames.chat);
  }

  Future<void> _triggerSos() async {
    final orderId = context.read<RideOrderProvider>().activeRide?.orderId;
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('ride.send_sos_q'.tr()),
        content: Text('ride.sos_alert_message'.tr()),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text('common.cancel'.tr())),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text('ride.send_sos'.tr(),
                style: const TextStyle(color: AppColors.danger)),
          ),
        ],
      ),
    );
    if (confirm != true) return;
    final ok = await SosService.instance.triggerSos(orderId: orderId);
    if (!mounted) return;
    AppSnackbar.show(
        context, ok ? 'ride.sos_sent'.tr() : 'ride.sos_failed'.tr());
  }

  Widget _bottomSheet(RideOrderProvider p, ActiveRideModel ride,
      PlaceInfo target, ChatProvider chat) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
        boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 12)],
      ),
      child: SafeArea(
        top: false,
        // Constrain + scroll so a tall step (fare + payment + action button)
        // never overflows / pushes off-screen on small devices.
        child: ConstrainedBox(
          constraints: BoxConstraints(
            maxHeight: MediaQuery.of(context).size.height * 0.6,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: _stepContent(p, ride, target, chat),
            ),
          ),
        ),
      ),
    );
  }

  List<Widget> _stepContent(RideOrderProvider p, ActiveRideModel ride,
      PlaceInfo target, ChatProvider chat) {
    switch (ride.status) {
      case RideStatus.accepted:
      case RideStatus.goToPickup:
        return [
          CustomerInfoCard(
            name: ride.customer.name,
            phone: ride.customer.phone,
            avatar: ride.customer.avatar,
            rating: ride.customer.rating,
            onCall: p.callCustomer,
            onMessage: () => _onMessageTap(chat),
            messageLoading: chat.loadingIcon,
            unreadCount: chat.unreadBadge,
          ),
          const SizedBox(height: 12),
          _addressRow(Icons.my_location, 'ride.pickup'.tr(), target.address),
          const SizedBox(height: 16),
          StatusActionButton(
            label: 'ride.arrived_at_pickup'.tr(),
            loading: p.updating,
            onConfirm: () => _arrived(p),
          ),
        ];
      case RideStatus.confirmArrival:
        return [
          CustomerInfoCard(
            name: ride.customer.name,
            phone: ride.customer.phone,
            avatar: ride.customer.avatar,
            rating: ride.customer.rating,
            onCall: p.callCustomer,
            onMessage: () => _onMessageTap(chat),
            messageLoading: chat.loadingIcon,
            unreadCount: chat.unreadBadge,
          ),
          const SizedBox(height: 12),
          Text('ride.ask_customer_otp'.tr(),
              style: TextStyle(color: Theme.of(context).hintColor)),
          const SizedBox(height: 16),
          StatusActionButton(
            label: ride.otpRequired
                ? 'ride.verify_otp_start'.tr()
                : 'ride.confirm_pickup'.tr(),
            icon: Icons.verified_user,
            loading: p.updating,
            slideToConfirm: false,
            onConfirm: () => _verifyPickup(p),
          ),
        ];
      case RideStatus.pickedUp:
        return [
          _addressRow(Icons.location_on, 'ride.drop_off'.tr(), ride.drop.address),
          const SizedBox(height: 16),
          StatusActionButton(
            label: 'ride.start_ride'.tr(),
            loading: p.updating,
            onConfirm: () => p.updateStatus(RideStatus.startRide),
          ),
        ];
      case RideStatus.startRide:
        return [
          _addressRow(Icons.location_on, 'ride.drop_off'.tr(), ride.drop.address),
          const SizedBox(height: 16),
          StatusActionButton(
            label: 'ride.reached_destination'.tr(),
            loading: p.updating,
            onConfirm: () => p.updateStatus(RideStatus.droppedOff),
          ),
        ];
      case RideStatus.droppedOff:
        return [
          _fareSummary(ride),
          const SizedBox(height: 12),
          _paymentBox(ride),
          const SizedBox(height: 16),
          StatusActionButton(
            label: ride.isCash
                ? 'ride.cash_received_complete'.tr()
                : 'ride.complete_ride'.tr(),
            icon: Icons.check,
            loading: p.updating,
            color: AppColors.success,
            onConfirm: () => _complete(p),
          ),
        ];
      default:
        return [
          Center(child: Text('ride.finalizing'.tr())),
        ];
    }
  }

  Widget _addressRow(IconData icon, String label, String address) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, color: AppColors.primary),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label,
                  style: TextStyle(
                      fontSize: 12, color: Theme.of(context).hintColor)),
              Text(address.isEmpty ? '—' : address,
                  style: const TextStyle(
                      fontSize: 15, fontWeight: FontWeight.w500)),
            ],
          ),
        ),
      ],
    );
  }

  Widget _fareSummary(ActiveRideModel ride) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).scaffoldBackgroundColor,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          _fareRow('ride.total_fare'.tr(), ride.fare.totalFare),
          _fareRow('ride.commission'.tr(), '-${ride.fare.adminCommission}'),
          const Divider(),
          _fareRow('ride.your_earning'.tr(), ride.fare.driverEarning,
              bold: true),
        ],
      ),
    );
  }

  Widget _fareRow(String label, String value, {bool bold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label,
              style: TextStyle(
                  fontWeight: bold ? FontWeight.bold : FontWeight.normal)),
          Text(Helpers.money(double.tryParse(value.replaceAll('-', '')) ?? 0),
              style: TextStyle(
                  fontWeight: bold ? FontWeight.bold : FontWeight.normal,
                  color: bold
                      ? AppColors.success
                      : Theme.of(context).colorScheme.onSurface)),
        ],
      ),
    );
  }

  Widget _paymentBox(ActiveRideModel ride) {
    final cash = ride.isCash;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: (cash ? AppColors.warning : AppColors.success)
            .withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Icon(cash ? Icons.payments : Icons.check_circle,
              color: cash ? AppColors.warning : AppColors.success),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              cash
                  ? 'ride.collect_from_customer'.tr(namedArgs: {
                      'amount': Helpers.money(
                          double.tryParse(ride.fare.customerPayable) ?? 0)
                    })
                  : 'ride.payment_auto_processed'.tr(
                      namedArgs: {'method': ride.paymentMethod}),
              style: const TextStyle(fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }
}
