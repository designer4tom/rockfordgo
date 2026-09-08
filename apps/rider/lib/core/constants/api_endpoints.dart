class ApiEndpoints {
  // Config
  static const String config = '/config';
  static const String banners = '/banners';
  static const String faqs = '/faqs';
  static const String safetyTips = '/safety-tips';
  static String page(String slug) => '/pages/$slug';
  static const String cancellationReasons = '/cancellation-reasons';

  // Auth
  static const String sendOtp = '/auth/send-otp';
  static const String verifyOtp = '/auth/verify-otp';
  static const String completeProfile = '/auth/complete-profile';
  static const String logout = '/auth/logout';

  // Profile
  static const String profile = '/user/profile';
  static const String updateFcmToken = '/user/update-fcm-token';
  static const String deleteAccount = '/user/account';

  // Home
  static const String services = '/services';
  static const String vehicleCategories = '/ride/vehicle-categories';
  static const String nearbyDrivers = '/nearby-drivers';

  // Ride
  static const String rideFareEstimate = '/ride/fare-estimate';
  static const String rideBook = '/ride/book';
  static String rideStatus(int id) => '/ride/$id/status';
  static String rideCancel(int id) => '/ride/$id/cancel';
  static String rideTip(int id) => '/ride/$id/tip';
  static String rideRate(int id) => '/ride/$id/rate';
  static String rideShareLink(int id) => '/ride/$id/generate-share-link';

  // Parcel
  static const String parcelEstimate = '/parcel/estimate';
  static const String parcelBook = '/parcel/book';
  static String parcelStatus(int id) => '/parcel/$id/status';
  static String parcelCancel(int id) => '/parcel/$id/cancel';
  static String parcelRate(int id) => '/parcel/$id/rate';
  static String parcelPayAfter(int id) => '/parcel/$id/pay-after-delivery';

  // Coupon
  static const String couponValidate = '/coupon/validate';
  static const String coupons = '/coupons';

  // Wallet
  static const String wallet = '/user/wallet';
  static const String walletTransactions = '/user/wallet/transactions';
  static const String walletDues = '/user/wallet/dues';
  static const String walletPaymentMethods = '/user/wallet/payment-methods';
  static const String walletAddMoneyInitiate = '/user/wallet/add-money/initiate';
  static const String userWithdrawalRequest = '/user/wallet/withdrawal/request';
  static const String userWithdrawalHistory = '/user/wallet/withdrawal/history';
  static const String withdrawalMethods = '/withdrawal-methods';

  // Orders
  static const String activeOrder = '/user/active-order';
  static const String orders = '/user/orders';
  static String orderDetail(int id) => '/user/orders/$id';
  static String invoice(int id) => '/orders/$id/invoice';
  static const String scheduledOrders = '/user/scheduled-orders';

  // Favourite Locations
  static const String favouriteLocations = '/user/favourite-locations';

  // Notifications
  static const String notifications = '/user/notifications';
  static const String notificationsMarkRead = '/user/notifications/mark-read';

  // SOS & Emergency
  static const String sos = '/user/sos';
  static const String emergencyContact = '/user/emergency-contact';

  // Referral
  static const String referral = '/user/referral';

  // Complaints
  static const String complaints = '/user/complaints';

  // Geocode
  static const String geocodeSearch = '/geocode/search';
  static const String geocodePlace = '/geocode/place';
  static const String geocodeReverse = '/geocode/reverse';

  // Chat (customer side — driver app uses the /driver/chat/... prefix)
  static String chatByOrder(int orderId) => '/chat/order/$orderId';
  static String chatMessages(int conversationId) =>
      '/chat/$conversationId/messages';
  static String chatSend(int conversationId) => '/chat/$conversationId/send';
  static String chatRead(int conversationId) => '/chat/$conversationId/read';
  static const String chatConversations = '/chat/conversations';
  static const String chatUnreadCount = '/chat/unread-count';
  static const String chatHeartbeat = '/chat/heartbeat';
  static const String chatOffline = '/chat/offline';
}
