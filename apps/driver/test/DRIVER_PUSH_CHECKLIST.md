# Driver app — chat push notification checklist

**Audience:** Flutter developer (driver app), or an AI agent working in the driver app repo.
**Symptom:** customer sends a chat message → the notification row appears in
`/driver/notifications`, but no push lands on the driver's device. The customer
app receives its chat pushes normally. Same Firebase project for both apps.

---

## 0. What the backend has already been verified to do

Please don't re-investigate these — each was measured against the running code,
not assumed. Re-checking them costs a day and finds nothing.

| Checked | Result |
|---|---|
| Driver login stores the token | ✅ `device_token` accepted as an alias for `fcm_token`; your exact 142-char token was stored byte-identical in both `drivers.fcm_token` and `device_tokens` |
| Column sizes | ✅ `text` and `varchar(512)` — no truncation |
| Notification row written | ✅ `type: "chat"` |
| Push target resolution | ✅ customer→driver resolves `driver#N`; driver→customer resolves `user#N`. Symmetric |
| Token lookup | ✅ returns the driver's token, so the push **is** attempted |
| FCM message shape | ✅ includes a real `notification` block (title + body) **and** a `data` block — not data-only |

So the server sends the driver the same kind of message it sends the customer.
Whatever differs, differs after the send.

---

## 1. First, get the verdict from the server

Ask the backend developer to run this on the live server:

```bash
php artisan push:test --driver=<driver_id> --user=<customer_id>
```

It sends an identical push to both and prints FCM's raw answer. **Everything
below depends on which branch you land in:**

| Output for the driver | Meaning | Whose fix |
|---|---|---|
| `NO TOKEN STORED` | The app never registered a token | §2 |
| `rejected: Unregistered` | The token is dead — the app stopped re-registering | §2 |
| `rejected: SenderIdMismatch` | Driver app built against a different Firebase project | §3 |
| `1 accepted` but phone shows nothing | **Transport is fine — it is the app** | §4, §5, §6 |

If you get `1 accepted`, stop looking at the backend. FCM has taken delivery.

---

## 2. Token registration

Send the token on **every** login, and again whenever Firebase rotates it.
Login-only registration goes stale on reinstall, app update, and clear-data —
and a stale token fails silently forever.

```dart
final token = await FirebaseMessaging.instance.getToken();

// on login
await api.post('/driver/auth/verify-otp', data: {
  'phone': phone,
  'otp': otp,
  'device_token': token,     // or fcm_token — both accepted
  'platform': 'android',     // or 'ios'
});

// and register the refresh listener ONCE at startup
FirebaseMessaging.instance.onTokenRefresh.listen((newToken) {
  api.post('/driver/update-fcm-token', data: {
    'device_token': newToken,
    'platform': Platform.isIOS ? 'ios' : 'android',
  });
});
```

> `POST /api/v1/driver/update-fcm-token` already exists. If you only call it at
> login, that is the bug.

**Verify:** log out, log in, and confirm with the backend developer that
`drivers.fcm_token` changed. If it didn't, the field isn't reaching the server.

---

## 3. Firebase project

Only relevant if you saw `SenderIdMismatch`.

```bash
grep project_id android/app/google-services.json
```

Must equal the `server project_id` printed by `push:test`. If the driver app
was ever pointed at a second Firebase project — even briefly — every token it
has issued is invalid for this server.

---

## 4. Notification permission — the most likely cause

This is the classic reason two apps sharing one Firebase project behave
differently. **On Android 13+ (API 33), notifications are a runtime permission.**
If the driver app never requests it, FCM reports success and Android silently
drops every notification. Nothing appears in any log.

```xml
<!-- android/app/src/main/AndroidManifest.xml -->
<uses-permission android:name="android.permission.POST_NOTIFICATIONS"/>
```

```dart
// must run at startup, and the user must actually accept it
final settings = await FirebaseMessaging.instance.requestPermission();
debugPrint('permission: ${settings.authorizationStatus}');   // expect: authorized
```

**Verify on the device — do this before anything else:**
Settings → Apps → *driver app* → Notifications → must be **ON**.
Compare with the customer app on the same phone. If the customer app is on and
the driver app is off, that is your entire bug.

> If permission was denied once, `requestPermission()` will not prompt again.
> Uninstall and reinstall to re-test.

---

## 5. Foreground messages are NOT displayed automatically

If the driver is looking at the app when the message arrives, Android and iOS
do **not** draw the notification for you, even though the payload contains a
`notification` block. You must render it.

```dart
FirebaseMessaging.onMessage.listen((RemoteMessage message) {
  // Fires only in the foreground. If this is empty, chat messages will appear
  // to "not arrive" whenever the driver has the app open.
  flutterLocalNotificationsPlugin.show(
    message.hashCode,
    message.notification?.title,
    message.notification?.body,
    notificationDetails,
  );
});
```

**Test both states separately** — background (press home) and foreground (app
open). They fail independently, and "no notification" means different things in
each.

---

## 6. Notification channel (Android 8+)

Without a matching channel, Android drops the notification silently.

```dart
const channel = AndroidNotificationChannel(
  'high_importance_channel',
  'Chat & ride alerts',
  importance: Importance.high,   // Importance.low = no sound, no heads-up banner
);
await flutterLocalNotificationsPlugin
    .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
    ?.createNotificationChannel(channel);
```

```xml
<meta-data
  android:name="com.google.firebase.messaging.default_notification_channel_id"
  android:value="high_importance_channel"/>
```

The `android:value` must match the channel id you create. A mismatch is silent.

> **Note:** a channel's importance is fixed when it is first created. Changing
> it in code afterwards does nothing until the app is uninstalled and
> reinstalled. If you ever shipped this channel at low importance, test on a
> fresh install.

---

## 7. Background handler must be a top-level function

```dart
@pragma('vm:entry-point')          // required, or it is tree-shaken in release
Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
}

void main() {
  FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);
}
```

Missing `@pragma('vm:entry-point')` is a classic "works in debug, silently fails
in release" bug. **Test on a release build**, not just debug.

---

## 8. iOS only

- Upload the **APNs auth key** to Firebase Console → Project Settings → Cloud Messaging.
- Enable Push Notifications + Background Modes → Remote notifications in Xcode.
- iOS Simulator cannot receive push. Use a real device.

---

## 9. What the payload looks like

The chat push you are handling:

```json
{
  "notification": { "title": "Rahim Uddin", "body": "I'm at the gate" },
  "data": {
    "type": "chat",
    "conversation_id": "21",
    "order_id": "48"
  }
}
```

- `type` is `"chat"` — if your handler switches on `type` and only knows
  `order_request`, add a `chat` case, or it will be swallowed.
- Tapping it should open the thread using `conversation_id` (**not**
  `order_id` — see below).

---

## 10. While you are here — the realtime 403

Separate from push, and it requires a one-line app change. The Pusher channel
is built from the **conversation id**, never the order id. They are different
numbers (order `48` → conversation `21`). Subscribing with `order_id` is a
channel this driver is not a participant of, so authorisation returns
`403 AccessDeniedHttpException`.

```dart
// ❌ 403
pusher.subscribe(channelName: 'private-conversation.${conversation['order_id']}');

// ✅ use the field the backend now ships
pusher.subscribe(channelName: conversation['channel']);
```

---

## Fastest path

1. Phone Settings → driver app → Notifications **ON**? (§4) — most common cause
2. Backend runs `push:test`. `accepted` → app-side; `rejected` → §2/§3
3. Test background **and** foreground separately (§5)
4. Test a **release** build (§7)
