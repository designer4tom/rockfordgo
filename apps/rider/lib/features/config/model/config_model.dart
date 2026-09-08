import '../../../core/constants/app_constants.dart';

/// App-wide config fetched from `GET /config`. Keys (Pusher, Maps, feature
/// flags) come from here rather than being hard-coded.
class ConfigModel {
  final String appName;
  final String currency;
  final String currencySymbol;
  final String currencyPosition; // before / after
  final String supportEmail;
  final String supportPhone;
  final List<double> topupQuickAmounts;
  final double minRechargeAmount;
  final double maxRechargeAmount;
  final bool rideShareEnabled;
  final bool surgeEnabled;
  final bool scheduledBookingEnabled;
  final int maxScheduleDays;
  final bool codEnabled;
  final bool tipEnabled;
  final List<double> tipAmounts;
  final bool referralEnabled;
  final double searchRadiusKm;
  final int requestTimeoutSeconds;
  final String googleMapsKey;
  final String pusherKey;
  final String pusherCluster;
  final String phoneCode;
  final String countryFlag;
  final int phoneMinLength;
  final int phoneMaxLength;
  final String phoneRegex; // optional; overrides length check when set
  final double userWithdrawalMinimum;

  ConfigModel({
    this.appName = 'ReadyRide',
    this.currency = 'BDT',
    this.currencySymbol = '৳',
    this.currencyPosition = 'before',
    this.supportEmail = '',
    this.supportPhone = '',
    this.topupQuickAmounts = const [100, 200, 500, 1000],
    this.minRechargeAmount = 50,
    this.maxRechargeAmount = 10000,
    this.rideShareEnabled = false,
    this.surgeEnabled = false,
    this.scheduledBookingEnabled = true,
    this.maxScheduleDays = 7,
    this.codEnabled = true,
    this.tipEnabled = true,
    this.tipAmounts = const [10, 20, 50, 100],
    this.referralEnabled = false,
    this.searchRadiusKm = 5,
    this.requestTimeoutSeconds = 30,
    this.googleMapsKey = '',
    this.pusherKey = '',
    this.pusherCluster = 'mt1',
    this.phoneCode = '+880',
    this.countryFlag = '🇧🇩',
    this.phoneMinLength = 6,
    this.phoneMaxLength = 15,
    this.phoneRegex = '',
    this.userWithdrawalMinimum = 50,
  });

  /// The flag rendered as an emoji. Accepts either an emoji directly or a
  /// 2-letter ISO country code (e.g. "BD") and converts it.
  String get flagEmoji {
    final f = countryFlag.trim();
    if (f.isEmpty) return '🇧🇩';
    final code = f.toUpperCase();
    final isIsoCode = code.length == 2 &&
        code.codeUnits.every((c) => c >= 65 && c <= 90); // A–Z
    if (isIsoCode) {
      return String.fromCharCodes(
        code.codeUnits.map((c) => 0x1F1E6 + (c - 65)),
      );
    }
    return f; // already an emoji / display string
  }

  factory ConfigModel.fromJson(Map<String, dynamic> json) {
    return ConfigModel(
      appName: json['app_name'] ?? 'ReadyRide',
      currency: json['currency'] ?? 'BDT',
      currencySymbol: json['currency_symbol'] ?? '৳',
      currencyPosition: json['currency_position']?.toString() ?? 'before',
      supportEmail: json['support_email']?.toString() ?? '',
      supportPhone: json['support_phone']?.toString() ?? '',
      topupQuickAmounts: (json['topup_quick_amounts'] as List?)
              ?.map((e) => (e as num).toDouble())
              .toList() ??
          const [100, 200, 500, 1000],
      minRechargeAmount:
          double.tryParse(json['min_recharge_amount']?.toString() ?? '') ?? 50,
      maxRechargeAmount:
          double.tryParse(json['max_recharge_amount']?.toString() ?? '') ??
              10000,
      rideShareEnabled: json['ride_share_enabled'] ?? false,
      surgeEnabled: json['surge_enabled'] ?? false,
      scheduledBookingEnabled: json['scheduled_booking_enabled'] ?? true,
      maxScheduleDays: json['max_schedule_days'] ?? 7,
      codEnabled: json['cod_enabled'] ?? true,
      tipEnabled: json['tip_enabled'] ?? true,
      tipAmounts: (json['tip_amounts'] as List?)
              ?.map((e) => (e as num).toDouble())
              .toList() ??
          const [10, 20, 50, 100],
      referralEnabled: json['referral_enabled'] ?? false,
      searchRadiusKm: (json['search_radius_km'] as num?)?.toDouble() ?? 5,
      requestTimeoutSeconds: json['request_timeout_seconds'] ?? 30,
      googleMapsKey: json['google_maps_key'] ?? '',
      pusherKey: json['pusher_key'] ?? '',
      pusherCluster: json['pusher_cluster'] ?? 'mt1',
      phoneCode: json['phone_code']?.toString() ?? '+880',
      countryFlag: json['country_flag']?.toString() ?? '🇧🇩',
      phoneMinLength: (json['phone_min_length'] as num?)?.toInt() ?? 6,
      phoneMaxLength: (json['phone_max_length'] as num?)?.toInt() ?? 15,
      phoneRegex: json['phone_regex']?.toString() ?? '',
      userWithdrawalMinimum: double.tryParse(
              (json['min_withdrawal_amount'] ?? json['user_withdrawal_minimum'])
                      ?.toString() ??
                  '') ??
          50,
    );
  }

  /// Validates a digits-only phone string against the config rules.
  /// Returns true when valid.
  bool isValidPhone(String digits) {
    if (phoneRegex.isNotEmpty) {
      try {
        return RegExp(phoneRegex).hasMatch(digits);
      } catch (_) {
        // Bad regex from config — fall back to length check.
      }
    }
    return digits.length >= phoneMinLength && digits.length <= phoneMaxLength;
  }

  /// Used when /config can't be reached — falls back to compile-time keys.
  factory ConfigModel.fallback() => ConfigModel(
        pusherKey: AppConstants.pusherKey,
        pusherCluster: AppConstants.pusherCluster,
        googleMapsKey: AppConstants.googleMapsApiKey,
      );
}
