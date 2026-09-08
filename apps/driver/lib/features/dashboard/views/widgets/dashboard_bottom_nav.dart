import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

class DashboardBottomNav extends StatelessWidget {
  final int currentIndex;
  final ValueChanged<int> onChanged;

  const DashboardBottomNav({
    super.key,
    required this.currentIndex,
    required this.onChanged,
  });

  static const _activeColor = Color(0xFF5B3DF6);

  static const _items = <_NavItemData>[
    _NavItemData(Icons.home_rounded, 'nav.home'),
    _NavItemData(Icons.bar_chart_rounded, 'nav.earnings'),
    _NavItemData(Icons.assignment_outlined, 'nav.orders'),
    _NavItemData(Icons.person_outline, 'nav.profile'),
  ];

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: Container(
        margin: const EdgeInsets.fromLTRB(12, 0, 12, 8),
        decoration: BoxDecoration(
          color: Theme.of(context).cardColor,
          borderRadius: BorderRadius.circular(999),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              blurRadius: 16,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 6),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              for (var i = 0; i < _items.length; i++)
                _NavItem(
                  data: _items[i],
                  active: currentIndex == i,
                  onTap: () => onChanged(i),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NavItemData {
  final IconData icon;
  final String label;
  const _NavItemData(this.icon, this.label);
}

class _NavItem extends StatelessWidget {
  final _NavItemData data;
  final bool active;
  final VoidCallback onTap;

  const _NavItem({
    required this.data,
    required this.active,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final color = active ? DashboardBottomNav._activeColor : Theme.of(context).hintColor;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 10),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(data.icon, color: color, size: 20),
            const SizedBox(height: 2),
            Text(
              data.label.tr(),
              style: TextStyle(
                color: color,
                fontSize: 11,
                fontWeight: active ? FontWeight.w600 : FontWeight.w500,
              ),
            ),
            //const SizedBox(height: 2),
            // Container(
            //   width: 18,
            //   height: 2,
            //   decoration: BoxDecoration(
            //     color: active ? DashboardBottomNav._activeColor : Colors.transparent,
            //     borderRadius: BorderRadius.circular(2),
            //   ),
            // ),
          ],
        ),
      ),
    );
  }
}
