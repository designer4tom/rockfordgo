import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/dio_client.dart';
import '../model/faq_model.dart';
import 'help_repository.dart';

class HelpRepositoryImpl implements HelpRepository {
  final DioClient _client;

  HelpRepositoryImpl(this._client);

  @override
  Future<List<FaqModel>> getFaqs() async {
    try {
      final res = await _client.dio.get(ApiEndpoints.faqs);
      return (res.data['data'] as List? ?? [])
          .map((e) => FaqModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<List<SafetyTipModel>> getSafetyTips() async {
    try {
      final res = await _client.dio.get(ApiEndpoints.safetyTips);
      return (res.data['data'] as List? ?? [])
          .map((e) => SafetyTipModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<PageModel> getPage(String slug) async {
    try {
      final res = await _client.dio.get(
        ApiEndpoints.page(slug),
        queryParameters: {'app_type': 'customer'},
      );
      return PageModel.fromJson(Map<String, dynamic>.from(res.data['data']));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
