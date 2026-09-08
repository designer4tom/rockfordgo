import '../model/banner_model.dart';

abstract class BannerRepository {
  Future<List<BannerModel>> getBanners();
}
