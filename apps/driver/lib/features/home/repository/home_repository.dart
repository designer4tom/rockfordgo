import '../../auth/model/driver_model.dart';
import '../model/document_alert.dart';

/// Result of attempting to go online.
class ToggleOnlineResult {
  final bool success;
  final bool isOnline;
  final String message;
  final List<String> blockReasons;

  ToggleOnlineResult({
    required this.success,
    required this.isOnline,
    required this.message,
    this.blockReasons = const [],
  });
}

/// Snapshot of the driver's home dashboard data.
class HomeData {
  final DriverModel? driver;
  final String todayEarning;
  final int todayTrips;
  final String dueAmount;
  final bool dueExceeded;
  final double dueLimit;
  final List<DocumentAlert> expiringDocs;
  final bool hasActiveOrder;

  HomeData({
    this.driver,
    this.todayEarning = '0.00',
    this.todayTrips = 0,
    this.dueAmount = '0.00',
    this.dueExceeded = false,
    this.dueLimit = 0,
    this.expiringDocs = const [],
    this.hasActiveOrder = false,
  });
}

/// Lightweight reference to a resumable active order.
class ActiveOrderRef {
  final int id;
  final String type; // ride | parcel
  ActiveOrderRef({required this.id, required this.type});

  bool get isParcel => type.toLowerCase() == 'parcel';
}

abstract class HomeRepository {
  Future<HomeData> loadHomeData();
  /// Toggle online/offline. When going online, send the driver's current
  /// location (lat/lng) — the backend resolves the zone automatically.
  Future<ToggleOnlineResult> toggleOnline(
    bool online, {
    double? lat,
    double? lng,
  });
  Future<void> updateLocation({
    required double lat,
    required double lng,
    double? heading,
    double? speed,
  });
  Future<ActiveOrderRef?> activeOrder();
}
