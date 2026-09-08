import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';

/// Large online/offline control with a pulse animation while online.
class OnlineToggle extends StatefulWidget {
  final bool isOnline;
  final bool loading;
  final VoidCallback onToggle;

  const OnlineToggle({
    super.key,
    required this.isOnline,
    required this.loading,
    required this.onToggle,
  });

  @override
  State<OnlineToggle> createState() => _OnlineToggleState();
}

class _OnlineToggleState extends State<OnlineToggle>
    with SingleTickerProviderStateMixin {
  late final AnimationController _pulse;

  @override
  void initState() {
    super.initState();
    _pulse = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1400),
    )..repeat();
  }

  @override
  void dispose() {
    _pulse.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final online = widget.isOnline;
    final base = online ? AppColors.online : AppColors.offline;

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        GestureDetector(
          onTap: widget.loading ? null : widget.onToggle,
          child: SizedBox(
            width: 150,
            height: 150,
            child: Stack(
              alignment: Alignment.center,
              children: [
                if (online)
                  AnimatedBuilder(
                    animation: _pulse,
                    builder: (_, _) {
                      final t = _pulse.value;
                      return Container(
                        width: 90 + 60 * t,
                        height: 90 + 60 * t,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: base.withValues(alpha: (1 - t) * 0.25),
                        ),
                      );
                    },
                  ),
                Container(
                  width: 110,
                  height: 110,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: base,
                    boxShadow: [
                      BoxShadow(
                        color: base.withValues(alpha: 0.4),
                        blurRadius: 16,
                        spreadRadius: 2,
                      ),
                    ],
                  ),
                  child: widget.loading
                      ? const Center(
                          child: SizedBox(
                            width: 28,
                            height: 28,
                            child: CircularProgressIndicator(
                              color: Colors.white,
                              strokeWidth: 3,
                            ),
                          ),
                        )
                      : Icon(
                          online ? Icons.power_settings_new : Icons.power_off,
                          color: Colors.white,
                          size: 44,
                        ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),
        Text(
          online ? 'home.online'.tr() : 'home.offline'.tr(),
          style: TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.bold,
            color: base,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          online
              ? 'home.searching_for_orders'.tr()
              : 'home.tap_to_go_online'.tr(),
          style: TextStyle(color: Theme.of(context).hintColor),
        ),
      ],
    );
  }
}
