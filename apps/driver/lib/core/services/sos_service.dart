import '../constants/api_endpoints.dart';
import '../network/dio_client.dart';
import 'location_service.dart';

/// Fires an SOS alert to the backend with the driver's current location.
class SosService {
  SosService._();
  static final SosService instance = SosService._();

  final DioClient _client = DioClient.instance;

  Future<bool> triggerSos({int? orderId}) async {
    double? lat;
    double? lng;
    try {
      final pos = await LocationService.instance.getCurrentLocation();
      lat = pos.latitude;
      lng = pos.longitude;
    } catch (_) {
      // Send without coordinates if location is unavailable.
    }
    try {
      final res = await _client.post(ApiEndpoints.sos, data: {
        'order_id': ?orderId,
        'lat': ?lat,
        'lng': ?lng,
      });
      final body = res.data as Map;
      return body['success'] == true || body['status'] == true;
    } catch (_) {
      return false;
    }
  }
}
