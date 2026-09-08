import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/dio_client.dart';
import '../model/earnings_model.dart';

class EarningsRepository {
  final DioClient _client;
  EarningsRepository({DioClient? client})
      : _client = client ?? DioClient.instance;

  Future<EarningsModel> getEarnings(String period) async {
    try {
      final res = await _client.get(ApiEndpoints.earnings, query: {'period': period});
      final data = (res.data['data'] ?? res.data) as Map;
      return EarningsModel.fromJson(data.cast<String, dynamic>());
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  Future<Map<String, dynamic>> getSummary() async {
    try {
      final res = await _client.get(ApiEndpoints.earningsSummary);
      final data = (res.data['data'] ?? res.data) as Map;
      return data.cast<String, dynamic>();
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  /// Average rating lives in /performance, not /earnings.
  Future<double> getAverageRating() async {
    try {
      final res = await _client.get(ApiEndpoints.performance);
      final data = (res.data['data'] ?? res.data) as Map;
      return double.tryParse('${data['average_rating'] ?? 0}') ?? 0;
    } catch (_) {
      return 0;
    }
  }

  Future<List<ChartData>> getChart(String period) async {
    try {
      final res = await _client.get(ApiEndpoints.earningsChart, query: {'period': period});
      final list = (res.data['data'] as List?) ?? const [];
      return list
          .map((e) => ChartData.fromJson((e as Map).cast<String, dynamic>()))
          .toList();
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }
}
