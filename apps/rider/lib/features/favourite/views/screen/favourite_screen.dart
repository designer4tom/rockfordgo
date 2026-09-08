import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/snackbar_helper.dart';
import '../../../../core/widgets/empty_state.dart';
import '../../../../core/widgets/loading_widget.dart';
import '../../../location/model/place_model.dart';
import '../../provider/favourite_provider.dart';

class FavouriteScreen extends StatefulWidget {
  const FavouriteScreen({super.key});

  @override
  State<FavouriteScreen> createState() => _FavouriteScreenState();
}

class _FavouriteScreenState extends State<FavouriteScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback(
      (_) => context.read<FavouriteProvider>().loadFavourites(),
    );
  }

  Future<void> _addNew() async {
    final place = await context.push<PlaceModel>('/map-picker');
    if (place == null || !mounted) return;
    final label = await _askLabel();
    if (label == null || !mounted) return;
    final ok = await context.read<FavouriteProvider>().addFavourite(
          label: label,
          customLabel: label == 'custom' ? 'favourite.other'.tr() : null,
          place: place,
        );
    if (mounted && ok) {
      SnackbarHelper.showSuccess(context, 'favourite.added'.tr());
    }
  }

  Future<String?> _askLabel() {
    return showModalBottomSheet<String>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.home_outlined),
              title: Text('favourite.home'.tr()),
              onTap: () => Navigator.pop(ctx, 'home'),
            ),
            ListTile(
              leading: const Icon(Icons.work_outline),
              title: Text('favourite.office'.tr()),
              onTap: () => Navigator.pop(ctx, 'office'),
            ),
            ListTile(
              leading: const Icon(Icons.star_border),
              title: Text('favourite.other'.tr()),
              onTap: () => Navigator.pop(ctx, 'custom'),
            ),
          ],
        ),
      ),
    );
  }

  IconData _icon(String label) => switch (label) {
        'home' => Icons.home,
        'office' => Icons.work,
        _ => Icons.star,
      };

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<FavouriteProvider>();

    return Scaffold(
      appBar: AppBar(title: Text('favourite.title'.tr())),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _addNew,
        icon: const Icon(Icons.add),
        label: Text('favourite.add'.tr()),
      ),
      body: provider.isLoading
          ? const LoadingWidget()
          : provider.favourites.isEmpty
              ? EmptyState(
                  icon: Icons.favorite_border,
                  title: 'favourite.empty'.tr(),
                  message: 'favourite.empty_msg'.tr(),
                )
              : ListView(
                  children: provider.favourites.map((f) {
                    return Dismissible(
                      key: ValueKey(f.id),
                      direction: DismissDirection.endToStart,
                      background: Container(
                        color: AppColors.danger,
                        alignment: Alignment.centerRight,
                        padding: const EdgeInsets.only(right: 20),
                        child: const Icon(Icons.delete, color: Colors.white),
                      ),
                      onDismissed: (_) =>
                          context.read<FavouriteProvider>().deleteFavourite(f.id),
                      child: ListTile(
                        leading: Icon(_icon(f.label), color: AppColors.primary),
                        title: Text(f.displayLabel),
                        subtitle: Text(f.address,
                            maxLines: 1, overflow: TextOverflow.ellipsis),
                      ),
                    );
                  }).toList(),
                ),
    );
  }
}
