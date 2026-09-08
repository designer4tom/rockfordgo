/// App config fetched from `/config` (cached on splash, with offline fallback).
class ConfigModel {
  final String appName;
  final String currency;
  final String currencySymbol;
  final String currencyPosition; // before | after
  final String? supportEmail;
  final String? supportPhone;

  final String countryCode;
  final String countryFlag;
  final String phoneCode;
  final String phoneExample;
  final String googleMapsKey;
  final String pusherKey;
  final String pusherCluster;
  final String minRechargeAmount;
  final String minWithdrawalAmount;
  final int requestTimeoutSeconds;
  final int searchRadiusKm;
  final bool codEnabled;
  final bool tipEnabled;
  final bool proofOfDeliveryEnabled;

  ConfigModel({
    required this.appName,
    required this.currency,
    required this.currencySymbol,
    required this.currencyPosition,
    this.supportEmail,
    this.supportPhone,

    required this.countryCode,
    required this.countryFlag,
    required this.phoneCode,
    required this.phoneExample,
    required this.googleMapsKey,
    required this.pusherKey,
    required this.pusherCluster,
    required this.minRechargeAmount,
    required this.minWithdrawalAmount,
    required this.requestTimeoutSeconds,
    required this.searchRadiusKm,
    required this.codEnabled,
    required this.tipEnabled,
    required this.proofOfDeliveryEnabled,
  });

  factory ConfigModel.fromJson(Map<String, dynamic> json) {
    final d = (json['data'] is Map ? json['data'] : json) as Map;
    return ConfigModel(
      appName: (d['app_name'] ?? 'ReadyRide').toString(),
      currency: (d['currency'] ?? 'BDT').toString(),
      currencySymbol: (d['currency_symbol'] ?? '৳').toString(),
      currencyPosition: (d['currency_position'] ?? 'before').toString(),
      supportEmail: d['support_email']?.toString(),
      supportPhone: d['support_phone']?.toString(),

      countryCode: (d['country_code'] ?? 'BD').toString(),
      countryFlag: (d['country_flag'] ?? '🇧🇩').toString(),
      phoneCode: (d['phone_code'] ?? '+880').toString(),
      phoneExample: (d['phone_example'] ?? '01712345678').toString(),
      googleMapsKey: (d['google_maps_key'] ?? '').toString(),
      pusherKey: (d['pusher_key'] ?? '').toString(),
      pusherCluster: (d['pusher_cluster'] ?? 'mt1').toString(),
      minRechargeAmount: (d['min_recharge_amount'] ?? '50').toString(),
      minWithdrawalAmount: (d['min_withdrawal_amount'] ?? '100').toString(),
      requestTimeoutSeconds: _toInt(d['request_timeout_seconds'], 30),
      searchRadiusKm: _toInt(d['search_radius_km'], 5),
      codEnabled: d['cod_enabled'] != false,
      tipEnabled: d['tip_enabled'] != false,
      proofOfDeliveryEnabled: d['proof_of_delivery_enabled'] == true,
    );
  }

  static int _toInt(dynamic v, int fallback) =>
      v is int ? v : int.tryParse(v?.toString() ?? '') ?? fallback;
}
