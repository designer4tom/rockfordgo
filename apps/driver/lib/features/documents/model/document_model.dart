class DocumentModel {
  final String type;
  final String typeLabel;
  final String status; // pending/approved/rejected/expired/expiring_soon
  final String? expiryDate;
  final String? rejectionReason;
  final int? daysUntilExpiry;
  final bool hasBack;

  DocumentModel({
    required this.type,
    required this.typeLabel,
    required this.status,
    this.expiryDate,
    this.rejectionReason,
    this.daysUntilExpiry,
    this.hasBack = false,
  });

  bool get isApproved => status == 'approved';
  bool get isRejected => status == 'rejected';
  bool get isExpired => status == 'expired';
  bool get isExpiringSoon => status == 'expiring_soon';
  bool get needsAction => isRejected || isExpired || isExpiringSoon;

  factory DocumentModel.fromJson(Map<String, dynamic> json) => DocumentModel(
        type: (json['type'] ?? '').toString(),
        typeLabel:
            (json['type_label'] ?? json['label'] ?? json['type'] ?? '')
                .toString(),
        status: (json['status'] ?? 'pending').toString(),
        expiryDate: json['expiry_date']?.toString(),
        rejectionReason: json['rejection_reason']?.toString(),
        daysUntilExpiry: json['days_until_expiry'] is int
            ? json['days_until_expiry']
            : int.tryParse(json['days_until_expiry']?.toString() ?? ''),
        hasBack: json['has_back'] == true,
      );
}
