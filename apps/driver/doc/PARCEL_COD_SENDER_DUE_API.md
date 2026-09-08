# Parcel COD — Sender-Pays-Delivery + Sender Due (Flutter API changes)

**Audience:** Flutter developer (customer app + rider app).
**Scope:** Parcel **COD** only. Rides, non-COD parcels, and the driver-due system are unchanged.
**Base URL:** `https://readyride.razinsoft.com/api/v1`
**Status:** Backend implemented on the `rasel` branch — **not yet merged or deployed**. The production URL above will **not** return the new fields until the backend is deployed, so build/test against a backend running this branch, and ship the app only after the backend is live.

---

## 0. For the implementing agent

- **You have the Flutter repo, not the backend.** This document is the API contract. Map each change below onto the app's existing screens, models, and API/service layer (wallet screen, COD-collect screen, parcel-booking screen, top-up flow, delete-account screen). Search the Flutter codebase for the current calls to these endpoints and update the models + UI accordingly.
- **Auth is unchanged.** Every endpoint here uses the same bearer-token (Sanctum) auth the app already sends on other authenticated calls — no new auth work.
- **Only these endpoints changed.** Anything not listed in §2–§3 is untouched; don't refactor unrelated flows.
- **Response envelope is unchanged:** every response is `{ "success": bool, "message": string, "data": ... }` (lists also add `"meta"` for pagination) — the same shape the app already parses.
- **Money is always a string** with 2 decimals (`"75.00"`). Parse to num where you compute.

---

## 1. What changed (the concept)

Previously, a COD parcel expected the **receiver** to pay `product price + delivery charge`, and the rider was blocked if they collected less (this caused the `"Collected amount is less than the expected 197.10"` 422 error).

**New model:**
- The **receiver pays only the product price** (`cod_amount` — the number the sender types). The rider collects exactly that.
- The **delivery charge is billed to the SENDER** at delivery completion:
  - It is deducted from the sender's **wallet balance**.
  - Whatever the wallet cannot cover becomes a **sender "due"** (money the sender owes the platform).
- The sender still receives their full product price into their wallet. The driver still earns their delivery share.

> **`cod_amount` is unchanged.** The sender keeps typing the same single amount they type today (their product price). No new fields are required in the booking screen. The backend does the rest.

### Worked example (delivery charge = ৳125)
| Sender's wallet before | Sender types (`cod_amount`) | Rider collects | Sender wallet after | Sender due after |
|---|---|---|---|---|
| ৳0 | ৳200 | ৳200 | ৳200 (product) | **৳125** |
| ৳50 | ৳200 | ৳200 | ৳200 (product) | **৳75** |
| ৳500 | ৳200 | ৳200 | ৳575 (500 − 125 + 200) | ৳0 |

The delivery charge is billed against the wallet balance the sender had **before** the product payout, so a sender can hold a wallet balance **and** a due at the same time (exactly like the driver wallet/due model).

### How the due is cleared
Any **wallet top-up / add-money pays the outstanding due first**, then adds the remainder to the balance. (Same behavior the driver recharge already has.) Admin can also clear a sender's due from the panel.

### Due limit / block
There is a setting `sender_due_limit_amount` (default **৳500**). If a sender's due is **at or above** this limit, **new COD parcel bookings are blocked** with `403` until they clear the due.

---

## 2. Rider app changes

### 2.1 `POST /driver/parcel/collect-cod`

**Request (unchanged):**
```json
{ "order_id": 47, "collected_amount": 100.0 }
```

**What changed:** The expected amount is now the **product price (`cod_amount`)**, not `cod_amount + delivery_charge`. So collecting the product price now **succeeds** (this fixes the old 422).

**Success `200`:**
```json
{
  "success": true,
  "message": "COD collected. Complete delivery to finalize.",
  "data": {
    "collected": "100.00",
    "product_price": "100.00",
    "delivery_charge": "97.10",
    "delivery_charged_to": "sender_wallet",
    "your_earning": "XX.XX"
  }
}
```

**Error `422` (only if the rider collects LESS than the product price):**
```json
{
  "success": false,
  "message": "Collected amount is less than the product price 100.00.",
  "errors": {
    "expected_amount": "100.00",
    "collected_amount": "80.00",
    "shortfall": "20.00",
    "product_price": "100.00",
    "delivery_charge": "97.10",
    "delivery_charged_to": "sender_wallet"
  }
}
```

**App action:** Show the rider they must collect the **product price** (`data.product_price`). Do **not** add the delivery charge to the amount the rider is told to collect. If you were displaying `cod_amount + delivery_charge` as the "collect" figure, change it to `cod_amount` only.

### 2.2 `POST /driver/parcel/update-status`

The `cod_reminder` object now reflects product-only collection:
```json
"cod_reminder": {
  "collect_amount": "100.00",
  "breakdown": "৳100 (product price)"
}
```
Previously `collect_amount` was `product + delivery`. **App action:** display `collect_amount` as-is (it is already the correct amount to collect).

### 2.3 `POST /driver/parcel/complete`
No request change. Settlement now bills the sender's wallet/due behind the scenes. No app change needed.

---

## 3. Customer app changes

### 3.1 `GET /user/wallet` — new fields
```json
{
  "success": true,
  "data": {
    "balance": "200.00",
    "due_amount": "75.00",
    "due_limit": "500.00",
    "can_place_cod": true,
    "currency": "BDT",
    "currency_symbol": "৳"
  }
}
```
- `due_amount` — outstanding delivery-charge due the sender owes.
- `due_limit` — the block threshold.
- `can_place_cod` — `false` when `due_amount >= due_limit`.

