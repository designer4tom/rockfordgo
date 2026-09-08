import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

/// Handles Firebase Cloud Messaging + local notification display.
///
/// Order-request notifications use a HIGH priority channel so the driver
/// never misses a new ride/parcel request.
class FcmService {
  FcmService._();
  static final FcmService instance = FcmService._();

  final FirebaseMessaging _messaging = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _local =
      FlutterLocalNotificationsPlugin();

  static const AndroidNotificationChannel orderChannel =
      AndroidNotificationChannel(
    'order_requests',
    'Order Requests',
    description: 'New ride and parcel requests',
    importance: Importance.max,
    playSound: true,
  );

  // Also the manifest's `default_notification_channel_id` target (see
  // AndroidManifest.xml) — background/killed FCM messages with a
  // `notification` block are shown by the OS through this exact channel.
  // Importance.low/defaultImportance means no heads-up banner and no sound,
  // which reads as "notification never arrived" even though it did.
  static const AndroidNotificationChannel generalChannel =
      AndroidNotificationChannel(
    'general',
    'General',
    description: 'General notifications',
    importance: Importance.high,
    playSound: true,
  );

  Future<void> init() async {
    // Permission (iOS + Android 13+). If this was denied once, iOS/Android
    // won't prompt again — the driver has to enable it manually from
    // Settings → Apps → this app → Notifications (uninstall+reinstall also
    // resets it for re-testing).
    final settings =
        await _messaging.requestPermission(alert: true, badge: true, sound: true);
    debugPrint('FCM permission: ${settings.authorizationStatus}');

    // Local notifications init
    const androidInit = AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosInit = DarwinInitializationSettings();
    await _local.initialize(
      settings:
          const InitializationSettings(android: androidInit, iOS: iosInit),
    );

    // Register Android channels
    final androidPlugin =
        _local.resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>();
    await androidPlugin?.createNotificationChannel(orderChannel);
    await androidPlugin?.createNotificationChannel(generalChannel);

    // Show heads-up notification for foreground messages
    FirebaseMessaging.onMessage.listen(_showLocal);
  }

  Future<String?> getToken() => _messaging.getToken();

  /// FCM tokens rotate (app reinstall, Play Services update, OS restore, …).
  /// Without re-pushing the new one to the backend, every push — order
  /// requests included, not just chat — silently stops reaching this device.
  void onTokenRefresh(void Function(String) handler) =>
      _messaging.onTokenRefresh.listen(handler);

  /// Message that cold-launched the app from a terminated state (set in main()
  /// before any screen mounts, so HomeScreen can drain it whenever it's ready).
  static RemoteMessage? initialMessage;

  void onMessage(Function(RemoteMessage) handler) =>
      FirebaseMessaging.onMessage.listen(handler);

  void onMessageOpenedApp(Function(RemoteMessage) handler) =>
      FirebaseMessaging.onMessageOpenedApp.listen((m) {
        debugPrint('FCMTAP onMessageOpenedApp: ${m.data}');
        handler(m);
      });

  Future<RemoteMessage?> getInitialMessage() => _messaging.getInitialMessage();

  void _showLocal(RemoteMessage message) {
    final type = message.data['type']?.toString() ?? '';
    final isOrder = type == 'order_request' || type == 'new_order';

    // Foreground order requests are delivered instantly by Pusher; suppress the
    // duplicate FCM notification here. FCM remains the backup for
    // background/killed states (handled by the OS, not this callback).
    if (isOrder) return;

    var title = message.notification?.title;
    var body = message.notification?.body;

    // Chat pushes are data-only (see test/CHAT_API.md §4) — the backend
    // doesn't attach a `notification` block, it expects the client to build
    // one from `sender_name` / `body` in `data`. Without this, a foreground
    // driver never sees an incoming message.
    if (title == null && body == null && type == 'chat') {
      title = message.data['sender_name']?.toString() ?? 'New message';
      body = message.data['body']?.toString() ?? '';
    }
    if (title == null && body == null) return; // nothing to show

    const channel = generalChannel;

    _local.show(
      id: ('$title$body').hashCode,
      title: title,
      body: body,
      notificationDetails: NotificationDetails(
        android: AndroidNotificationDetails(
          channel.id,
          channel.name,
          channelDescription: channel.description,
          importance: channel.importance,
          priority: Priority.defaultPriority,
          playSound: true,
        ),
        iOS: const DarwinNotificationDetails(),
      ),
      payload: message.data.isNotEmpty ? message.data.toString() : null,
    );
  }

  /// Top-level background handler (registered in main.dart).
  ///
  /// Runs in its own isolate with no existing Firebase context — MUST
  /// re-initialize Firebase itself before touching anything Firebase-related,
  /// or the plugin throws internally (seen as
  /// "type 'Null' is not a subtype of type 'String'" from
  /// FlutterFire Messaging, which silently aborts this handler on every
  /// background push, chat included).
  @pragma('vm:entry-point')
  static Future<void> backgroundHandler(RemoteMessage message) async {
    await Firebase.initializeApp();
    debugPrint('FCM background message: ${message.messageId}');

    // Messages with a `notification` block are shown by the OS automatically.
    // Chat pushes are data-only (see test/CHAT_API.md §4), so without this the
    // driver never sees an incoming message while the app is backgrounded or
    // killed — the OS has nothing to render on its own.
    if (message.notification != null) return;

    final type = message.data['type']?.toString() ?? '';
    if (type != 'chat') return;

    final title = message.data['sender_name']?.toString() ?? 'New message';
    final body = message.data['body']?.toString() ?? '';
    if (body.isEmpty) return;

    final local = FlutterLocalNotificationsPlugin();
    const androidInit = AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosInit = DarwinInitializationSettings();
    await local.initialize(
      settings: const InitializationSettings(android: androidInit, iOS: iosInit),
    );
    final androidPlugin = local.resolvePlatformSpecificImplementation<
        AndroidFlutterLocalNotificationsPlugin>();
    await androidPlugin?.createNotificationChannel(generalChannel);

    await local.show(
      id: ('$title$body').hashCode,
      title: title,
      body: body,
      notificationDetails: NotificationDetails(
        android: AndroidNotificationDetails(
          generalChannel.id,
          generalChannel.name,
          channelDescription: generalChannel.description,
          importance: generalChannel.importance,
          priority: Priority.defaultPriority,
          playSound: true,
        ),
        iOS: const DarwinNotificationDetails(),
      ),
      payload: message.data.isNotEmpty ? message.data.toString() : null,
    );
  }
}
