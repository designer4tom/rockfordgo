/// Missing means "active" — an older backend omitted the flag entirely.
bool _parseBool(dynamic value, {bool orElse = true}) {
  if (value == null) return orElse;
  if (value is bool) return value;
  if (value is num) return value != 0;
  final s = value.toString().toLowerCase();
  return s == 'true' || s == '1';
}

class PaymentMethodModel {
  final String id; // bkash / nagad / card
  final String name;
  final String icon;
  final bool isActive;

  PaymentMethodModel({
    required this.id,
    this.name = '',
    this.icon = '',
    this.isActive = true,
  });

  factory PaymentMethodModel.fromJson(Map<String, dynamic> json) {
    // Backends have used different key names for the gateway identifier
    // (id / key / code / gateway / value) across environments. If the
    // expected field is missing, every method falls back to the same
    // empty string and the UI ends up "selecting" all of them at once —
    // so try the known aliases before giving up, and fall back to the
    // name so at least each row stays distinct.
    final rawId = json['id'] ??
        json['key'] ??
        json['code'] ??
        json['gateway'] ??
        json['value'];
    final name = (json['name'] ?? '').toString();
    return PaymentMethodModel(
      id: (rawId == null || rawId.toString().isEmpty)
          ? name
          : rawId.toString(),
      name: name,
      icon: (json['icon'] ?? '').toString(),
      // `is_active` has come back as `1` / `"1"` as well as a real bool;
      // assigning a non-bool here threw mid-parse and left the method list
      // permanently empty (the top-up screen spun forever).
      isActive: _parseBool(json['is_active']),
    );
  }
}
