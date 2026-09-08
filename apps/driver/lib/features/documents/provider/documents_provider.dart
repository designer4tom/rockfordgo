import 'dart:io';

import 'package:flutter/foundation.dart';

import '../../../core/network/api_exception.dart';
import '../model/document_model.dart';
import '../repository/documents_repository.dart';

class DocumentsProvider extends ChangeNotifier {
  final DocumentsRepository _repository;
  DocumentsProvider(this._repository);

  List<DocumentModel> documents = [];
  bool loading = false;
  bool submitting = false;
  String? error;

  bool get hasExpiryAlert =>
      documents.any((d) => d.isExpired || d.isExpiringSoon);

  Future<void> loadDocuments() async {
    loading = true;
    notifyListeners();
    try {
      documents = await _repository.getDocuments();
      error = null;
    } on ApiException catch (e) {
      error = e.message;
    }
    loading = false;
    notifyListeners();
  }

  Future<bool> updateDocument({
    required String type,
    required File file,
    File? backFile,
    DateTime? expiryDate,
  }) async {
    submitting = true;
    error = null;
    notifyListeners();
    try {
      final ok = await _repository.updateDocument(
        type: type,
        file: file,
        backFile: backFile,
        expiryDate: expiryDate,
      );
      submitting = false;
      if (ok) await loadDocuments();
      notifyListeners();
      return ok;
    } on ApiException catch (e) {
      error = e.message;
      submitting = false;
      notifyListeners();
      return false;
    }
  }
}
