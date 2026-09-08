import '../model/complaint_model.dart';

abstract class ComplaintRepository {
  Future<List<ComplaintModel>> getComplaints();

  Future<ComplaintModel> createComplaint({
    required String category,
    required String description,
    int? orderId,
  });
}
