class ApiEndpoints {
  // Config
  static const String config = '/config';
  static const String faqs = '/faqs';

  // CMS pages (privacy-policy, terms-conditions, …) — app_type: driver
  static String page(String slug) => '/pages/$slug';

  // Driver Auth
  static const String sendOtp = '/driver/auth/send-otp';
  static const String verifyOtp = '/driver/auth/verify-otp';
  static const String register = '/driver/auth/register';
  static const String authStatus = '/driver/auth/status';
  static const String logout = '/driver/auth/logout';

  // Profile
  static const String profile = '/driver/profile';
  static const String updateFcmToken = '/driver/update-fcm-token';
  static const String updateDocument = '/driver/documents/update';
  static const String deleteAccount = '/driver/account';
  static const String emergencyContact = '/driver/emergency-contact';

  // Online & Location
  static const String toggleOnline = '/driver/toggle-online';
  static const String updateLocation = '/driver/update-location';
  static const String activeOrder = '/driver/active-order';

  // Ride
  static const String rideRespond = '/driver/ride/respond';
  static const String rideUpdateStatus = '/driver/ride/update-status';
  static const String rideCollectProof = '/driver/ride/collect-proof';
  static String rideRate(int id) => '/driver/ride/$id/rate';

  // Parcel
  static const String parcelUpdateStatus = '/driver/parcel/update-status';
  // Note: parcel proof is submitted together with parcelComplete — the backend
  // has no separate parcel proof endpoint (unlike rideCollectProof).
  static const String parcelCollectCod = '/driver/parcel/collect-cod';
  static const String parcelComplete = '/driver/parcel/complete';

  // Wallet
  static const String wallet = '/driver/wallet';
  static const String walletTransactions = '/driver/wallet/transactions';
  static const String withdrawalRequest = '/driver/wallet/withdrawal/request';
  static const String withdrawalHistory = '/driver/wallet/withdrawal/history';
  static const String withdrawalMethods = '/withdrawal-methods';

  // Recharge (wallet top-up + due clear)
  static const String rechargeMethods = '/driver/recharge/payment-methods';
  static const String rechargeInitiate = '/driver/recharge/initiate';
  static const String rechargeConfirm = '/driver/recharge/confirm';
  static const String rechargeHistory = '/driver/recharge/history';

  // Earnings
  static const String earnings = '/driver/earnings';
  static const String earningsSummary = '/driver/earnings/summary';
  static const String earningsChart = '/driver/earnings/chart';

  // Performance & Shifts
  static const String performance = '/driver/performance';
  static const String shifts = '/driver/shifts';

  // Orders
  static const String orders = '/driver/orders';
  static String orderDetail(int id) => '/driver/orders/$id';

  // Notifications
  static const String notifications = '/driver/notifications';
  static const String notificationsMarkRead = '/driver/notifications/mark-read';

  // Chat (driver side — customer app uses the /chat/... prefix instead)
  static String chatByOrder(int orderId) => '/driver/chat/order/$orderId';
  static String chatMessages(int conversationId) =>
      '/driver/chat/$conversationId/messages';
  static String chatSend(int conversationId) =>
      '/driver/chat/$conversationId/send';
  static String chatRead(int conversationId) =>
      '/driver/chat/$conversationId/read';
  static const String chatConversations = '/driver/chat/conversations';
  static const String chatUnreadCount = '/driver/chat/unread-count';
  static const String chatHeartbeat = '/driver/chat/heartbeat';
  static const String chatOffline = '/driver/chat/offline';

  // SOS
  static const String sos = '/driver/sos';

  // Complaints
  static const String complaints = '/driver/complaints';

  // Vehicle Categories (for registration — location-free driver endpoint)
  static const String vehicleCategories = '/driver/vehicle-categories';
}
