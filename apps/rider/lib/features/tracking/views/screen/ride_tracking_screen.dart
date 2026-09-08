import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../../chat/provider/chat_provider.dart';
import '../../provider/tracking_provider.dart';
import '../widgets/driver_info_card.dart';
import '../widgets/otp_display.dart';
import '../widgets/sos_button.dart';
import '../widgets/tracking_map.dart';
import '../widgets/trip_status_bar.dart';

class RideTrackingScreen extends StatefulWidget {
  final int orderId;

  const RideTrackingScreen({super.key, required this.orderId});

  @override
  State<RideTrackingScreen> createState() => _RideTrackingScreenState();
}

class _RideTrackingScreenState extends State<RideTrackingScreen> {
  late final TrackingProvider _tracking;
  bool _navigatedToComplete = false;

  // OTP is shown before the passenger is picked up.
  static const _otpStatuses = {'accepted', 'go_to_pickup', 'confirm_arrival'};

  @override
  void initState() {
    super.initState();
    _tracking = context.read<TrackingProvider>();
    _tracking.addListener(_onChange);
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => _tracking.initTracking(widget.orderId, 'ride'),
    );
  }

  void _onChange() {
    if (_navigatedToComplete || !mounted) return;
    final status = _tracking.tracking?.status;
    if (status == 'completed' || status == 'dropped_off') {
      _navigatedToComplete = true;
      // Online → payment screen; cash → straight to rating.
      final online = _tracking.tracking?.paymentMethod != 'cash';
      context.go(online ? '/trip-payment' : '/rating', extra: widget.orderId);
    } else if (status == 'cancelled') {
      _navigatedToComplete = true;
      context.go(RouteNames.home);
    }
  }

  @override
  void dispose() {
    _tracking.removeListener(_onChange);
    _tracking.stopTracking();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final tracking = context.watch<TrackingProvider>();
    final t = tracking.tracking;
    final status = t?.status ?? 'pending';
    final showOtp = _otpStatuses.contains(status) && (t?.otp ?? '').isNotEmpty;

    return Scaffold(
      body: Stack(
        children: [
          Positioned.fill(
            child: TrackingMap(
              driverPosition: tracking.driverPosition,
              driverBearing: tracking.driverBearing,
              pickup: (t?.pickupLat != null && t?.pickupLng != null)
                  ? LatLng(t!.pickupLat!, t.pickupLng!)
                  : null,
              drop: (t?.dropLat != null && t?.dropLng != null)
                  ? LatLng(t!.dropLat!, t.dropLng!)
                  : null,
            ),
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Row(
                children: [
                  _circleBtn(Icons.arrow_back, () => context.go(RouteNames.home)),
                  const SizedBox(width: 12),
                  Expanded(child: TripStatusBar(status: status)),
                  const SizedBox(width: 12),
                  SosButton(onTrigger: tracking.triggerSos),
                ],
              ),
            ),
          ),
          DraggableScrollableSheet(
            initialChildSize: 0.50,
            minChildSize: 0.18,
            maxChildSize: 0.50,
            builder: (context, controller) {
              return Container(
                decoration: BoxDecoration(
                  color: Theme.of(context).cardColor,
                  borderRadius: const BorderRadius.vertical(
                      top: Radius.circular(24)),
                  boxShadow: const [
                    BoxShadow(color: Colors.black12, blurRadius: 12)
                  ],
                ),
                child: SafeArea(
                  top: false,
                  child: ListView(
                  controller: controller,
                  padding: const EdgeInsets.all(20),
                  children: [
                    Center(
                      child: Container(
                        width: 40,
                        height: 4,
                        decoration: BoxDecoration(
                          color: Theme.of(context).dividerColor,
                          borderRadius: BorderRadius.circular(2),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    if (t?.driver != null)
                      DriverInfoCard(
                        driver: t!.driver!,
                        onCall: tracking.callDriver,
                        onShare: tracking.shareTrip,
                        onChat: () => _openChat(context),
                      )
                    else
                      Center(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Text('tracking.loading_driver'.tr()),
                        ),
                      ),
                    if (showOtp) ...[
                      const SizedBox(height: 16),
                      OtpDisplay(
                        otp: t!.otp!,
                        highlighted: status == 'confirm_arrival',
                      ),
                    ],
                    if (_otpStatuses.contains(status)) ...[
                      const SizedBox(height: 16),
                      CustomButton(
                        text: 'common.cancel'.tr(),
                        isOutlined: true,
                        color: AppColors.danger,
                        onPressed: () => _cancel(tracking),
                      ),
                    ],
                  ],
                ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  Future<void> _cancel(TrackingProvider tracking) async {
    // The cancel request is awaited fully so the server (which broadcasts
    // OrderCancelled to the driver) always receives it before we leave. On
    // failure we surface the error so the user can retry instead of silently
    // staying on the screen with the driver never notified.
    final ok = await tracking.cancelOrder('user_cancelled');
    if (!mounted) return;
    if (ok) {
      context.go(RouteNames.home);
    } else {
      SnackbarHelper.showError(
          context, tracking.error ?? 'ride.cancel_failed'.tr());
    }
  }

  Future<void> _openChat(BuildContext context) async {
    final chat = context.read<ChatProvider>();
    final hasChat = await chat.openForOrder(widget.orderId);
    if (!context.mounted) return;
    if (hasChat) {
      context.push(RouteNames.chat);
    } else {
      SnackbarHelper.showError(
        context,
        chat.error ?? 'chat.not_available'.tr(),
      );
    }
  }

  Widget _circleBtn(IconData icon, VoidCallback onTap) {
    return Material(
      color: Theme.of(context).cardColor,
      shape: const CircleBorder(),
      elevation: 2,
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: Padding(padding: const EdgeInsets.all(10), child: Icon(icon)),
      ),
    );
  }
}
