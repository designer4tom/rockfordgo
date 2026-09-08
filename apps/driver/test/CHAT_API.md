# Ride & Parcel Chat — Flutter Implementation Guide

**Audience:** Flutter developer (customer app + driver app), or an AI agent working in the Flutter repo.
**Base URL:** `https://readyride.razinsoft.com/api/v1`
**Status:** Implemented on the backend branch — **not yet deployed**.

> **This document is complete.** Every request body, every response field, every error, and every realtime payload below is copied from the actual implementation. You can build the entire feature from this file without calling a live API.

---

## Changelog

### 2026-08-12 — `channel` added to every conversation payload

**Backend change:** additive only. One new field, `channel`, on every conversation
object. Nothing was renamed, removed, or moved; no route or request body changed;
no migration. Existing screens keep working after you deploy.

**App change required: 1 line.** This is the fix for the
`403 AccessDeniedHttpException` from `/broadcasting/auth`.

```dart
// ❌ before — order_id is NOT the conversation id, so this subscribes to
//    a channel you are not a participant of → 403
pusher.subscribe(channelName: 'private-conversation.${conversation['order_id']}');

// ✅ after — use the string the backend hands you, verbatim
pusher.subscribe(channelName: conversation['channel']);
```

Deploying the backend alone does **not** clear the 403 — the app is choosing the
wrong id, so this line has to change too. (`'private-conversation.${conversation['id']}'`
is also correct; `channel` is just the version you cannot get wrong.)

**Where the new field appears:** §3.1 `GET {P}/order/{orderId}`,
§3.2 `GET {P}/{conversationId}/messages`, and §3.5 `GET {P}/conversations` —
i.e. everywhere a conversation object is returned, in both apps.

**Not changed by this release:** FCM push is a server-side configuration matter
(Firebase credentials) plus making sure the app registers its device token — see
§7. It is unrelated to the `channel` fix.

---

## 0. Read this first

- **The app never creates or closes a conversation.** The backend opens it when a driver accepts the order, and closes it when the order finishes. Your job is only: show, read, send, mark-read, presence.
- **Same endpoints for both apps.** The only difference is the path prefix and which side you are:
  - Customer app → `/chat/...`
  - Driver app → `/driver/chat/...`
  Everything else — bodies, responses, errors — is identical. Identity comes from the bearer token.
- **Response envelope** (unchanged from the rest of the API):
  ```json
  { "success": true, "message": "…", "data": { } }
  ```
  Errors use the same shape with `"success": false` and sometimes an `"errors"` object.
- **Auth:** `Authorization: Bearer <token>` on every request, same token as the rest of the app.
- **Works for rides *and* parcel deliveries** — identical behaviour, `order_type` tells you which.

---

## 1. Lifecycle — the rules that drive your UI

| # | Event | Backend | What the app must do |
|---|---|---|---|
| 1 | Driver accepts the order | conversation created, `status: active` | **Show the message icon** on the ride/parcel screen |
| 2 | During the ride | messages flow over Pusher | Normal chat UI |
| 3 | Order completes **or** is cancelled | `status: closed`, `ConversationClosed` broadcast | **Disable the composer**, show "This chat has ended". History stays readable |
| 4 | Same customer + same driver, **new** order | a **brand-new** conversation (new `id`) | Treat it as a fresh thread — never reuse the old `conversation_id` |

Because a conversation is tied to one `order_id`, rule 4 is automatic: always resolve the conversation **from the order**, never cache it against a driver.

---

## 2. The flow in practice

```
Ride/parcel screen opens
        │
        ├─ GET /chat/order/{orderId}
        │     404 → driver hasn't accepted yet → hide the message icon
        │     200 → save conversation.id → show icon with unread_count badge
        │
User taps the message icon
        │
        ├─ GET /chat/{id}/messages?limit=30      → render (already marks read)
        ├─ subscribe private-conversation.{id}   → live updates
        └─ POST /chat/heartbeat  every 60s       → so the other side sees "Online"
        │
User types and sends
        └─ POST /chat/{id}/send  { body }
              201 → append (or reconcile your optimistic bubble)
              422 → chat closed → disable composer

User leaves the screen
        └─ POST /chat/offline
```

---

## 3. Endpoints

Replace `{P}` with `/chat` (customer app) or `/driver/chat` (driver app).

### 3.1 `GET {P}/order/{orderId}` — is there a chat for this order?

The first call you make. Drives whether the message icon appears.

