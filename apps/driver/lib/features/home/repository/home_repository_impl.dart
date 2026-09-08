import '../../../core/constants/api_endpoints.dart';
import '../../../core/network/dio_client.dart';
import '../../auth/model/driver_model.dart';
import '../model/document_alert.dart';
import 'home_repository.dart';

class HomeRepositoryImpl implements HomeRepository {
  final DioClient _client;
  HomeRepositoryImpl({DioClient? client})
      : _client = client ?? DioClient.instance;

  @override
  Future<HomeData> loadHomeData() async {
    try {
      final res = await _client.get(ApiEndpoints.profile);
      final data = (res.data['data'] ?? res.data) as Map;

      final driverJson = (data['driver'] ?? data) as Map;
      final driver =
          DriverModel.fromJson(driverJson.cast<String, dynamic>());

      final summary = (data['today'] ?? data['summary']);
      final docs = (data['expiring_documents'] as List?) ?? const [];

      final due = double.tryParse(driver.dueAmount) ?? 0;
      final dueLimit = double.tryParse(
              (data['due_limit'] ?? data['max_due'] ?? '0').toString()) ??
          0;

      return HomeData(
        driver: driver,
        todayEarning: (summary is Map
                ? (summary['earning'] ?? summary['total'] ?? '0')
                : '0')
            .toString(),
        todayTrips: summary is Map
            ? (summary['trips'] is int
                ? summary['trips']
                : int.tryParse(summary['trips']?.toString() ?? '') ?? 0)
            : 0,
        dueAmount: driver.dueAmount,
        dueExceeded: dueLimit > 0 && due >= dueLimit,
        dueLimit: dueLimit,
        expiringDocs: docs
            .map((e) =>
                DocumentAlert.fromJson((e as Map).cast<String, dynamic>()))
            .toList(),
        hasActiveOrder: data['has_active_order'] == true ||
            data['active_order'] != null,
      );
    } catch (e) {
      throw DioClient.toApiException(e);
    }
  }

  @override
  Future<ToggleOnlineResult> toggleOnline(
    bool online, {
    double? lat,
    double? lng,
  }) async {
    try {
      final res = await _client.post(
        ApiEndpoints.toggleOnline,
        // Only is_online + current location. Zone is resolved by the backend.
        data: {
          'is_online': online,
          'lat': ?lat,
          'lng': ?lng,
        },
      );
      final body = res.data as Map;
      final data = (body['data'] is Map ? body['data'] : body) as Map;
      return ToggleOnlineResult(
        success: body['success'] == true || body['status'] == true,
        isOnline: data['is_online'] == true || data['is_online'] == 1 || online,
        message: (body['message'] ?? '').toString(),
      );
    } catch (e) {
      final ex = DioClient.toApiException(e);
      // 422/403 may carry block reasons in `errors`.
      final reasons = <String>[];
      final errs = ex.errors;
      if (errs is Map) {
        for (final v in errs.values) {
          if (v is List) {
            reasons.addAll(v.map((x) => x.toString()));
          } else {
            reasons.add(v.toString());
          }
        }
      } else if (errs is List) {
        reasons.addAll(errs.map((x) => x.toString()));
      }
      return ToggleOnlineResult(
        success: false,
        isOnline: false,
        message: ex.message,
        blockReasons: reasons.isEmpty ? [ex.message] : reasons,
      );
    }
  }

  @override
  Future<void> updateLocation({
    required double lat,
    required double lng,
    double? heading,
    double? speed,
  }) async {
    try {
      await _client.post(
        ApiEndpoints.updateLocation,
        data: {
          'lat': lat,
          'lng': lng,
          'heading': ?heading,
          'speed': ?speed,
        },
      );
    } catch (_) {
      // Location pings are best-effort — swallow transient failures.
    }
  }

  @override
  Future<ActiveOrderRef?> activeOrder() async {
    try {
      final res = await _client.get(ApiEndpoints.activeOrder);
      var data = res.data['data'];
      if (data is List) data = data.isEmpty ? null : data.first;
      if (data is! Map) return null;
      final id = data['order_id'] ?? data['id'];
      final parsedId =
          id is int ? id : int.tryParse(id?.toString() ?? '') ?? 0;
      if (parsedId == 0) return null;
      return ActiveOrderRef(
        id: parsedId,
        type: (data['type'] ?? 'ride').toString(),
      );
    } catch (_) {
      return null;
    }
  }
}
