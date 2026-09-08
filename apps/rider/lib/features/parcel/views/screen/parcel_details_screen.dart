import 'dart:io';

import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/widgets/custom_button.dart';
import '../../provider/parcel_provider.dart';
import '../widgets/parcel_size_selector.dart';
import '../widgets/parcel_step_indicator.dart';
import '../widgets/parcel_type_selector.dart';

class ParcelDetailsScreen extends StatefulWidget {
  const ParcelDetailsScreen({super.key});

  @override
  State<ParcelDetailsScreen> createState() => _ParcelDetailsScreenState();
}

class _ParcelDetailsScreenState extends State<ParcelDetailsScreen> {
  late String _type;
  late double _weight;
  late String _size;
  File? _photo;
  late final TextEditingController _note;

  @override
  void initState() {
    super.initState();
    final p = context.read<ParcelProvider>();
    _type = p.parcelType;
    _weight = p.weight;
    _size = p.size;
    _photo = p.parcelPhoto;
    _note = TextEditingController(text: p.parcelNote);
  }

  @override
  void dispose() {
    _note.dispose();
    super.dispose();
  }

  Future<void> _pickPhoto() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 70,
    );
    if (picked != null) setState(() => _photo = File(picked.path));
  }

  void _next() {
    final parcel = context.read<ParcelProvider>();
    parcel.setParcelDetails(
      type: _type,
      weight: _weight,
      size: _size,
      note: _note.text.trim().isEmpty ? null : _note.text.trim(),
      photo: _photo,
    );
    if (parcel.codEnabled) {
      parcel.currentStep = 3;
      context.push('/parcel-cod');
    } else {
      parcel.currentStep = 4;
      context.push('/parcel-confirm');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('parcel.title'.tr())),
      body: Column(
        children: [
          const ParcelStepIndicator(currentStep: 2),
          Expanded(
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Text('parcel.parcel_type'.tr(),
                    style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: 8),
                ParcelTypeSelector(
                  selected: _type,
                  onChanged: (v) => setState(() => _type = v),
                ),
                const SizedBox(height: 20),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('parcel.weight'.tr(),
                        style: Theme.of(context).textTheme.titleMedium),
                    Text('${_weight.toStringAsFixed(1)} kg',
                        style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            color: AppColors.primary)),
                  ],
                ),
                Slider(
                  value: _weight,
                  min: 0.5,
                  max: 20,
                  divisions: 39,
                  label: '${_weight.toStringAsFixed(1)} kg',
                  activeColor: AppColors.primary,
                  onChanged: (v) => setState(() => _weight = v),
                ),
                const SizedBox(height: 12),
                Text('parcel.size'.tr(),
                    style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: 8),
                ParcelSizeSelector(
                  selected: _size,
                  onChanged: (v) => setState(() => _size = v),
                ),
                const SizedBox(height: 20),
                Text('parcel.photo_optional'.tr(),
                    style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: 8),
                _photoPicker(),
                const SizedBox(height: 20),
                TextField(
                  controller: _note,
                  maxLines: 3,
                  decoration: InputDecoration(
                    labelText: 'parcel.note_optional'.tr(),
                    hintText: 'parcel.note_hint'.tr(),
                    alignLabelWithHint: true,
                  ),
                ),
              ],
            ),
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: CustomButton(text: 'common.next'.tr(), onPressed: _next),
            ),
          ),
        ],
      ),
    );
  }

  Widget _photoPicker() {
    return GestureDetector(
      onTap: _pickPhoto,
      child: Container(
        height: 120,
        width: double.infinity,
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Theme.of(context).dividerColor),
          image: _photo != null
              ? DecorationImage(image: FileImage(_photo!), fit: BoxFit.cover)
              : null,
        ),
        child: _photo == null
            ? Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.add_a_photo_outlined,
                      color: AppColors.textSecondary),
                  const SizedBox(height: 8),
                  Text('parcel.add_photo'.tr(),
                      style: const TextStyle(color: AppColors.textSecondary)),
                ],
              )
            : null,
      ),
    );
  }
}
