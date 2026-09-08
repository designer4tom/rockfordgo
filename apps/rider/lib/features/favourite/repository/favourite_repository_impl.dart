import 'package:dio/dio.dart';

import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/network/dio_client.dart';
import '../../location/model/place_model.dart';
import '../model/favourite_model.dart';
import 'favourite_repository.dart';

class FavouriteRepositoryImpl implements FavouriteRepository {
  final DioClient _client;

  FavouriteRepositoryImpl(this._client);

  @override
  Future<List<FavouriteModel>> getFavourites() async {
    try {
      final res = await _client.dio.get(ApiEndpoints.favouriteLocations);
      return (res.data['data'] as List? ?? [])
          .map((e) => FavouriteModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<FavouriteModel> addFavourite({
    required String label,
    String? customLabel,
    required PlaceModel place,
  }) async {
    try {
      final res = await _client.dio.post(
        ApiEndpoints.favouriteLocations,
        data: {
          'label': label,
          'custom_label': ?customLabel,
          'address': place.address,
          'lat': place.lat,
          'lng': place.lng,
        },
      );
      return FavouriteModel.fromJson(
        Map<String, dynamic>.from(res.data['data']),
      );
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }

  @override
  Future<void> deleteFavourite(int id) async {
    try {
      await _client.dio.delete('${ApiEndpoints.favouriteLocations}/$id');
    } on DioException catch (e) {
      throw ApiException.fromDioError(e);
    }
  }
}
