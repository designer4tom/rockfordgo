import '../../../core/utils/helpers.dart';

class RecentRating {
  final int rating;
  final String? comment;
  final String? customerName;
  final String? customerImage;
  final DateTime? date;

  RecentRating({
    required this.rating,
    this.comment,
    this.customerName,
    this.customerImage,
    this.date,
  });

  factory RecentRating.fromJson(Map<String, dynamic> json) => RecentRating(
        rating: json['rating'] is int
            ? json['rating']
            : int.tryParse(json['rating']?.toString() ?? '') ?? 0,
        comment: json['comment']?.toString(),
        customerName: json['customer_name']?.toString(),
        customerImage: Helpers.imageUrl(json['customer_image']?.toString()),
        date: Helpers.tryParse(json['created_at'] ?? json['date']),
      );
}

class PerformanceModel {
  final String averageRating;
  final int totalRatings;
  final Map<int, int> ratingBreakdown; // 5:180, 4:50...
  final String acceptanceRate;
  final String completionRate;
  final String cancellationRate;
  final List<RecentRating> recentRatings;

  PerformanceModel({
    required this.averageRating,
    required this.totalRatings,
    required this.ratingBreakdown,
    required this.acceptanceRate,
    required this.completionRate,
    required this.cancellationRate,
    required this.recentRatings,
  });

  factory PerformanceModel.fromJson(Map<String, dynamic> json) {
    final breakdown = <int, int>{};
    final raw = json['rating_breakdown'];
    if (raw is Map) {
      raw.forEach((k, v) {
        final star = int.tryParse(k.toString());
        final count = v is int ? v : int.tryParse(v.toString()) ?? 0;
        if (star != null) breakdown[star] = count;
      });
    }
    final recent = (json['recent_ratings'] as List?) ?? const [];

    return PerformanceModel(
      averageRating: (json['average_rating'] ?? '0.0').toString(),
      totalRatings: json['total_ratings'] is int
          ? json['total_ratings']
          : int.tryParse(json['total_ratings']?.toString() ?? '') ?? 0,
      ratingBreakdown: breakdown,
      acceptanceRate: (json['acceptance_rate'] ?? '0').toString(),
      completionRate: (json['completion_rate'] ?? '0').toString(),
      cancellationRate: (json['cancellation_rate'] ?? '0').toString(),
      recentRatings: recent
          .map((e) => RecentRating.fromJson((e as Map).cast<String, dynamic>()))
          .toList(),
    );
  }
}
