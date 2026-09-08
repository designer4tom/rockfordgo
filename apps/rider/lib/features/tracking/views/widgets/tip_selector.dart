import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';

class TipSelector extends StatefulWidget {
  final ValueChanged<double> onTip;

  const TipSelector({super.key, required this.onTip});

  @override
  State<TipSelector> createState() => _TipSelectorState();
}

class _TipSelectorState extends State<TipSelector> {
  static const _amounts = [10.0, 20.0, 50.0, 100.0];
  double? _selected;
  final _customController = TextEditingController();

  @override
  void dispose() {
    _customController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('tracking.tip_driver'.tr(),
            style: const TextStyle(fontWeight: FontWeight.w600)),
        const SizedBox(height: 10),
        Wrap(
          spacing: 8,
          children: _amounts.map((a) {
            final selected = _selected == a;
            return ChoiceChip(
              label: Text(Helpers.currency(a)),
              selected: selected,
              onSelected: (_) => setState(() {
                _selected = a;
                _customController.clear();
              }),
              selectedColor: AppColors.primary.withValues(alpha: 0.15),
            );
          }).toList(),
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _customController,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(
                  hintText: 'tracking.tip_custom'.tr(),
                  prefixText: '${Helpers.currencySymbol} ',
                ),
                onChanged: (v) =>
                    setState(() => _selected = double.tryParse(v)),
              ),
            ),
            const SizedBox(width: 8),
            ElevatedButton(
              onPressed: (_selected != null && _selected! > 0)
                  ? () => widget.onTip(_selected!)
                  : null,
              child: Text('tracking.tip_give'.tr()),
            ),
          ],
        ),
      ],
    );
  }
}
