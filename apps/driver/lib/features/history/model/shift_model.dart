import '../../../core/utils/helpers.dart';

class ShiftModel {
  final int id;
  final DateTime? date;
  final String onlineTime;
  final String hours;
  final int trips;
  final String earning;

  ShiftModel({
    required this.id,
    this.date,
    required this.onlineTime,
    required this.hours,
    required this.trips,
    required this.earning,
  });

  factory ShiftModel.fromJson(Map<String, dynamic> json) => ShiftModel(
        id: json['id'] is int
            ? json['id']
            : int.tryParse(json['id']?.toString() ?? '') ?? 0,
        date: Helpers.tryParse(json['date'] ?? json['created_at']),
        onlineTime: (json['online_time'] ?? '').toString(),
        hours: (json['hours'] ?? json['online_hours'] ?? '0').toString(),
        trips: json['trips'] is int
            ? json['trips']
            : int.tryParse(json['trips']?.toString() ?? '') ?? 0,
        earning: (json['earning'] ?? '0').toString(),
      );
}
