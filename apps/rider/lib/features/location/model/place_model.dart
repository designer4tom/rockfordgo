class PlaceModel {
  final String address;
  final double lat;
  final double lng;
  final String? placeId;
  final String? name;

  PlaceModel({
    required this.address,
    required this.lat,
    required this.lng,
    this.placeId,
    this.name,
  });

  factory PlaceModel.fromJson(Map<String, dynamic> json) {
    return PlaceModel(
      address: json['address'] ?? json['formatted_address'] ?? '',
      lat: _toDouble(json['lat'] ?? json['latitude']),
      lng: _toDouble(json['lng'] ?? json['longitude']),
      placeId: json['place_id']?.toString(),
      name: json['name'],
    );
  }

  Map<String, dynamic> toJson() => {
        'address': address,
        'lat': lat,
        'lng': lng,
        'place_id': placeId,
        'name': name,
      };

  static double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }
}
