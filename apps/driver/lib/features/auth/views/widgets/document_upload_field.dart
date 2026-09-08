import 'dart:io';

import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/helpers.dart';

class DocumentUploadField extends StatelessWidget {
  final String label;
  final bool required;
  final File? file;
  final void Function(ImageSource source) onPick;
  final VoidCallback? onRemove;

  // Optional expiry date support.
  final bool hasExpiry;
  final DateTime? expiryDate;
  final VoidCallback? onExpiryPick;

  const DocumentUploadField({
    super.key,
    required this.label,
    required this.onPick,
    this.required = true,
    this.file,
    this.onRemove,
    this.hasExpiry = false,
    this.expiryDate,
    this.onExpiryPick,
  });

  void _showSourceSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      builder: (_) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.camera_alt_outlined),
              title: Text('documents.camera'.tr()),
              onTap: () {
                Navigator.pop(context);
                onPick(ImageSource.camera);
              },
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: Text('documents.gallery'.tr()),
              onTap: () {
                Navigator.pop(context);
                onPick(ImageSource.gallery);
              },
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(
              label,
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
            if (required)
              const Text(' *', style: TextStyle(color: AppColors.danger)),
          ],
        ),
        const SizedBox(height: 8),
        GestureDetector(
          onTap: () => _showSourceSheet(context),
          child: Container(
            height: 120,
            width: double.infinity,
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: file == null
                    ? Theme.of(context).dividerColor
                    : AppColors.primary,
                style: BorderStyle.solid,
              ),
            ),
            child: file == null
                ? Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.cloud_upload_outlined,
                          color: Theme.of(context).hintColor, size: 32),
                      const SizedBox(height: 6),
                      Text('documents.tap_to_upload'.tr(),
                          style: TextStyle(
                              color: Theme.of(context).hintColor)),
                    ],
                  )
                : Stack(
                    fit: StackFit.expand,
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: Image.file(file!, fit: BoxFit.cover),
                      ),
                      Positioned(
                        top: 6,
                        right: 6,
                        child: InkWell(
                          onTap: onRemove,
                          child: const CircleAvatar(
                            radius: 14,
                            backgroundColor: AppColors.danger,
                            child: Icon(Icons.close,
                                size: 16, color: Colors.white),
                          ),
                        ),
                      ),
                    ],
                  ),
          ),
        ),
        if (hasExpiry) ...[
          const SizedBox(height: 8),
          InkWell(
            onTap: onExpiryPick,
            child: Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Theme.of(context).dividerColor),
              ),
              child: Row(
                children: [
                  Icon(Icons.event_outlined,
                      size: 20, color: Theme.of(context).hintColor),
                  const SizedBox(width: 10),
                  Text(
                    expiryDate == null
                        ? 'documents.expiry_date'.tr()
                        : Helpers.date(expiryDate),
                    style: TextStyle(
                      color: expiryDate == null
                          ? Theme.of(context).hintColor
                          : Theme.of(context).colorScheme.onSurface,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
        const SizedBox(height: 16),
      ],
    );
  }
}
