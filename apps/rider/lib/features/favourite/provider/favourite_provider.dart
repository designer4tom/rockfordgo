import 'package:flutter/material.dart';

import '../../../core/network/api_exception.dart';
import '../../location/model/place_model.dart';
import '../model/favourite_model.dart';
import '../repository/favourite_repository.dart';

class FavouriteProvider extends ChangeNotifier {
  final FavouriteRepository _repository;

  FavouriteProvider(this._repository);

  List<FavouriteModel> favourites = [];
  bool isLoading = false;
  String? error;

  Future<void> loadFavourites() async {
    isLoading = true;
    notifyListeners();
    try {
      favourites = await _repository.getFavourites();
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> addFavourite({
    required String label,
    String? customLabel,
    required PlaceModel place,
  }) async {
    try {
      final fav = await _repository.addFavourite(
        label: label,
        customLabel: customLabel,
        place: place,
      );
      favourites.add(fav);
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      error = e.message;
      notifyListeners();
      return false;
    }
  }

  Future<bool> deleteFavourite(int id) async {
    try {
      await _repository.deleteFavourite(id);
      favourites.removeWhere((f) => f.id == id);
      notifyListeners();
      return true;
    } on ApiException catch (e) {
      error = e.message;
      notifyListeners();
      return false;
    }
  }
}
