double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  if (value is String) return double.tryParse(value) ?? 0.0;
  return 0.0;
}

class FareBreakdown {
  final String baseFare;
  final String distanceCharge;
  final String timeCharge;
  final String surgeAmount;
  final double surgeMultiplier;

  FareBreakdown({
    this.baseFare = '0.00',
    this.distanceCharge = '0.00',
    this.timeCharge = '0.00',
    this.surgeAmount = '0.00',
    this.surgeMultiplier = 1.0,
  });

  factory FareBreakdown.fromJson(Map<String, dynamic> json) {
    return FareBreakdown(
      baseFare: (json['base_fare'] ?? '0.00').toString(),
      distanceCharge: (json['distance_charge'] ?? '0.00').toString(),
      timeCharge: (json['time_charge'] ?? '0.00').toString(),
      surgeAmount: (json['surge_amount'] ?? '0.00').toString(),
      surgeMultiplier: _toDouble(json['surge_multiplier'] ?? 1.0),
    );
  }
}

class FareEstimateModel {
  final int vehicleCategoryId;
  final String vehicleCategoryName;
  final double distanceKm;
  final int durationMinutes;
  final FareBreakdown breakdown;
  final String totalFare;
  final String minimumFare;
  final String finalFare;
  final bool surgeActive;

  FareEstimateModel({
    required this.vehicleCategoryId,
    this.vehicleCategoryName = '',
    this.distanceKm = 0,
    this.durationMinutes = 0,
    required this.breakdown,
    this.totalFare = '0.00',
    this.minimumFare = '0.00',
    this.finalFare = '0.00',
    this.surgeActive = false,
  });

  factory FareEstimateModel.fromJson(Map<String, dynamic> json) {
    return FareEstimateModel(
      vehicleCategoryId:
          json['vehicle_category_id'] ?? json['category_id'] ?? 0,
      vehicleCategoryName: json['vehicle_category_name'] ?? '',
      distanceKm: _toDouble(json['distance_km'] ?? json['distance']),
      durationMinutes: json['duration_minutes'] ?? json['duration'] ?? 0,
      breakdown: json['breakdown'] != null
          ? FareBreakdown.fromJson(Map<String, dynamic>.from(json['breakdown']))
          : FareBreakdown(),
      totalFare: (json['total_fare'] ?? '0.00').toString(),
      minimumFare: (json['minimum_fare'] ?? '0.00').toString(),
      finalFare:
          (json['final_fare'] ?? json['total_fare'] ?? '0.00').toString(),
      surgeActive: json['surge_active'] ?? false,
    );
  }
}
