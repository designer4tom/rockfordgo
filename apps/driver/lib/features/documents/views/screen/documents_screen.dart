import 'dart:io';

import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../core/utils/app_snackbar.dart';
import '../../../../core/widgets/empty_state.dart';
import '../../../../core/widgets/loading_indicator.dart';
import '../../model/document_model.dart';
import '../../provider/documents_provider.dart';
import '../widgets/document_status_card.dart';

class DocumentsScreen extends StatefulWidget {
  const DocumentsScreen({super.key});

  @override
  State<DocumentsScreen> createState() => _DocumentsScreenState();
}

class _DocumentsScreenState extends State<DocumentsScreen> {
  final _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<DocumentsProvider>().loadDocuments();
    });
  }

  Future<void> _update(DocumentModel doc) async {
    final picked = await _picker.pickImage(
        source: ImageSource.gallery, imageQuality: 70, maxWidth: 1280);
    if (picked == null) return;

    DateTime? expiry;
    if (doc.expiryDate != null) {
      expiry = await showDatePicker(
        // ignore: use_build_context_synchronously
        context: context,
        initialDate: DateTime.now().add(const Duration(days: 365)),
        firstDate: DateTime.now(),
        lastDate: DateTime.now().add(const Duration(days: 365 * 20)),
      );
    }

    if (!mounted) return;
    final p = context.read<DocumentsProvider>();
    final ok = await p.updateDocument(
      type: doc.type,
      file: File(picked.path),
      expiryDate: expiry,
    );
    if (!mounted) return;
    AppSnackbar.show(
        context,
        ok
            ? 'documents.submitted_for_review'.tr()
            : (p.error ?? 'common.failed'.tr()));
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<DocumentsProvider>();
    return Scaffold(
      appBar: AppBar(title: Text('documents.title'.tr())),
      body: p.loading && p.documents.isEmpty
          ? const LoadingIndicator()
          : RefreshIndicator(
              onRefresh: () => p.loadDocuments(),
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (p.hasExpiryAlert)
                    Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: AppColors.danger.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.warning_amber_rounded,
                              color: AppColors.danger),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'documents.expiry_alert'.tr(),
                              style: const TextStyle(color: AppColors.danger),
                            ),
                          ),
                        ],
                      ),
                    ),
                  if (p.documents.isEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 60),
                      child: EmptyState(
                        icon: Icons.description_outlined,
                        title: 'documents.no_documents'.tr(),
                      ),
                    )
                  else
                    ...p.documents.map((d) => DocumentStatusCard(
                          document: d,
                          onUpdate: () => _update(d),
                        )),
                ],
              ),
            ),
    );
  }
}
