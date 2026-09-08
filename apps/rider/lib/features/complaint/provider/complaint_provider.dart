import 'package:flutter/material.dart';

import '../../../core/network/api_exception.dart';
import '../model/complaint_model.dart';
import '../repository/complaint_repository.dart';

class ComplaintProvider extends ChangeNotifier {
  final ComplaintRepository _repository;

  ComplaintProvider(this._repository);

  List<ComplaintModel> complaints = [];
  bool isLoading = false;
  bool isSubmitting = false;
  String? error;

  Future<void> loadComplaints() async {
    isLoading = true;
    notifyListeners();
    try {
      complaints = await _repository.getComplaints();
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> createComplaint({
    required String category,
    required String description,
    int? orderId,
  }) async {
    isSubmitting = true;
    notifyListeners();
    try {
      final complaint = await _repository.createComplaint(
        category: category,
        description: description,
        orderId: orderId,
      );
      complaints.insert(0, complaint);
      isSubmitting = false;
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      error = e.message;
      isSubmitting = false;
      notifyListeners();
      return false;
    }
  }
}
