import 'package:flutter/material.dart';

import '../../../core/network/api_exception.dart';
import '../model/banner_model.dart';
import '../repository/banner_repository.dart';

class BannerProvider extends ChangeNotifier {
  final BannerRepository _repository;

  BannerProvider(this._repository);

  List<BannerModel> banners = [];
  bool isLoading = false;
  String? error;

  Future<void> loadBanners() async {
    if (banners.isNotEmpty) return; // already loaded
    isLoading = true;
    notifyListeners();
    try {
      banners = await _repository.getBanners();
    } on ApiException catch (e) {
      error = e.message;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }
}
