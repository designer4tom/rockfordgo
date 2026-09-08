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
import '../../../ride_order/views/widgets/customer_info_card.dart';
import '../../../ride_order/views/widgets/navigation_button.dart';
import '../../../ride_order/views/widgets/order_map.dart';
import '../../../ride_order/views/widgets/status_action_button.dart';
import '../../model/active_parcel_model.dart';
import '../../provider/parcel_order_provider.dart';
import '../widgets/cod_collection_sheet.dart';
import '../widgets/parcel_info_card.dart';
import '../widgets/proof_collection_sheet.dart';

class ParcelOrderScreen extends StatefulWidget {
  final int orderId;
  const ParcelOrderScreen({super.key, required this.orderId});

  @override
  State<ParcelOrderScreen> createState() => _ParcelOrderScreenState();
}

class _ParcelOrderScreenState extends State<ParcelOrderScreen> {
  bool _cancelHandled = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ParcelOrderProvider>().loadActiveOrder(widget.orderId);
      context.read<ChatProvider>().loadForOrder(widget.orderId);
    });
  }

  void _onCancelled(ParcelOrderProvider p) {
    if (_cancelHandled || !mounted) return;
    _cancelHandled = true;
    p.clear();
    context.read<ChatProvider>().clear();
    context.read<HomeProvider>().setTripActive(false);
    AppSnackbar.show(context, 'parcel.order_cancelled'.tr());
    context.go(RouteNames.home);
  }

  bool get _beforePickup {
    final s = context.read<ParcelOrderProvider>().currentStatus;
    return s == ParcelStatus.accepted ||
        s == ParcelStatus.goToPickup ||
        s == ParcelStatus.confirmArrival;
  }

  Future<void> _collectProof(
      ParcelOrderProvider p, ActiveParcelModel parcel) async {
    final type = parcel.proofType ?? 'otp';
    await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Theme.of(context).cardColor,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => ProofCollectionSheet(
        proofType: type,
        onSubmit: ({otp, photo, signature}) => p.collectProof(
          type: type,
          otp: otp,
          photo: photo,
          signature: signature,
        ),
      ),
    );
  }

  Future<void> _collectCod(
      ParcelOrderProvider p, ActiveParcelModel parcel) async {
    final amount = parcel.codCollectible;
    await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Theme.of(context).cardColor,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => CodCollectionSheet(
        amount: amount,
        onCollected: () async {
          final ok = await p.collectCod(amount);
          return ok ? null : (p.error ?? 'parcel.cod_record_failed'.tr());
        },
      ),
    );
  }

  Future<void> _complete(ParcelOrderProvider p) async {
    final ok = await p.completeDelivery();
    if (!mounted) return;
    if (ok) {
      context.read<HomeProvider>().setTripActive(false);
      context.read<ChatProvider>().clear();
      context.go('/parcel-complete', extra: widget.orderId);
    } else {
      AppSnackbar.error(context, p.error ?? 'parcel.could_not_complete'.tr());
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<ParcelOrderProvider>();
    final parcel = p.activeParcel;

    if (p.cancelledByCustomer) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _onCancelled(p));
    }

    if (p.loading && parcel == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (parcel == null) {
      return Scaffold(
        appBar: AppBar(),
        body: Center(child: Text(p.error ?? 'order.not_found'.tr())),
      );
    }

    final driverPos = context.watch<HomeProvider>().currentPosition;
    final chat = context.watch<ChatProvider>();
    final target = _beforePickup ? parcel.pickup : parcel.drop;

    return Scaffold(
      body: Stack(
        children: [
          Positioned.fill(
            child: OrderMap(
              driver: driverPos,
              target: target,
              targetLabel:
                  _beforePickup ? 'parcel.pickup'.tr() : 'parcel.drop_off'.tr(),
            ),
          ),
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
                        ? 'parcel.to_pickup'.tr()
                        : 'parcel.to_drop'.tr(),
                  ),
                ],
              ),
            ),
          ),
          Align(
            alignment: Alignment.bottomCenter,
            child: _bottomSheet(p, parcel, target, chat),
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
      AppSnackbar.show(context, chat.error ?? 'parcel.chat_not_ready'.tr());
      return;
    }
    context.push(RouteNames.chat);
  }

  Future<void> _triggerSos() async {
    final orderId = context.read<ParcelOrderProvider>().activeParcel?.orderId;
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('parcel.send_sos_title'.tr()),
        content: Text('parcel.send_sos_message'.tr()),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text('common.cancel'.tr())),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: Text('parcel.send_sos'.tr(),
                style: const TextStyle(color: AppColors.danger)),
          ),
        ],
      ),
    );
    if (confirm != true) return;
    final ok = await SosService.instance.triggerSos(orderId: orderId);
    if (!mounted) return;
    AppSnackbar.show(
        context, ok ? 'parcel.sos_sent'.tr() : 'parcel.sos_failed'.tr());
  }

  Widget _bottomSheet(ParcelOrderProvider p, ActiveParcelModel parcel,
      PlaceInfo target, ChatProvider chat) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
        boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 12)],
      ),
      child: SafeArea(
        top: false,
        child: ConstrainedBox(
          constraints: BoxConstraints(
            maxHeight: MediaQuery.of(context).size.height * 0.6,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: _stepContent(p, parcel, target, chat),
            ),
          ),
        ),
      ),
    );
  }

  List<Widget> _stepContent(ParcelOrderProvider p, ActiveParcelModel parcel,
      PlaceInfo target, ChatProvider chat) {
    switch (parcel.status) {
      case ParcelStatus.accepted:
      case ParcelStatus.goToPickup:
        return [
          CustomerInfoCard(
            roleLabel: 'parcel.sender'.tr(),
            name: parcel.sender.name,
            phone: parcel.sender.phone,
            onCall: p.callSender,
            onMessage: () => _onMessageTap(chat),
            messageLoading: chat.loadingIcon,
            unreadCount: chat.unreadBadge,
          ),
          const SizedBox(height: 12),
          _addressRow(Icons.my_location, 'parcel.pickup'.tr(), target.address),
          const SizedBox(height: 16),
          StatusActionButton(
            label: 'parcel.arrived_at_pickup'.tr(),
            loading: p.updating,
            onConfirm: () => p.updateStatus(ParcelStatus.confirmArrival),
          ),
        ];
      case ParcelStatus.confirmArrival:
        return [
          ParcelInfoCard(parcel: parcel.parcel),
          const SizedBox(height: 16),
          StatusActionButton(
            label: 'parcel.parcel_picked_up'.tr(),
            loading: p.updating,
            onConfirm: () => p.updateStatus(ParcelStatus.pickedUp),
          ),
        ];
      case ParcelStatus.pickedUp:
        return [
          CustomerInfoCard(
            roleLabel: 'parcel.receiver'.tr(),
            name: parcel.receiver.name,
            phone: parcel.receiver.phone,
            onCall: p.callReceiver,
            onMessage: () => _onMessageTap(chat),
            messageLoading: chat.loadingIcon,
            unreadCount: chat.unreadBadge,
          ),
          const SizedBox(height: 12),
          _addressRow(
              Icons.location_on, 'parcel.drop_off'.tr(), parcel.drop.address),
          const SizedBox(height: 16),
          StatusActionButton(
            label: 'parcel.start_delivery'.tr(),
            loading: p.updating,
            onConfirm: () => p.updateStatus(ParcelStatus.startRide),
          ),
        ];
      case ParcelStatus.startRide:
        return [
          CustomerInfoCard(
            roleLabel: 'parcel.receiver'.tr(),
            name: parcel.receiver.name,
            phone: parcel.receiver.phone,
            onCall: p.callReceiver,
            onMessage: () => _onMessageTap(chat),
            messageLoading: chat.loadingIcon,
            unreadCount: chat.unreadBadge,
          ),
          const SizedBox(height: 12),
          _addressRow(
              Icons.location_on, 'parcel.drop_off'.tr(), parcel.drop.address),
          if (parcel.isCod) ...[
            const SizedBox(height: 12),
            _codReminder(parcel),
          ],
          const SizedBox(height: 16),
          StatusActionButton(
            label: 'parcel.reached_destination'.tr(),
            loading: p.updating,
            onConfirm: () => p.updateStatus(ParcelStatus.droppedOff),
          ),
        ];
      case ParcelStatus.droppedOff:
        return _deliveryStep(p, parcel);
      default:
        return [Center(child: Text('parcel.finalizing'.tr()))];
    }
  }

  List<Widget> _deliveryStep(ParcelOrderProvider p, ActiveParcelModel parcel) {
    final needsProof = (parcel.proofType ?? '').isNotEmpty;
    final proofDone = !needsProof || p.proofCollected;
    final codDone = !parcel.isCod || p.codCollected;
    final canComplete = proofDone && codDone;

    return [
      Text('parcel.complete_delivery'.tr(),
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
      const SizedBox(height: 12),
      if (needsProof)
        _taskTile(
          done: p.proofCollected,
          icon: Icons.verified_outlined,
          label: 'parcel.proof_of_delivery'
              .tr(namedArgs: {'type': parcel.proofType ?? ''}),
          onTap: p.proofCollected ? null : () => _collectProof(p, parcel),
        ),
      if (parcel.isCod)
        _taskTile(
          done: p.codCollected,
          icon: Icons.payments_outlined,
          label: 'parcel.collect_cod'.tr(namedArgs: {
            'amount': Helpers.money(parcel.codCollectible)
          }),
          onTap: p.codCollected ? null : () => _collectCod(p, parcel),
        ),
      const SizedBox(height: 12),
      StatusActionButton(
        label: 'parcel.complete_delivery'.tr(),
        icon: Icons.check,
        color: AppColors.success,
        loading: p.updating,
        slideToConfirm: canComplete,
        onConfirm: canComplete
            ? () => _complete(p)
            : () => AppSnackbar.show(
                context, 'parcel.finish_steps_first'.tr()),
      ),
    ];
  }

  Widget _taskTile({
    required bool done,
    required IconData icon,
    required String label,
    VoidCallback? onTap,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Material(
        color: done
            ? AppColors.success.withValues(alpha: 0.1)
            : Theme.of(context).scaffoldBackgroundColor,
        borderRadius: BorderRadius.circular(12),
        child: ListTile(
          leading: Icon(done ? Icons.check_circle : icon,
              color: done ? AppColors.success : AppColors.primary),
          title: Text(label),
          trailing: done
              ? const Icon(Icons.done, color: AppColors.success)
              : const Icon(Icons.chevron_right),
          onTap: onTap,
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
    );
  }

  Widget _codReminder(ActiveParcelModel parcel) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.warning.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          const Icon(Icons.info_outline, color: AppColors.warning),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'parcel.cod_reminder'.tr(namedArgs: {
                'amount': Helpers.money(parcel.codCollectible)
              }),
              style: const TextStyle(fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
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
}
