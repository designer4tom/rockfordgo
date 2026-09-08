import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

/// Background message handler must be a top-level function.
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  // When the app is in the background/terminated, messages that carry a
  // `notification` payload are shown by the OS automatically. Data-only
  // messages would need a local notification here, but that requires
  // re-initialising the plugin in this isolate — kept minimal for now.
}

/// Wraps FCM + local notifications so push messages actually appear, including
/// while the app is in the foreground (Android does not show those by default).
class FcmService {
  // Singleton: the instance that listens to FCM is the same one whose
  // callbacks (e.g. [onForegroundMessage]) are wired up elsewhere.
  static final FcmService _instance = FcmService._internal();
  factory FcmService() => _instance;
  FcmService._internal();

  static final FlutterLocalNotificationsPlugin _local =
      FlutterLocalNotificationsPlugin();

  /// High-importance channel so notifications show as a heads-up banner.
  static const AndroidNotificationChannel _channel = AndroidNotificationChannel(
    'readyride_default',
    'ReadyRide Notifications',
    description: 'Ride, parcel and account updates',
    importance: Importance.high,
  );

  void Function(Map<String, dynamic> data)? onNotificationTap;
  VoidCallback? onForegroundMessage;

  Future<void> init() async {
    try {
      final messaging = FirebaseMessaging.instance;
      await messaging.requestPermission();

      // Show foreground heads-up notifications on Android (and iOS).
      await messaging.setForegroundNotificationPresentationOptions(
        alert: true,
        badge: true,
        sound: true,
      );

      const androidInit = AndroidInitializationSettings('@mipmap/ic_launcher');
      const iosInit = DarwinInitializationSettings();
      await _local.initialize(
        settings:
            const InitializationSettings(android: androidInit, iOS: iosInit),
        onDidReceiveNotificationResponse: (resp) {
          if (resp.payload != null) {
            onNotificationTap?.call({'payload': resp.payload!});
          }
        },
      );
      await _local
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>()
          ?.createNotificationChannel(_channel);

      FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
      FirebaseMessaging.onMessage.listen(_onMessage);
      FirebaseMessaging.onMessageOpenedApp.listen(_onMessageOpenedApp);

      // Debug: confirm a device token is generated.
      final token = await messaging.getToken();
      debugPrint('🔔 FCM token: $token');
    } catch (e) {
      debugPrint('🔔 FcmService init skipped: $e');
    }
  }

  Future<String?> getToken() async {
    try {
      final token = await FirebaseMessaging.instance.getToken();
      debugPrint('🔔 getToken(): $token');
      return token;
    } catch (e) {
      debugPrint('🔔 getToken() failed: $e');
      return null;
    }
  }

  void _onMessage(RemoteMessage message) {
    final n = message.notification;
    final data = message.data;
    // Support both `notification` payloads and data-only messages.
    final title = n?.title ?? data['title']?.toString();
    final body = n?.body ?? data['body']?.toString() ?? data['message']?.toString();

    if (title != null || body != null) {
      _local.show(
        id: message.hashCode,
        title: title,
        body: body,
        notificationDetails: NotificationDetails(
          android: AndroidNotificationDetails(
            _channel.id,
            _channel.name,
            channelDescription: _channel.description,
            importance: Importance.high,
            priority: Priority.high,
            icon: '@mipmap/ic_launcher',
          ),
          iOS: const DarwinNotificationDetails(),
        ),
      );
    }
    onForegroundMessage?.call();
  }

  void _onMessageOpenedApp(RemoteMessage message) {
    onNotificationTap?.call(message.data);
  }
}
