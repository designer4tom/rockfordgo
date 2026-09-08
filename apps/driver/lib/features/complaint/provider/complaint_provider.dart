import 'package:flutter/foundation.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/dio_client.dart';
import '../model/complaint_model.dart';

class ComplaintProvider extends ChangeNotifier {
  final DioClient _client;
  ComplaintProvider({DioClient? client})
      : _client = client ?? DioClient.instance;

  List<ComplaintModel> complaints = [];
  bool loading = false;
  bool submitting = false;
  String? error;

  Future<void> loadComplaints() async {
    loading = true;
    notifyListeners();
    try {
      final res = await _client.get(ApiEndpoints.complaints);
      final data = res.data['data'];
      final list = data is List ? data : const [];
      complaints = list
          .map((e) =>
              ComplaintModel.fromJson((e as Map).cast<String, dynamic>()))
          .toList();
      error = null;
    } catch (e) {
      error = DioClient.toApiException(e).message;
    }
    loading = false;
    notifyListeners();
  }

  Future<bool> createComplaint({
    int? orderId,
    required String category,
    required String description,
  }) async {
    submitting = true;
    error = null;
    notifyListeners();
    try {
      final res = await _client.post(ApiEndpoints.complaints, data: {
        'order_id': ?orderId,
        'category': category,
        'description': description,
      });
      final body = res.data as Map;
      final ok = body['success'] == true || body['status'] == true;
      submitting = false;
      if (ok) await loadComplaints();
      notifyListeners();
      return ok;
    } catch (e) {
      error = DioClient.toApiException(e).message;
      submitting = false;
      notifyListeners();
      return false;
    }
  }
}
