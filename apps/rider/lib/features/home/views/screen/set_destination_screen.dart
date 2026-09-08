import 'dart:async';

import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/theme/app_text_styles.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/widgets/empty_state.dart';
import '../../../location/model/place_model.dart';
import '../../../location/provider/location_provider.dart';
import '../../../ride/provider/ride_provider.dart';
import '../../provider/home_provider.dart';
import '../widgets/place_search_result.dart';

class SetDestinationScreen extends StatefulWidget {
  const SetDestinationScreen({super.key});

  @override
  State<SetDestinationScreen> createState() => _SetDestinationScreenState();
}

class _SetDestinationScreenState extends State<SetDestinationScreen> {
  final _dropController = TextEditingController();
  Timer? _debounce;
  List<PlaceModel> _results = [];
  bool _searching = false;

  PlaceModel? _pickup;
  PlaceModel? _drop;

  @override
  void initState() {
    super.initState();
    final location = context.read<LocationProvider>();
    final home = context.read<HomeProvider>();
    _pickup = home.pickupLocation ?? location.currentPlace;

    // If we still don't have a pickup (location not resolved yet), resolve it
    // now so the user isn't blocked with "pickup not found".
    if (_pickup == null) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _resolvePickup());
    }
  }

  Future<void> _resolvePickup() async {
    final location = context.read<LocationProvider>();
    if (location.currentPlace == null) {
      await location.getCurrentPlace();
    }
    if (!mounted) return;
    final resolved = location.currentPlace;
    if (resolved != null) setState(() => _pickup = resolved);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _dropController.dispose();
    super.dispose();
  }

  void _onQueryChanged(String query) {
    _debounce?.cancel();
    if (query.trim().isEmpty) {
      setState(() => _results = []);
      return;
    }
    // Debounce 300ms to avoid an API call per keystroke.
    _debounce = Timer(const Duration(milliseconds: 300), () => _search(query));
  }

  Future<void> _search(String query) async {
    setState(() => _searching = true);
    final results = await context.read<LocationProvider>().searchPlaces(query);
    if (!mounted) return;
    setState(() {
      _results = results;
      _searching = false;
    });
  }

  Future<void> _selectDrop(PlaceModel place) async {
    setState(() {
      _dropController.text = place.name ?? place.address;
      _results = [];
    });

    var resolved = place;
    // Search predictions have no coordinates — resolve them from the place_id
    // so the route/polyline isn't drawn to (0, 0).
    if (place.lat == 0 && place.lng == 0 && place.placeId != null) {
      setState(() => _searching = true);
      final details =
          await context.read<LocationProvider>().placeDetails(place.placeId!);
      if (!mounted) return;
      setState(() => _searching = false);
      if (details == null || (details.lat == 0 && details.lng == 0)) {
        SnackbarHelper.showError(
            context, 'destination.location_resolve_failed'.tr());
        return;
      }
      resolved = PlaceModel(
        address: place.address, // keep the friendly prediction text
        lat: details.lat,
        lng: details.lng,
        placeId: place.placeId,
        name: place.name,
      );
    }

    setState(() => _drop = resolved);
    _proceed();
  }

  Future<void> _openMapPicker() async {
    final picked = await context.push<PlaceModel>('/map-picker');
    if (picked != null) _selectDrop(picked);
  }

  Future<void> _pickPickupFromMap() async {
    final picked = await context.push<PlaceModel>('/map-picker');
    if (picked != null) setState(() => _pickup = picked);
  }

  void _proceed() {
    if (_pickup == null) {
      SnackbarHelper.showError(context, 'destination.pickup_not_found'.tr());
      return;
    }
    if (_drop == null) return;
    context.read<HomeProvider>().setPickup(_pickup!);
    context.read<RideProvider>().setTrip(_pickup!, _drop!);
    context.push(RouteNames.vehicleSelect);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('destination.title'.tr())),
      body: Column(
        children: [
          Container(
            color: Theme.of(context).cardColor,
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
            child: Column(
              children: [
                InkWell(
                  onTap: _pickPickupFromMap,
                  child: _field(
                    icon: Icons.my_location,
                    iconColor: AppColors.secondary,
                    text: _pickup?.address ?? 'destination.pickup_select'.tr(),
                  ),
                ),
                const Divider(height: 16),
                TextField(
                  controller: _dropController,
                  autofocus: true,
                  onChanged: _onQueryChanged,
                  decoration: InputDecoration(
                    prefixIcon: const Icon(Icons.location_on,
                        color: AppColors.danger),
                    hintText: 'destination.enter_destination'.tr(),
                    border: InputBorder.none,
                    filled: false,
                  ),
                ),
              ],
            ),
          ),
          ListTile(
            leading: const Icon(Icons.map_outlined, color: AppColors.primary),
            title: Text('destination.select_on_map'.tr()),
            onTap: _openMapPicker,
          ),
          const Divider(height: 1),
          Expanded(child: _resultsList()),
        ],
      ),
    );
  }

  Widget _resultsList() {
    if (_searching) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_results.isEmpty) {
      return EmptyState(
        icon: Icons.search,
        title: 'destination.search_empty_title'.tr(),
        message: 'destination.search_empty_message'.tr(),
      );
    }
    return ListView.separated(
      itemCount: _results.length,
      separatorBuilder: (context, index) => const Divider(height: 1),
      itemBuilder: (context, index) => PlaceSearchResult(
        place: _results[index],
        onTap: () => _selectDrop(_results[index]),
      ),
    );
  }

  Widget _field({
    required IconData icon,
    required Color iconColor,
    required String text,
  }) {
    return Row(
      children: [
        Icon(icon, color: iconColor, size: 20),
        const SizedBox(width: 12),
        Expanded(
          child: Text(
            text,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: AppTextStyles.body,
          ),
        ),
      ],
    );
  }
}
