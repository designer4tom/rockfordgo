import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/dio_client.dart';
import '../model/page_model.dart';

class PageRepository {
  final DioClient _client;
  PageRepository({DioClient? client}) : _client = client ?? DioClient.instance;

  /// CMS page by slug — driver-specific content via `app_type: driver`.
  Future<PageModel> getPage(String slug) async {
    try {
      final res = await _client.get(
        ApiEndpoints.page(slug),
        query: {'app_type': 'driver'},
      );
      return PageModel.fromJson((res.data as Map).cast<String, dynamic>());
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }
}
