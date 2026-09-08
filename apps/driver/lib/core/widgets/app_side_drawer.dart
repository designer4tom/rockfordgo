import 'package:flutter/material.dart';

import '../constants/app_colors.dart';
import '../utils/helpers.dart';

/// Theme-aware drawer palette. The accent stays blue (Figma) in both modes;
/// surfaces and text/icons adapt so the drawer reads correctly in dark mode.
/// Light values intentionally equal the Figma constants — light mode is
/// pixel-identical to the design.
class _DrawerPalette {
  final Color surface; // drawer background
  final Color card; // wallet card background
  final Color name; // driver name / wallet value
  final Color subtitle; // phone, labels, version, card label
  final Color label; // menu row text
  final Color icon; // inactive menu icon
  final Color chevron; // inactive trailing chevron
  final Color divider; // group divider
  final Color accentSoft; // active row tint / icon tile / avatar bg

  const _DrawerPalette({
    required this.surface,
    required this.card,
    required this.name,
    required this.subtitle,
    required this.label,
    required this.icon,
    required this.chevron,
    required this.divider,
    required this.accentSoft,
  });

  factory _DrawerPalette.of(BuildContext context) {
    final dark = Theme.of(context).brightness == Brightness.dark;
    if (!dark) {
      return const _DrawerPalette(
        surface: Colors.white,
        card: Colors.white,
        name: AppColors.drawerName,
        subtitle: AppColors.drawerSubtitle,
        label: AppColors.drawerLabel,
        icon: AppColors.drawerIcon,
        chevron: AppColors.drawerChevron,
        divider: AppColors.drawerDivider,
        accentSoft: AppColors.drawerAccentSoft,
      );
    }
    return _DrawerPalette(
      surface: AppColors.darkBackground,
      card: AppColors.darkSurface,
      name: AppColors.darkTextPrimary,
      subtitle: AppColors.darkTextSecondary,
      label: AppColors.darkTextPrimary,
      icon: AppColors.darkTextSecondary,
      chevron: AppColors.darkTextSecondary,
      divider: AppColors.darkBorder,
      accentSoft: AppColors.drawerAccent.withValues(alpha: 0.20),
    );
  }
}

/// A single menu row, driven by your app's existing menu data.
class DrawerMenuItem {
  final IconData icon;
  final String label;
  final String? route; // informational; tap handled by [onTap] / onSelect
  final bool isActive;
  final int groupId; // rows with a different groupId get a divider between them
  final bool danger; // e.g. Logout — rendered in the danger color
  final VoidCallback? onTap;

  const DrawerMenuItem({
    required this.icon,
    required this.label,
    this.route,
    this.isActive = false,
    this.groupId = 0,
    this.danger = false,
    this.onTap,
  });
}

/// Header user data — map this from YOUR existing user/profile model.
class DrawerHeaderData {
  final String name;
  final String subtitle; // e.g. phone / email
  final String? avatarUrl;
  final bool verified;
  final String verifiedLabel;
  final String unverifiedLabel;

  const DrawerHeaderData({
    required this.name,
    required this.subtitle,
    this.avatarUrl,
    this.verified = false,
    this.verifiedLabel = 'Verified',
    this.unverifiedLabel = 'Pending',
  });
}

/// Optional summary/highlight card just under the header (e.g. wallet balance).
class DrawerSummaryCard {
  final IconData icon;
  final String label;
  final String value;
  final String actionLabel;
  final VoidCallback onAction;

  const DrawerSummaryCard({
    required this.icon,
    required this.label,
    required this.value,
    required this.actionLabel,
    required this.onAction,
  });
}

/// Reusable side navigation drawer — styled to the Figma drawer spec.
///
/// Drawer accent is [AppColors.drawerAccent] (blue) and is scoped to the
/// drawer only, leaving the app's amber branding untouched. Plug points:
///   • [header]   → your user data
///   • [summary]  → any summary card (or null to hide)
///   • [items]    → your existing menu model (icon/label/route/active/group)
///   • [version]  → footer version string
class AppSideDrawer extends StatelessWidget {
  final DrawerHeaderData header;
  final DrawerSummaryCard? summary;
  final List<DrawerMenuItem> items;
  final String appName;
  final String version;

