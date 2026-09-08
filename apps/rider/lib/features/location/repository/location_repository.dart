import '../model/place_model.dart';

abstract class LocationRepository {
  /// Search places via the backend geocode proxy.
  Future<List<PlaceModel>> searchPlaces(
    String query, {
    double? lat,
    double? lng,
  });

  /// Resolve a search prediction's coordinates from its place_id.
  Future<PlaceModel> placeDetails(String placeId);

  /// Reverse geocode a coordinate into an address.
  Future<PlaceModel> reverseGeocode(double lat, double lng);
}
