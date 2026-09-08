import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/dio_client.dart';
import '../model/banner_model.dart';
import 'banner_repository.dart';

class BannerRepositoryImpl implements BannerRepository {
  final DioClient _client;

  BannerRepositoryImpl(this._client);

  @override
  Future<List<BannerModel>> getBanners() async {
    try {
      final res = await _client.dio.get(ApiEndpoints.banners);
      return (res.data['data'] as List? ?? [])
          .map((e) => BannerModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