**200**
```json
{
  "success": true,
  "message": "Conversation fetched.",
  "data": {
    "conversation": {
      "id": 7,
      "order_id": 29,
      "channel": "private-conversation.7",
      "order_number": "RR-2026-00029",
      "order_type": "ride",
      "order_status": "accepted",
      "status": "active",
      "is_active": true,
      "can_send": true,
      "closed_at": null,
      "participant": {
        "type": "driver",
        "id": 1,
        "name": "Jamal Mia",
        "avatar": "https://…/storage/avatars/x.jpg",
        "is_online": true,
        "last_seen_at": "2026-08-11T10:19:48.000000Z"
      },
      "unread_count": 2,
      "last_message": {
        "body": "On my way, 2 min",
        "sender_type": "driver",
        "is_mine": false,
        "created_at": "2026-08-11T10:19:48.000000Z"
      },
      "last_message_at": "2026-08-11T10:19:48.000000Z"
    }
  }
}
```
- `channel` **(added 2026-08-12)** — the exact Pusher channel to subscribe to. Pass it through untouched. Note in the example above that `id` is `7` while `order_id` is `29`: the channel is built from **`id`**, never `order_id`. Building it yourself from `order_id` is what produces a 403 (see §4).
- `participant` is always **the other person** — the driver in the customer app, the customer in the driver app. Render it directly in the chat header.
- `can_send` — bind your composer's enabled state to this. Do not infer it from anything else.
- **404** `"No chat for this order yet."` → the driver has not accepted; hide the icon and re-check when the order status changes to `accepted`.

### 3.2 `GET {P}/{conversationId}/messages` — open the thread

Query: `limit` (1–100, default 30) · `before_id` (for older pages)

**Returned oldest → newest**, so you can append straight into a bottom-anchored list.

**200**
```json
{
  "success": true,
  "message": "Messages fetched.",
  "data": {
    "conversation": { "...": "same object as 3.1" },
    "messages": [
      {
        "id": 4,
        "conversation_id": 7,
        "body": "Hi, I'm at the main gate",
        "sender_type": "user",
        "sender_id": 1,
        "is_mine": true,
        "is_read": true,
        "read_at": "2026-08-11T10:20:02.000000Z",
        "created_at": "2026-08-11T10:19:40.000000Z"
      }
    ],
    "pagination": { "has_more": false, "next_before_id": null, "limit": 30 }
  }
}
```
- **`is_mine`** — use this for left/right bubble alignment. Never compare ids yourself.
- **`is_read` / `read_at`** — single vs double tick on your own messages.
- **Infinite scroll:** when `has_more` is true, load older with `?before_id={next_before_id}`.
- **Opening this endpoint marks the other side's messages as read** — you do not need to call mark-as-read after it.
- **404** → not a participant, or no such conversation.

### 3.3 `POST {P}/{conversationId}/send`

```json
{ "body": "Hi, I'm at the main gate" }
```
`body`: **required**, max **2000** characters.

**201**
```json
{ "success": true, "message": "Message sent.", "data": { "message": { "...": "same shape as above, is_mine: true" } } }
```

**Errors**
| Code | Meaning | App action |
|---|---|---|
| `422` + `errors.conversation_status = "closed"` | ride ended | Disable composer, show "This chat has ended" |
| `422` (validation) | empty or > 2000 chars | Inline field error |
| `404` | not a participant | Close the screen |

> **Optimistic UI:** render the bubble immediately with a temp id, then replace it with the returned `message` on 201, or mark it failed on error. You will **not** receive your own message back over Pusher (see §4).

### 3.4 `POST {P}/{conversationId}/read`

Body optional:
```json
{ "message_ids": [12, 13] }
```
Omit `message_ids` to mark **all** received messages read.

**200** → `data: { "read_count": 2, "unread_count": 0 }`

Use when the user scrolls back to the bottom of an already-open thread. (Not needed right after §3.2, which marks read for you.)

### 3.5 `GET {P}/conversations` — inbox

Query: `per_page` (max 50) · `active_only=1` for ongoing rides only.

**200** → `data` is an **array** of the same conversation object as §3.1, newest activity first, plus:
```json
"meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 3 }
```

### 3.6 `GET {P}/unread-count` — tab badge

**200** → `data: { "unread_count": 3 }` (across all conversations).

### 3.7 Presence — `POST {P}/heartbeat` and `POST {P}/offline`

**heartbeat 200** → `data: { "is_online": true, "last_seen_at": "…", "heartbeat_interval": 60 }`

