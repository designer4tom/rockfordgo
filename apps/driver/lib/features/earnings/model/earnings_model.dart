class EarningsModel {
  final String period;
  final String totalEarning;
  final String rideEarning;
  final String parcelEarning;
  final String commissionPaid;
  final String tipsReceived;
  final int totalTrips;
  final String onlineHours;

  EarningsModel({
    required this.period,
    required this.totalEarning,
    required this.rideEarning,
    required this.parcelEarning,
    required this.commissionPaid,
    required this.tipsReceived,
    required this.totalTrips,
    required this.onlineHours,
  });

  factory EarningsModel.fromJson(Map<String, dynamic> json) => EarningsModel(
        period: (json['period'] ?? '').toString(),
        totalEarning: (json['total_earning'] ?? json['total'] ?? '0').toString(),
        rideEarning: (json['ride_earning'] ?? '0').toString(),
        parcelEarning: (json['parcel_earning'] ?? '0').toString(),
        commissionPaid: (json['commission_paid'] ?? json['commission'] ?? '0')
            .toString(),
        tipsReceived: (json['tips_received'] ?? json['tips'] ?? '0').toString(),
        totalTrips: _toInt(json['total_trips'] ?? json['trips']),
        onlineHours: (json['online_hours'] ?? '0').toString(),
      );

  static int _toInt(dynamic v) =>
      v is int ? v : int.tryParse(v?.toString() ?? '') ?? 0;

  /// online_hours "6.5" → "6h 30m" ; "7.75" → "7h 45m".
  String get formattedOnlineTime {
    final hours = double.tryParse(onlineHours) ?? 0;
    if (hours <= 0) return '0h';
    final h = hours.floor();
    final m = ((hours - h) * 60).round();
    return m == 0 ? '${h}h' : '${h}h ${m}m';
  }
}

/// One bar/point on the earnings chart.
class ChartData {
  final String label; // e.g. day name / date
  final double value;

  ChartData({required this.label, required this.value});

  factory ChartData.fromJson(Map<String, dynamic> json) => ChartData(
        label: (json['label'] ?? json['date'] ?? json['day'] ?? '').toString(),
        value: (json['value'] ?? json['earning'] ?? json['total'] ?? 0) is num
            ? (json['value'] ?? json['earning'] ?? json['total'] ?? 0).toDouble()
            : double.tryParse(
                    (json['value'] ?? json['earning'] ?? '0').toString()) ??
                0,
      );
}
