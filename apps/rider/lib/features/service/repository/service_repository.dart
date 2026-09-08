import '../model/service_model.dart';
import '../model/vehicle_category_model.dart';

abstract class ServiceRepository {
  Future<List<ServiceModel>> getServices();

  Future<List<VehicleCategoryModel>> getVehicleCategories(
    double lat,
    double lng,
  );
}
