import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/dio_client.dart';
import '../model/coupon_offer_model.dart';
import 'coupon_repository.dart';

class CouponRepositoryImpl implements CouponRepository {
  final DioClient _client;

  CouponRepositoryImpl(this._client);

  @override
  Future<List<CouponOfferModel>> getCoupons({String? serviceType}) async {
    try {
      final res = await _client.dio.get(
        ApiEndpoints.coupons,
        queryParameters:
            serviceType != null ? {'service_type': serviceType} : null,
      );
      return (res.data['data'] as List? ?? [])
          .map((e) => CouponOfferModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
