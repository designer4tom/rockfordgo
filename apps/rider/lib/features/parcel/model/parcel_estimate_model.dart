class ParcelChargeBreakdown {
  final String baseCharge;
  final String distanceCharge;
  final String weightCharge;
  final String typeCharge;

  ParcelChargeBreakdown({
    this.baseCharge = '0.00',
    this.distanceCharge = '0.00',
    this.weightCharge = '0.00',
    this.typeCharge = '0.00',
  });

  factory ParcelChargeBreakdown.fromJson(Map<String, dynamic> json) {
    return ParcelChargeBreakdown(
      baseCharge: (json['base_charge'] ?? '0.00').toString(),
      distanceCharge: (json['distance_charge'] ?? '0.00').toString(),
      weightCharge: (json['weight_charge'] ?? '0.00').toString(),
      typeCharge: (json['type_charge'] ?? '0.00').toString(),
    );
  }
}

class ParcelEstimateModel {
  final double distanceKm;
  final String deliveryCharge;
  final ParcelChargeBreakdown breakdown;
  final List<String> paymentTimingOptions; // ['before', 'after']
  final bool codAvailable;

  ParcelEstimateModel({
    this.distanceKm = 0,
    this.deliveryCharge = '0.00',
    required this.breakdown,
    this.paymentTimingOptions = const ['before'],
    this.codAvailable = false,
  });

  factory ParcelEstimateModel.fromJson(Map<String, dynamic> json) {
    return ParcelEstimateModel(
      distanceKm: _toDouble(json['distance_km'] ?? json['distance']),
      deliveryCharge: (json['delivery_charge'] ?? '0.00').toString(),
      breakdown: json['breakdown'] != null
          ? ParcelChargeBreakdown.fromJson(
              Map<String, dynamic>.from(json['breakdown']))
          : ParcelChargeBreakdown(),
      paymentTimingOptions: (json['payment_timing_options'] as List?)
              ?.map((e) => e.toString())
              .toList() ??
          const ['before'],
      codAvailable: json['cod_available'] ?? false,
    );
  }

  static double _toDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }
}
