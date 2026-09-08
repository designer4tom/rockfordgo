import 'package:easy_localization/easy_localization.dart';

class FavouriteModel {
  final int id;
  final String label; // home/office/custom
  final String? customLabel;
  final String address;
  final double lat;
  final double lng;

  FavouriteModel({
    required this.id,
    this.label = 'custom',
    this.customLabel,
    this.address = '',
    this.lat = 0,
    this.lng = 0,
  });

  factory FavouriteModel.fromJson(Map<String, dynamic> json) {
    return FavouriteModel(
      id: json['id'] ?? 0,
      label: json['label'] ?? 'custom',
      customLabel: json['custom_label'],
      address: json['address'] ?? '',
      lat: _toDouble(json['lat']),
      lng: _toDouble(json['lng']),
    );
  }

  String get displayLabel => switch (label) {
        'home' => 'favourite.home'.tr(),
        'office' => 'favourite.office'.tr(),
        _ => customLabel ?? 'favourite.other'.tr(),
      };

  static double _toDouble(dynamic v) {
    if (v is num) return v.toDouble();
    if (v is String) return double.tryParse(v) ?? 0;
    return 0;
  }
}
