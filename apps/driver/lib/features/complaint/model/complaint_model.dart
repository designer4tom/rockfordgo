import '../../../core/utils/helpers.dart';

class ComplaintModel {
  final int id;
  final String category;
  final String description;
  final String status; // open | in_progress | resolved | closed
  final String? response;
  final DateTime? createdAt;

  ComplaintModel({
    required this.id,
    required this.category,
    required this.description,
    required this.status,
    this.response,
    this.createdAt,
  });

  factory ComplaintModel.fromJson(Map<String, dynamic> json) => ComplaintModel(
        id: json['id'] is int
            ? json['id']
            : int.tryParse(json['id']?.toString() ?? '') ?? 0,
        category: (json['category'] ?? '').toString(),
        description: (json['description'] ?? '').toString(),
        status: (json['status'] ?? 'open').toString(),
        response: json['response']?.toString(),
        createdAt: Helpers.tryParse(json['created_at'] ?? json['date']),
      );
}
