import '../../location/model/place_model.dart';
import '../model/favourite_model.dart';

abstract class FavouriteRepository {
  Future<List<FavouriteModel>> getFavourites();

  Future<FavouriteModel> addFavourite({
    required String label,
    String? customLabel,
    required PlaceModel place,
  });

  Future<void> deleteFavourite(int id);
}
