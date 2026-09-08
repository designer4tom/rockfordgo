import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/dio_client.dart';
import '../model/referral_model.dart';
import 'referral_repository.dart';

class ReferralRepositoryImpl implements ReferralRepository {
  final DioClient _client;

  ReferralRepositoryImpl(this._client);

  @override
  Future<ReferralModel> getReferral() async {
    try {
      final res = await _client.dio.get(ApiEndpoints.referral);
      return ReferralModel.fromJson(Map<String, dynamic>.from(res.data['data']));
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
