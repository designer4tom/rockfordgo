import 'dart:async';

import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../model/place_model.dart';
import '../../provider/location_provider.dart';

/// Reusable address search screen. Returns the chosen [PlaceModel] (with
/// resolved coordinates) via `Navigator.pop`. Used for ride destination and
/// parcel sender/receiver locations.
class LocationSearchScreen extends StatefulWidget {
  final String title;

  const LocationSearchScreen({super.key, this.title = ''});

  @override
  State<LocationSearchScreen> createState() => _LocationSearchScreenState();
}

class _LocationSearchScreenState extends State<LocationSearchScreen> {
  final _controller = TextEditingController();
  Timer? _debounce;
  List<PlaceModel> _results = [];
  bool _searching = false;
  bool _resolving = false;

  @override
  void dispose() {
    _debounce?.cancel();
    _controller.dispose();
    super.dispose();
  }

  void _onChanged(String q) {
    _debounce?.cancel();
    if (q.trim().isEmpty) {
      setState(() => _results = []);
      return;
    }
    _debounce = Timer(const Duration(milliseconds: 300), () => _search(q));
  }

  Future<void> _search(String q) async {
    setState(() => _searching = true);
    final results = await context.read<LocationProvider>().searchPlaces(q);
    if (!mounted) return;
    setState(() {
      _results = results;
      _searching = false;
    });
  }

  Future<void> _select(PlaceModel place) async {
    var resolved = place;
    // Predictions carry no coordinates → resolve from place_id.
    if (place.lat == 0 && place.lng == 0 && place.placeId != null) {
      setState(() => _resolving = true);
      final details =
          await context.read<LocationProvider>().placeDetails(place.placeId!);
      if (!mounted) return;
      setState(() => _resolving = false);
      if (details == null || (details.lat == 0 && details.lng == 0)) {
        SnackbarHelper.showError(
            context, 'destination.location_resolve_failed'.tr());
        return;
      }
      resolved = PlaceModel(
        address: place.address,
        lat: details.lat,
        lng: details.lng,
        placeId: place.placeId,
        name: place.name,
      );
    }
    if (mounted) context.pop(resolved);
  }

  Future<void> _pickOnMap() async {
    final picked = await context.push<PlaceModel>('/map-picker');
    if (picked != null && mounted) context.pop(picked);
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text(widget.title.isNotEmpty
            ? widget.title
            : 'destination.search_destination'.tr()),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: TextField(
              controller: _controller,
              autofocus: true,
              onChanged: _onChanged,
              decoration: InputDecoration(
                prefixIcon: const Icon(Icons.search),
                hintText: 'destination.search_destination'.tr(),
                suffixIcon: _controller.text.isEmpty
                    ? null
                    : IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () {
                          _controller.clear();
                          setState(() => _results = []);
                        },
                      ),
              ),
            ),
          ),
          ListTile(
            leading: const Icon(Icons.map_outlined, color: AppColors.primary),
            title: Text('destination.select_on_map'.tr()),
            onTap: _pickOnMap,
          ),
          const Divider(height: 1),
          if (_resolving) const LinearProgressIndicator(minHeight: 2),
          Expanded(
            child: _searching
                ? const Center(child: CircularProgressIndicator())
                : _results.isEmpty
                    ? Center(
                        child: Text(
                          'destination.search_empty_message'.tr(),
                          style:
                              const TextStyle(color: AppColors.textSecondary),
                        ),
                      )
                    : ListView.separated(
                        itemCount: _results.length,
                        separatorBuilder: (context, i) =>
                            const Divider(height: 1),
                        itemBuilder: (context, i) {
                          final p = _results[i];
                          return ListTile(
                            leading: const Icon(Icons.location_on_outlined,
                                color: AppColors.textSecondary),
                            title: Text(p.name ?? p.address,
                                maxLines: 1, overflow: TextOverflow.ellipsis),
                            subtitle: p.name != null
                                ? Text(p.address,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis)
                                : null,
                            onTap: () => _select(p),
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }
}
