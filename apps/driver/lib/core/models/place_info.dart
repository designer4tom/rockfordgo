/// Parse a dynamic value into a double safely.
double toDouble(dynamic v) =>
    v is num ? v.toDouble() : double.tryParse(v?.toString() ?? '') ?? 0.0;

/// A geographic location with a human-readable address.
class PlaceInfo {
  final String address;
  final double lat;
  final double lng;

  PlaceInfo({required this.address, required this.lat, required this.lng});

  factory PlaceInfo.fromJson(Map<String, dynamic> json) => PlaceInfo(
        address: (json['address'] ?? json['name'] ?? '').toString(),
        lat: toDouble(json['lat'] ?? json['latitude']),
        lng: toDouble(json['lng'] ?? json['longitude'] ?? json['lon']),
      );
}
