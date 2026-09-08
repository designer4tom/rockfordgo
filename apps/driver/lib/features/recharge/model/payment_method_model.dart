class PaymentMethodModel {
  final String code; // bkash | nagad | card ...
  final String name;
  final String? icon; // optional image url

  PaymentMethodModel({required this.code, required this.name, this.icon});

  factory PaymentMethodModel.fromJson(Map<String, dynamic> json) =>
      PaymentMethodModel(
        // The backend uses `name` as the gateway slug (e.g. "stripe") and
        // `label` as the human-readable text (e.g. "Stripe"), so `name`
        // must be checked here too or the code stays blank.
        code: (json['code'] ??
                json['method'] ??
                json['id'] ??
                json['gateway'] ??
                json['key'] ??
                json['slug'] ??
                json['value'] ??
                json['name'] ??
                '')
            .toString(),
        name: (json['label'] ??
                json['name'] ??
                json['title'] ??
                json['gateway'] ??
                '')
            .toString(),
        icon: json['icon']?.toString(),
      );

  /// Static fallback list used until the backend returns real methods.
  static List<PaymentMethodModel> defaults() => [
        PaymentMethodModel(code: 'bkash', name: 'bKash'),
        PaymentMethodModel(code: 'nagad', name: 'Nagad'),
        PaymentMethodModel(code: 'card', name: 'Card'),
      ];
}
