import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';

/// Slide-to-confirm action button — prevents accidental status changes.
/// Falls back to a normal tap button when [slideToConfirm] is false.
class StatusActionButton extends StatefulWidget {
  final String label;
  final VoidCallback onConfirm;
  final bool loading;
  final bool slideToConfirm;
  final Color color;
  final IconData icon;

  const StatusActionButton({
    super.key,
    required this.label,
    required this.onConfirm,
    this.loading = false,
    this.slideToConfirm = true,
    this.color = AppColors.primary,
    this.icon = Icons.chevron_right,
  });

  @override
  State<StatusActionButton> createState() => _StatusActionButtonState();
}

class _StatusActionButtonState extends State<StatusActionButton> {
  double _dragX = 0;
  static const _thumb = 56.0;

  @override
  Widget build(BuildContext context) {
    if (!widget.slideToConfirm) {
      return SizedBox(
        height: _thumb,
        width: double.infinity,
        child: ElevatedButton(
          onPressed: widget.loading ? null : widget.onConfirm,
          style: ElevatedButton.styleFrom(backgroundColor: widget.color),
          child: widget.loading
              ? const SizedBox(
                  height: 22,
                  width: 22,
                  child: CircularProgressIndicator(
                      color: Colors.white, strokeWidth: 2.4),
                )
              : Text(widget.label),
        ),
      );
    }

    return LayoutBuilder(
      builder: (context, constraints) {
        final maxX = constraints.maxWidth - _thumb - 8;
        return Container(
          height: _thumb,
          decoration: BoxDecoration(
            color: widget.color.withValues(alpha: 0.15),
            borderRadius: BorderRadius.circular(_thumb / 2),
          ),
          child: Stack(
            alignment: Alignment.center,
            children: [
              Text(
                widget.loading ? 'common.please_wait'.tr() : widget.label,
                style: TextStyle(
                  color: widget.color,
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
              Positioned(
                left: 4 + _dragX,
                child: GestureDetector(
                  onHorizontalDragUpdate: widget.loading
                      ? null
                      : (d) {
                          setState(() {
                            _dragX = (_dragX + d.delta.dx).clamp(0.0, maxX);
                          });
                        },
                  onHorizontalDragEnd: widget.loading
                      ? null
                      : (_) {
                          if (_dragX >= maxX - 4) {
                            widget.onConfirm();
                          }
                          setState(() => _dragX = 0);
                        },
                  child: Container(
                    width: _thumb - 8,
                    height: _thumb - 8,
                    decoration: BoxDecoration(
                      color: widget.color,
                      shape: BoxShape.circle,
                    ),
                    child: widget.loading
                        ? const Padding(
                            padding: EdgeInsets.all(14),
                            child: CircularProgressIndicator(
                                color: Colors.white, strokeWidth: 2.4),
                          )
                        : Icon(widget.icon, color: Colors.white),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
