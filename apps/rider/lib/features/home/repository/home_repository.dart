import '../model/driver_marker_model.dart';

abstract class HomeRepository {
  Future<List<DriverMarker>> getNearbyDrivers(
    double lat,
    double lng, {
    String? serviceType,
  });
}
