import 'package:cached_network_image/cached_network_image.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../home/provider/home_provider.dart';
import '../../model/banner_model.dart';

class BannerCard extends StatelessWidget {
  final BannerModel banner;

  const BannerCard({super.key, required this.banner});

  Future<void> _handleAction(BuildContext context) async {
    switch (banner.actionType) {
      case 'url':
        final value = banner.actionValue;
        if (value != null && value.isNotEmpty) {
          final uri = Uri.tryParse(value);
          if (uri != null && await canLaunchUrl(uri)) {
            await launchUrl(uri, mode: LaunchMode.externalApplication);
          }
        }
      case 'service':
        if (banner.actionValue == 'parcel') {
          context.read<HomeProvider>().selectService('parcel');
          context.push(RouteNames.parcelBooking);
        } else {
          context.read<HomeProvider>().selectService('ride');
          context.push(RouteNames.setDestination);
        }
      case 'screen':
        final route = banner.actionValue;
        if (route != null && route.isNotEmpty) context.push(route);
      default:
        break; // none
    }
  }

  @override
  Widget build(BuildContext context) {
    final hasImage = banner.image != null && banner.image!.isNotEmpty;

    return Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(20),
        child: InkWell(
          borderRadius: BorderRadius.circular(20),
          onTap: () => _handleAction(context),
          child: Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            clipBehavior: Clip.antiAlias,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(20),
              gradient: const LinearGradient(
                colors: [AppColors.primary, AppColors.primaryDark],
                begin: Alignment.centerLeft,
                end: Alignment.centerRight,
              ),
              image: hasImage
                  ? DecorationImage(
                      image: CachedNetworkImageProvider(banner.image!),
                      fit: BoxFit.cover,
                      colorFilter: ColorFilter.mode(
                        Colors.black.withValues(alpha: 0.25),
                        BlendMode.darken,
                      ),
                    )
                  : null,
            ),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (banner.title != null && banner.title!.isNotEmpty)
                        Text(
                          banner.title!,
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                            fontSize: 20,
                          ),
                        ),
                      if (banner.subtitle != null &&
                          banner.subtitle!.isNotEmpty) ...[
                        const SizedBox(height: 6),
                        Text(
                          banner.subtitle!,
                          style: const TextStyle(
                              color: Colors.white70, fontSize: 13),
                        ),
                      ],
                      const SizedBox(height: 14),
                      Material(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        child: InkWell(
                          borderRadius: BorderRadius.circular(20),
                          onTap: () => _handleAction(context),
                          child: Padding(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 14, vertical: 8),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(
                                  (banner.buttonText?.isNotEmpty ?? false)
                                      ? banner.buttonText!
                                      : 'home.learn_more'.tr(),
                                  style: const TextStyle(
                                    color: AppColors.primary,
                                    fontWeight: FontWeight.w600,
                                    fontSize: 13,
                                  ),
                                ),
                                const SizedBox(width: 4),
                                const Icon(Icons.arrow_forward,
                                    size: 14, color: AppColors.primary),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                if (!hasImage)
                  const Icon(Icons.local_taxi, size: 64, color: Colors.white24),
              ],
            ),
          ),
        ),
      );
  }
}
