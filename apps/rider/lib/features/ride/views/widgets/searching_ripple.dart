import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';

/// An animated blue ripple that pulses outward from a centre dot, marking the
/// rider's location while searching. Purely decorative — wrapped in
/// [IgnorePointer] by the caller so it never blocks map gestures.
class SearchingRipple extends StatefulWidget {
  final double size;
  const SearchingRipple({super.key, this.size = 160});

  @override
  State<SearchingRipple> createState() => _SearchingRippleState();
}

class _SearchingRippleState extends State<SearchingRipple>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2400),
    )..repeat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: widget.size,
      height: widget.size,
      child: AnimatedBuilder(
        animation: _controller,
        builder: (context, child) {
          return Stack(
            alignment: Alignment.center,
            children: [
              // Three staggered expanding rings.
              for (var i = 0; i < 3; i++)
                _ring((_controller.value + i / 3) % 1.0),
              // Solid centre dot.
              Container(
                width: 16,
                height: 16,
                decoration: BoxDecoration(
                  color: AppColors.primary,
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white, width: 3),
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.primary.withValues(alpha: 0.5),
                      blurRadius: 8,
                    ),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _ring(double t) {
    final scale = Curves.easeOut.transform(t);
    return Opacity(
      opacity: (1 - t).clamp(0.0, 1.0) * 0.5,
      child: Container(
        width: widget.size * scale,
        height: widget.size * scale,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: AppColors.primary.withValues(alpha: 0.18),
        ),
      ),
    );
  }
}