- Call **heartbeat every 60 s** (use the returned `heartbeat_interval`) while a chat screen is visible.
- Call **offline** when leaving the chat or backgrounding the app.
- Online is derived from "seen within the last 2 minutes", so it self-heals if the app is killed — you never get a user stuck showing "Online".
- Read the other side's state from `participant.is_online` / `participant.last_seen_at` (§3.1).

---

## 4. Realtime (Pusher)

**Channel:** `private-conversation.{conversationId}`

> ⚠️ **The single most common integration bug — a 403 from `/broadcasting/auth`.**
> The id in the channel name is the **conversation id** (`data.id`), **not** the
> order id (`data.order_id`). They are different numbers: an order id of `48` may
> have conversation id `21`. Subscribing to `private-conversation.48` is a
> different channel that this driver/customer is not a participant of, so
> authorisation correctly rejects it with `403 AccessDeniedHttpException`.
>
> Don't build the string yourself. Every conversation payload now ships a ready
> made `channel` field — subscribe to it verbatim:
>
> ```json
> { "id": 21, "order_id": 48, "channel": "private-conversation.21" }
> ```
>
> ```dart
> pusher.subscribe(channelName: conversation['channel']);
> ```

**Authorisation:** `POST {{base_url}}/broadcasting/auth` with the same bearer token — the same mechanism the app already uses for `private-order.{id}`. Only the two participants are authorised; anyone else is rejected.

### Events

**`MessageSent`**
```json
{
  "message": {
    "id": 4,
    "conversation_id": 7,
    "sender_type": "user",
    "sender_id": 1,
    "sender_name": "Rahim Uddin",
    "body": "Left at reception",
    "is_read": false,
    "created_at": "2026-08-11T10:17:48.000000Z"
  }
}
```
> ⚠️ **No `is_mine` here** (the payload is shared by both sides). Derive it: `is_mine = sender_type == mySide`, where `mySide` is `"user"` in the customer app and `"driver"` in the driver app.

**`MessagesRead`**
```json
{ "conversation_id": 7, "reader_type": "driver", "read_count": 2, "read_at": "2026-08-11T10:20:02.000000Z" }
```
The other side opened the chat → flip your sent bubbles to double ticks.

**`ConversationClosed`**
```json
{ "conversation_id": 7, "order_id": 29, "status": "closed", "reason": "completed", "closed_at": "2026-08-11T10:17:48.509707Z" }
```
`reason` is `completed` or `cancelled`. Disable the composer and show the ended state — do not wait for a failed send.

### Also expect a push notification
When a message arrives, the recipient also gets an FCM push (`type: "chat"`) carrying `conversation_id` and `order_id` in the data payload — use it to deep-link into the thread when the app is backgrounded.

---

## 5. Suggested app state

```dart
class ChatState {
  int? conversationId;
  bool canSend = false;          // ← from conversation.can_send
  String status = 'active';      // active | closed
  List<Message> messages = [];   // oldest → newest
  int unreadCount = 0;
  bool participantOnline = false;
  DateTime? participantLastSeen;
  bool hasMore = false;
  int? nextBeforeId;
}
```

**Rules to encode**
1. Resolve the conversation **from `order_id`**, every time the ride screen opens. Never persist `conversation_id` against a driver — a new ride means a new conversation.
2. Composer enabled ⟺ `canSend`. Set it false on `ConversationClosed` and on a 422 with `conversation_status: "closed"`.
3. On `MessageSent`, ignore the event if a message with that `id` is already in the list (protects against the optimistic copy).
4. Stop the heartbeat timer when the screen closes, and call `/offline`.
5. `unread_count` from §3.6 drives the tab badge; per-conversation `unread_count` (§3.1/§3.5) drives the row badge.

---

## 6. Error reference

| HTTP | When | Handling |
|---|---|---|
| `401` | token missing/expired | Normal re-auth path |
| `404` | no chat for that order yet, or you are not a participant | Hide the icon / close the screen |
| `422` + `errors.conversation_status="closed"` | sending to a finished ride | Disable composer permanently |
| `422` | `body` empty or > 2000 chars | Inline validation error |

---

## 7. Backend setup (for whoever deploys)

1. **Pusher must be configured** (Settings → Notifications) — without it, messages still save and the REST API works, but nothing arrives live.
2. **Queue worker must be running** — broadcasts go through the `broadcasts` queue:
   ```bash
   php artisan queue:work --queue=mail,broadcasts,dispatch,default --tries=3 --timeout=300
   ```
3. Run `php artisan migrate` (adds `conversations`, `messages`, and `last_seen_at`).
