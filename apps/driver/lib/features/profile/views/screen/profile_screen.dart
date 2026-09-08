import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/routing/route_names.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../../core/utils/helpers.dart';
import '../../../../core/widgets/loading_indicator.dart';
import '../../../auth/model/driver_model.dart';
import '../../provider/profile_provider.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  static const _accent = Color(0xFF5B3DF6);
  static const _accentSoft = Color(0xFFEDE7FE);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ProfileProvider>().loadProfile();
    });
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<ProfileProvider>();
    final d = p.driver;

    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: p.loading && d == null
          ? const SafeArea(child: LoadingIndicator())
          : d == null
          ? _error(p.error)
          : SafeArea(
              child: RefreshIndicator(
                onRefresh: () => context.read<ProfileProvider>().loadProfile(),
                child: CustomScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  slivers: [
                    // Header stays pinned to the top while the rest scrolls.
                    SliverPersistentHeader(
                      pinned: true,
                      delegate: _PinnedHeaderDelegate(
                        height: 108.r + 32.h,
                        child: Container(
                          color: Theme.of(context).scaffoldBackgroundColor,
                          padding: EdgeInsets.fromLTRB(16.w, 14.h, 16.w, 14.h),
                          child: _header(d),
                        ),
                      ),
                    ),
                    SliverPadding(
                      padding: EdgeInsets.fromLTRB(16.w, 0, 16.w, 20.h),
                      sliver: SliverList(
                        delegate: SliverChildListDelegate([
                          _summaryCard(d),
                          SizedBox(height: 14.h),
                          _menuCard(d),
                        ]),
                      ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _error(String? msg) => Center(
    child: Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(msg ?? 'profile.could_not_load'.tr()),
        const SizedBox(height: 12),
        OutlinedButton(
          onPressed: () => context.read<ProfileProvider>().loadProfile(),
          child: Text('common.retry'.tr()),
        ),
      ],
    ),
  );

  // ---- Header ----
  Widget _header(DriverModel d) {
    final rating = num.tryParse(d.averageRating) ?? 0;
    final approved = d.isApproved;
    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Stack(
          clipBehavior: Clip.none,
          children: [
            Container(
              width: 108.r,
              height: 108.r,
              decoration: BoxDecoration(
                color: _accentSoft,
                shape: BoxShape.circle,
              ),
              padding: EdgeInsets.all(6.r),
              child: CircleAvatar(
                backgroundColor: Colors.white,
                backgroundImage: Helpers.imageUrl(d.avatar) != null
                    ? NetworkImage(Helpers.imageUrl(d.avatar)!)
                    : null,
                child: Helpers.imageUrl(d.avatar) != null
                    ? null
                    : Text(
                        _initial(d.name),
                        style: TextStyle(
                          fontSize: 30.sp,
                          fontWeight: FontWeight.bold,
                          color: _accent,
                        ),
                      ),
              ),
            ),
            Positioned(
              right: 4,
              bottom: 4,
              child: GestureDetector(
                onTap: () => context.push('/edit-profile'),
                child: Container(
                  width: 30.r,
                  height: 30.r,
                  decoration: const BoxDecoration(
                    color: _accent,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(Icons.edit, color: Colors.white, size: 15.sp),
                ),
              ),
            ),
          ],
        ),
        SizedBox(width: 18.w),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                d.name.isEmpty ? 'profile.driver'.tr() : d.name,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: Theme.of(context).colorScheme.onSurface,
                  fontSize: 22.sp,
                  fontWeight: FontWeight.w800,
                  height: 1.1,
                ),
              ),
              SizedBox(height: 8.h),
              Row(
                children: [
                  Icon(Icons.star_rounded, color: _accent, size: 18.sp),
                  SizedBox(width: 4.w),
                  Text(
                    rating > 0 ? rating.toStringAsFixed(1) : '0.0',
                    style: TextStyle(
                      color: _accent,
                      fontSize: 13.sp,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  Padding(
                    padding: EdgeInsets.symmetric(horizontal: 8.w),
                    child: Text(
                      '•',
                      style: TextStyle(
                        color: Theme.of(context).hintColor,
                        fontSize: 12.sp,
                      ),
                    ),
                  ),
                  Text(
                    '${d.totalTrips} ${'profile.trips'.tr()}',
                    style: TextStyle(
                      color: Theme.of(context).hintColor,
                      fontSize: 13.sp,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
              SizedBox(height: 10.h),
              _statusChip(
                icon: approved ? Icons.verified_rounded : Icons.hourglass_top,
                label: approved
                    ? 'profile.verified'.tr()
                    : d.status.toUpperCase(),
                color: approved ? const Color(0xFF22B07D) : Colors.orange,
                background: approved
                    ? const Color(0xFFE8F8EF)
                    : const Color(0xFFFFF3E4),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _statusChip({
    required IconData icon,
    required String label,
    required Color color,
    required Color background,
  }) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 10.w, vertical: 6.h),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(10.r),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16.sp, color: color),
          SizedBox(width: 6.w),
          Text(
            label,
            style: TextStyle(
              color: color,
              fontSize: 12.sp,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }

  // ---- Overview ----
  Widget _summaryCard(DriverModel d) {
    final balance = double.tryParse(d.walletBalance) ?? 0;
    final trips = d.totalTrips;
    final due = double.tryParse(d.dueAmount) ?? 0;
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(22.r),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.035),
            blurRadius: 18,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Padding(
        padding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 14.h),
        child: Row(
          children: [
            Expanded(
              child: GestureDetector(
                behavior: HitTestBehavior.opaque,
                onTap: () => context.push(RouteNames.wallet),
                child: _summaryItem(
                  label: 'wallet.balance'.tr(),
                  value: Helpers.money(balance),
                  icon: Icons.account_balance_wallet_rounded,
                  iconColor: _accent,
                  background: _accentSoft,
                  alignLeft: true,
                ),
              ),
            ),
            Container(width: 1, height: 76.h, color: const Color(0xFFF0EDF8)),
            Expanded(
              child: GestureDetector(
                behavior: HitTestBehavior.opaque,
                onTap: () => context.push(RouteNames.history),
                child: _summaryItem(
                  label: 'profile.trips'.tr(),
                  value: '$trips',
                  icon: Icons.directions_car_filled_rounded,
                  iconColor: const Color(0xFF22B07D),
                  background: const Color(0xFFEAF8F0),
                ),
              ),
            ),
            Container(width: 1, height: 76.h, color: const Color(0xFFF0EDF8)),
            Expanded(
              child: _summaryItem(
                label: 'profile.due'.tr(),
                value: Helpers.money(due),
                icon: Icons.local_atm_rounded,
                iconColor: const Color(0xFFF59E0B),
                background: const Color(0xFFFFF4E5),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _summaryItem({
    required String label,
    required String value,
    required IconData icon,
    required Color iconColor,
    required Color background,
    bool alignLeft = false,
  }) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 8.w, vertical: 4.h),
      child: Column(
        crossAxisAlignment: alignLeft
            ? CrossAxisAlignment.start
            : CrossAxisAlignment.center,
        children: [
          Container(
            width: 40.r,
            height: 40.r,
            decoration: BoxDecoration(
              color: background,
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: iconColor, size: 20.sp),
          ),
          SizedBox(height: 10.h),
          Text(
            label,
            textAlign: alignLeft ? TextAlign.left : TextAlign.center,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: Theme.of(context).hintColor,
              fontSize: 11.sp,
              fontWeight: FontWeight.w600,
            ),
          ),
          SizedBox(height: 6.h),
          Text(
            value,
            textAlign: alignLeft ? TextAlign.left : TextAlign.center,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              color: Theme.of(context).colorScheme.onSurface,
              fontSize: 15.sp,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }

  // ---- Menu ----
  Widget _menuCard(DriverModel d) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(22.r),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.035),
            blurRadius: 18,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      // The background lives on a Material (not the BoxDecoration) so the
      // ListTiles' ink splashes stay visible.
      child: Material(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(22.r),
        clipBehavior: Clip.antiAlias,
        child: Column(
          children: [
            _menuItem(
              icon: Icons.person_outline_rounded,
              iconBg: const Color(0xFFF1ECFF),
              iconColor: _accent,
              title: 'profile.personal_information'.tr(),
              subtitle: 'profile.personal_information_subtitle'.tr(),
              onTap: () => context.push('/edit-profile'),
            ),
            _menuDivider(),
            _menuItem(
              icon: Icons.directions_car_outlined,
              iconBg: const Color(0xFFEAF4FF),
              iconColor: const Color(0xFF1E88E5),
              title: 'profile.vehicle_information'.tr(),
              subtitle: 'profile.vehicle_information_subtitle'.tr(),
              onTap: d.vehicle == null
                  ? () => AppSnackbar.show(
                      context,
                      'profile.vehicle_information_unavailable'.tr(),
                    )
                  : () => _showVehicleInfo(d.vehicle!),
            ),
            _menuDivider(),
            _menuItem(
              icon: Icons.description_outlined,
              iconBg: const Color(0xFFE8F7EF),
              iconColor: const Color(0xFF22B07D),
              title: 'documents.title'.tr(),
              subtitle: 'documents.subtitle'.tr(),
              onTap: () => context.push(RouteNames.documents),
            ),
            _menuDivider(),
            _menuItem(
              icon: Icons.account_balance_outlined,
              iconBg: const Color(0xFFFFF1E4),
              iconColor: const Color(0xFFF59E0B),
              title: 'profile.payout_methods'.tr(),
              subtitle: 'profile.payout_methods_subtitle'.tr(),
              onTap: () => context.push('/withdrawal-account'),
            ),
            _menuDivider(),
            _menuItem(
              icon: Icons.shield_outlined,
              iconBg: const Color(0xFFF0ECFF),
              iconColor: _accent,
              title: 'profile.safety_center'.tr(),
              subtitle: 'profile.safety_center_subtitle'.tr(),
              onTap: () => context.push('/emergency-contact'),
            ),
            _menuDivider(),
            _menuItem(
              icon: Icons.headset_mic_outlined,
              iconBg: const Color(0xFFEAF4FF),
              iconColor: const Color(0xFF1E88E5),
              title: 'profile.help_support'.tr(),
              subtitle: 'profile.help_support_subtitle'.tr(),
              onTap: _showSupportInfo,
            ),
            _menuDivider(),
            _menuItem(
              icon: Icons.settings_outlined,
              iconBg: const Color(0xFFF1EEF6),
              iconColor: Colors.black54,
              title: 'settings.title'.tr(),
              subtitle: 'settings.subtitle'.tr(),
              onTap: () => context.push(RouteNames.settings),
            ),
          ],
        ),
      ),
    );
  }

  Widget _menuItem({
    required IconData icon,
    required Color iconBg,
    required Color iconColor,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
    bool danger = false,
    bool showChevron = true,
  }) {
    return ListTile(
      contentPadding: EdgeInsets.symmetric(horizontal: 14.w, vertical: 4.h),
      minLeadingWidth: 0,
      leading: Container(
        width: 44.r,
        height: 44.r,
        decoration: BoxDecoration(
          color: iconBg,
          borderRadius: BorderRadius.circular(12.r),
        ),
        child: Icon(icon, color: iconColor, size: 22.sp),
      ),
      title: Text(
        title,
        style: TextStyle(
          color: danger
              ? AppColors.danger
              : Theme.of(context).colorScheme.onSurface,
          fontSize: 14.sp,
          fontWeight: FontWeight.w700,
        ),
      ),
      subtitle: Text(
        subtitle,
        style: TextStyle(
          color: Theme.of(context).hintColor,
          fontSize: 12.sp,
          fontWeight: FontWeight.w500,
        ),
      ),
      trailing: showChevron
          ? Icon(
              Icons.chevron_right,
              size: 22.sp,
              color: Theme.of(context).hintColor,
            )
          : null,
      onTap: onTap,
    );
  }

  Widget _menuDivider() {
    return Container(
      height: 1,
      margin: EdgeInsets.only(left: 70.w),
      color: const Color(0xFFF1EEF6),
    );
  }

  String _initial(String name) =>
      name.trim().isEmpty ? '?' : name.trim()[0].toUpperCase();

  void _showVehicleInfo(VehicleInfo v) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return SafeArea(
          child: Container(
            padding: EdgeInsets.fromLTRB(16.w, 16.h, 16.w, 24.h),
            decoration: BoxDecoration(
              color: Theme.of(context).cardColor,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24.r)),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Center(
                  child: Container(
                    width: 42,
                    height: 4,
                    margin: EdgeInsets.only(bottom: 16.h),
                    decoration: BoxDecoration(
                      color: Colors.black12,
                      borderRadius: BorderRadius.circular(100),
                    ),
                  ),
                ),
                Text(
                  'profile.vehicle_information'.tr(),
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 18.sp,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                SizedBox(height: 16.h),
                _infoRow('profile.category'.tr(), v.categoryName ?? '—'),
                _sheetDivider(),
                _infoRow(
                  'profile.make_model'.tr(),
                  '${v.make ?? ''} ${v.model ?? ''}'.trim(),
                ),
                _sheetDivider(),
                _infoRow(
                  'profile.registration'.tr(),
                  v.registrationNumber ?? '—',
                ),
                _sheetDivider(),
                _infoRow('profile.color'.tr(), v.color ?? '—'),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _sheetDivider() {
    // Let the line overflow the sheet's 16.w horizontal padding so it spans the
    // full screen width edge-to-edge.
    final width = MediaQuery.of(context).size.width;
    return SizedBox(
      height: 1,
      child: OverflowBox(
        maxWidth: width,
        child: Container(
          width: width,
          height: 1,
          color: Theme.of(context).dividerColor.withValues(alpha: 0.25),
        ),
      ),
    );
  }

  Widget _infoRow(String label, String value) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 12.h),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Fixed-ratio columns keep every row's label/value aligned.
          Expanded(
            flex: 2,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 13.sp,
                color: Theme.of(context).hintColor,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          SizedBox(width: 12.w),
          Expanded(
            flex: 3,
            child: Text(
              value.trim().isEmpty ? 'common.no_data'.tr() : value,
              textAlign: TextAlign.end,
              style: TextStyle(
                fontSize: 13.sp,
                color: Theme.of(context).colorScheme.onSurface,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _showSupportInfo() {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return SafeArea(
          child: Container(
            padding: EdgeInsets.fromLTRB(16.w, 16.h, 16.w, 24.h),
            decoration: BoxDecoration(
              color: Theme.of(context).cardColor,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24.r)),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 42,
                    height: 4,
                    margin: EdgeInsets.only(bottom: 14.h),
                    decoration: BoxDecoration(
                      color: Colors.black12,
                      borderRadius: BorderRadius.circular(100),
                    ),
                  ),
                ),
                Center(
                  child: Text(
                    'profile.help_support'.tr(),
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 18.sp,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                SizedBox(height: 10.h),
                Text(
                  'profile.support_info'.tr(),
                  style: TextStyle(
                    fontSize: 13.sp,
                    color: Theme.of(context).hintColor,
                    height: 1.4,
                  ),
                ),
                SizedBox(height: 14.h),
                Align(
                  alignment: Alignment.centerRight,
                  child: TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: Text('common.close'.tr()),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

/// Fixed-height sliver header so the profile header stays pinned at the top
/// while the summary and menu cards scroll underneath it.
class _PinnedHeaderDelegate extends SliverPersistentHeaderDelegate {
  _PinnedHeaderDelegate({required this.height, required this.child});

  final double height;
  final Widget child;

  @override
  double get minExtent => height;

  @override
  double get maxExtent => height;

  @override
  Widget build(
    BuildContext context,
    double shrinkOffset,
    bool overlapsContent,
  ) => SizedBox.expand(child: child);

  @override
  bool shouldRebuild(_PinnedHeaderDelegate oldDelegate) =>
      oldDelegate.height != height || oldDelegate.child != child;
}