  /// Fallback tap handler when a [DrawerMenuItem] has no [onTap].
  final void Function(DrawerMenuItem item)? onSelect;

  const AppSideDrawer({
    super.key,
    required this.header,
    required this.items,
    required this.appName,
    required this.version,
    this.summary,
    this.onSelect,
  });

  @override
  Widget build(BuildContext context) {
    // Figma drawer occupies ~82% of the viewport, clamped on large screens.
    final width =
        (MediaQuery.of(context).size.width * 0.82).clamp(280.0, 360.0);
    final c = _DrawerPalette.of(context);
    return Drawer(
      width: width,
      backgroundColor: c.surface,
      elevation: 0,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.only(
          topRight: Radius.circular(0),
          bottomRight: Radius.circular(0),
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Column(
          children: [
            // Header stays pinned at the top; only the list below scrolls.
            _Header(data: header),
            SizedBox(height: 8),
            Expanded(
              child: ListView(
                padding: EdgeInsets.zero,
                physics: const BouncingScrollPhysics(),
                children: [
                  if (summary != null) ...[
                    const SizedBox(height: 4),
                    _SummaryCard(data: summary!),
                    const SizedBox(height: 12),
                  ] else
                    const SizedBox(height: 8),
                  ..._buildRows(),
                  const SizedBox(height: 8),
                ],
              ),
            ),
            _Footer(label: '$appName v$version'),
          ],
        ),
      ),
    );
  }

  List<Widget> _buildRows() {
    final rows = <Widget>[];
    int? lastGroup;
    for (final item in items) {
      if (lastGroup != null && item.groupId != lastGroup) {
        rows.add(const _GroupDivider());
      }
      rows.add(_MenuRow(
        item: item,
        onTap: item.onTap ?? () => onSelect?.call(item),
      ));
      lastGroup = item.groupId;
    }
    return rows;
  }
}

// ---- Header ----
class _Header extends StatelessWidget {
  final DrawerHeaderData data;
  const _Header({required this.data});

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        // Soft tinted gradient background.
        Container(
          width: double.infinity,
          padding: const EdgeInsets.fromLTRB(20, 24, 20, 20),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                AppColors.primary.withValues(alpha: 0.12),
                AppColors.primary.withValues(alpha: 0.02),
              ],
            ),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(2),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  border: Border.all(
                      color:
                          Theme.of(context).cardColor.withValues(alpha: 0.9),
                      width: 2),
                ),
                child: CircleAvatar(
                  radius: 42,
                  backgroundColor: AppColors.primary.withValues(alpha: 0.15),
                  backgroundImage: Helpers.imageUrl(data.avatarUrl) != null
                      ? NetworkImage(Helpers.imageUrl(data.avatarUrl)!)
                      : null,
                  child: Helpers.imageUrl(data.avatarUrl) != null
                      ? null
                      : const Icon(Icons.person,
                          size: 40, color: AppColors.primary),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      data.name,
                      style: TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.bold,
                        color: Theme.of(context).colorScheme.onSurface,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 2),
                    Text(
                      data.subtitle,
                      style: TextStyle(
                          fontSize: 15, color: Theme.of(context).hintColor),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 6),
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          data.verified
                              ? Icons.verified
                              : Icons.hourglass_top_rounded,
                          size: 16,
                          color: data.verified
                              ? AppColors.primary
                              : AppColors.warning,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          data.verified
                              ? data.verifiedLabel
                              : data.unverifiedLabel,
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: data.verified
                                ? AppColors.primary
                                : AppColors.warning,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        // Decorative blob, top-right, low opacity.
        Positioned(
          top: -24,
          right: -24,
          child: IgnorePointer(
            child: Container(
              width: 96,
              height: 96,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: AppColors.primary.withValues(alpha: 0.06),
              ),
            ),
          ),
        ),
      ],
    );
  }
}

