import 'dart:io';

import '../model/active_parcel_model.dart';

abstract class ParcelOrderRepository {
  Future<ActiveParcelModel?> loadActiveOrder(int orderId);
  Future<bool> updateStatus(int orderId, String status);

  Future<bool> collectCod(int orderId, double amount);

  /// Completes the delivery AND submits proof in one call — the backend bundles
  /// proof into /driver/parcel/complete (there is no separate proof endpoint).
  Future<bool> complete({
    required int orderId,
    String? proofType, // otp | photo | signature
    String? otp,
    File? photo,
    String? signature, // base64
  });
}
