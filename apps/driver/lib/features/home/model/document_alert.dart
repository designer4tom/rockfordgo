/// A document nearing expiry, surfaced on the home alerts area.
class DocumentAlert {
  final String name;
  final DateTime? expiry;
  final bool expired;

  DocumentAlert({required this.name, this.expiry, required this.expired});

  factory DocumentAlert.fromJson(Map<String, dynamic> json) {
    final exp = DateTime.tryParse((json['expiry'] ?? '').toString());
    return DocumentAlert(
      name: (json['name'] ?? json['document'] ?? '').toString(),
      expiry: exp,
      expired: json['expired'] == true ||
          (exp != null && exp.isBefore(DateTime.now())),
    );
  }
}