// ---- Summary card ----
class _SummaryCard extends StatelessWidget {
  final DrawerSummaryCard data;
  const _SummaryCard({required this.data});

  @override
  Widget build(BuildContext context) {
    final c = _DrawerPalette.of(context);
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: c.card,
          borderRadius: BorderRadius.circular(16),
          boxShadow: const [
            BoxShadow(
              color: AppColors.drawerCardShadow,
              blurRadius: 18,
              offset: Offset(0, 6),
            ),
          ],
        ),
        child: Material(
          color: Colors.transparent,
          borderRadius: BorderRadius.circular(16),
          child: InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: data.onAction,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(12, 12, 16, 12),
              child: Row(
                children: [
                  Padding(
                    padding: const EdgeInsets.all(8.0),
                    child: Container(
                      width: 36,
                      height: 36,
                      decoration: BoxDecoration(
                        color: AppColors.drawerAccentSoft,
                        shape: BoxShape.circle,
                      ),
                      child: Icon(data.icon,
                          color: AppColors.drawerAccent, size: 20),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          data.label,
                          style: TextStyle(
                            fontSize: 12,
                            height: 1.2,
                            color: c.subtitle,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          data.value,
                          style: TextStyle(
                            fontSize: 17,
                            height: 1.2,
                            fontWeight: FontWeight.w700,
                            color: c.name,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    data.actionLabel,
                    style: const TextStyle(
                      color: AppColors.drawerAccent,
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                  ),
                  const Icon(Icons.chevron_right_rounded,
                      color: AppColors.drawerAccent, size: 18),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

// ---- Menu row ----
class _MenuRow extends StatelessWidget {
  final DrawerMenuItem item;
  final VoidCallback onTap;
  const _MenuRow({required this.item, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final c = _DrawerPalette.of(context);
    final Color fg;
    final Color iconColor;
    if (item.danger) {
      fg = AppColors.danger;
      iconColor = AppColors.danger;
    } else if (item.isActive) {
      fg = AppColors.drawerAccent;
      iconColor = AppColors.drawerAccent;
    } else {
      fg = c.label;
      iconColor = c.icon;
    }

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 1),
      child: Material(
        color: item.isActive ? c.accentSoft : Colors.transparent,
        borderRadius: BorderRadius.circular(12),
        child: InkWell(
          borderRadius: BorderRadius.circular(12),
          onTap: onTap,
          child: SizedBox(
            height: 50,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              child: Row(
                children: [
                  Icon(item.icon, size: 22, color: iconColor),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Text(
                      item.label,
                      style: TextStyle(
                        fontSize: 15,
                        height: 1.2,
                        fontWeight:
                            item.isActive ? FontWeight.w600 : FontWeight.w500,
                        color: fg,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  Icon(
                    Icons.chevron_right_rounded,
                    size: 20,
                    color: item.isActive
                        ? AppColors.drawerAccent
                        : (item.danger ? AppColors.danger : c.chevron),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _GroupDivider extends StatelessWidget {
  const _GroupDivider();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 8),
      child: Divider(
        height: 1,
        thickness: 1,
        color: _DrawerPalette.of(context).divider,
      ),
    );
  }
}

// ---- Footer ----
class _Footer extends StatelessWidget {
  final String label;
  const _Footer({required this.label});

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).padding.bottom;
    return Padding(
      padding: EdgeInsets.only(top: 8, bottom: 16 + bottomInset),
      child: Center(
        child: Text(
          label,
          textAlign: TextAlign.center,
          style: TextStyle(
            color: _DrawerPalette.of(context).subtitle,
            fontSize: 12,
            fontWeight: FontWeight.w500,
          ),
        ),
      ),
    );
  }
}
