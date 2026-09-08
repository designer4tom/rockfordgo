class ComplaintModel {
  final int id;
  final String category;
  final String description;
  final String status;
  final String? orderNumber;
  final String createdAt;

  ComplaintModel({
    required this.id,
    this.category = '',
    this.description = '',
    this.status = 'open',
    this.orderNumber,
    this.createdAt = '',
  });

  factory ComplaintModel.fromJson(Map<String, dynamic> json) {
    return ComplaintModel(
      id: json['id'] ?? 0,
      category: json['category'] ?? '',
      description: json['description'] ?? '',
      status: json['status'] ?? 'open',
      orderNumber: json['order_number'],
      createdAt: json['created_at'] ?? '',
    );
  }
}