**App action:** On the wallet screen, show a **"Due"** row when `due_amount > 0` (style it as owed, e.g. red). Optionally show a progress toward `due_limit`.

### 3.2 `GET /user/wallet/dues` — NEW endpoint (paginated due ledger)
Query: `?page=1&per_page=20`
```json
{
  "success": true,
  "message": "Dues fetched.",
  "data": [
    {
      "id": 12,
      "type": "added",
      "amount": "75.00",
      "due_before": "0.00",
      "due_after": "75.00",
      "note": "Delivery charge due RR-2026-00047",
      "order_number": "RR-2026-00047",
      "created_at": "2026-07-11T08:40:00.000000Z"
    },
    {
      "id": 13,
      "type": "paid",
      "amount": "75.00",
      "due_before": "75.00",
      "due_after": "0.00",
      "note": "Top-up — due cleared",
      "order_number": null,
      "created_at": "2026-07-11T09:10:00.000000Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 2 }
}
```
`type` is `added` (a delivery charge became due) or `paid` (due cleared via top-up / admin). **App action:** optional "Due history" screen.

### 3.3 Top-up responses now include due-clearing breakdown

Applies to `POST /user/wallet/topup/confirm` **and** `POST /user/wallet/add-money/confirm`.

`POST /user/wallet/topup/confirm` response:
```json
{
  "success": true,
  "message": "৳100 added to your wallet",
  "data": {
    "due_cleared": "75.00",
    "balance_added": "25.00",
    "new_balance": "225.00",
    "new_due": "0.00"
  }
}
```
**App action:** If `due_cleared > 0`, tell the user e.g. *"৳75 went to clear your delivery-charge due, ৳25 added to your balance."* The `add-money/confirm` endpoint still returns its existing `amount_added` / `new_balance` fields and also processes the due first — reading the fresh balance from `GET /user/wallet` after confirm is the safest way to refresh the UI.

### 3.4 `POST /parcel/book` — response fields + new block

Request is **unchanged** (`cod_amount` etc. all the same).

**Success response field change (COD):** `total_receiver_pays` is now just the **product price** (`cod_amount`), because the receiver no longer pays the delivery charge. A new field `delivery_charge_payer` is `"sender"` for COD (else `null`):
```json
{
  "delivery_charge": "97.10",
  "cod_amount": "100.00",
  "total_receiver_pays": "100.00",
  "delivery_charge_payer": "sender"
}
```
**App action:** On any "receiver pays" / order-summary UI, show `total_receiver_pays` (product only). If you want to show the sender their delivery cost, use `delivery_charge` and note it's billed to their wallet.

**New block:** if the sender books a **COD** parcel while `due_amount >= due_limit`, the API returns:

**`403`:**
```json
{
  "success": false,
  "message": "Your outstanding delivery-charge due has reached the limit. Please clear it before placing a new COD order.",
  "errors": {
    "due_amount": "520.00",
    "due_limit": "500.00"
  }
}
```
**App action:** On `403` from `/parcel/book`, show this message and route the user to the wallet/top-up screen to clear their due. You can also pre-empt this using `can_place_cod` from `GET /user/wallet` before opening the COD booking flow.

### 3.5 `DELETE /user/account` — blocked while a due is outstanding

Account deletion now behaves like the driver app: it is **blocked if the customer has an outstanding delivery-charge due**.

**`422`:**
```json
{
  "success": false,
  "message": "Please clear your outstanding due before deleting your account.",
  "errors": null
}
```
This is returned **before** the existing "ongoing order" check passes, i.e. it's an additional precondition. **App action:** on the delete-account screen, if this `422` comes back, show the message and route the user to the wallet/top-up screen to clear the due (top-up auto-clears it — see §3.3). You can pre-check with `due_amount` from `GET /user/wallet` and disable the delete button when `due_amount > 0`.

> The other two backend changes shipped alongside this (admin "Recharge" for customers, and a Due column in the admin customer CSV export) are **admin-panel only — no app change**.

---

## 4. Summary of app work

**Rider app**
1. `collect-cod`: the amount to collect is the **product price** (`cod_amount`), not product + delivery. Handle the new success fields and the reworded 422.
2. `update-status`: use `cod_reminder.collect_amount` directly.

**Customer app**
3. Wallet screen: show `due_amount` (when > 0) and optionally `due_limit` / due history (`GET /user/wallet/dues`).
4. Top-up flows: surface `due_cleared` / `balance_added` when a top-up clears a due.
5. COD booking: handle `403` (over due limit) → send user to top up; optionally gate the COD option on `can_place_cod`.
6. Delete account: handle the new `422` (outstanding due) → send user to top up; optionally disable delete when `due_amount > 0`.

**No change needed**
- The `cod_amount` input on the booking screen (sender still types their product price).
- Ride flows, non-COD parcel flows.

---

## 5. Notes / edge cases
- **Currency:** all money values are strings formatted to 2 decimals (e.g. `"75.00"`). Parse as needed.
- **Partial collection:** the rider must still collect **at least** the product price; collecting less returns the 422 in §2.1.
- **Where the due comes from:** only the delivery charge is ever billed to the sender. The product price is always paid out to the sender in full.
- **Idempotency of top-ups** is handled server-side; the app flow is unchanged.
