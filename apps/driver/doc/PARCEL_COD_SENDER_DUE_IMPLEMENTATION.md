# Parcel COD Sender-Due — Implementation Status

**Contract:** `doc/PARCEL_COD_SENDER_DUE_API.md`
**Backend:** `rasel` branch — **not yet merged/deployed**. Production will not return the new fields until it ships. Do not release either app before the backend is live.

**Concept recap:** For COD parcels, the receiver now pays **only the product price** (`cod_amount`). The delivery charge is billed to the **sender's wallet** at delivery completion; whatever the wallet can't cover becomes a sender **due**. Top-ups clear the due first. When `due_amount >= due_limit` (default ৳500), new COD bookings are blocked (403) and account deletion is blocked while any due is outstanding (422).

---

## Part 1 — Customer app: DONE (this repo)

All changes are backward compatible: every new field has a safe default (`due_amount` → `0.00`, `can_place_cod` → `true`), so the app behaves exactly as before against the current production API.

### Data layer
| File | Change |
|---|---|
| `lib/features/wallet/model/wallet_model.dart` | Parses `due_amount`, `due_limit`, `can_place_cod`; getters `dueValue`, `dueLimitValue`, `hasDue` |
| `lib/features/wallet/model/due_entry_model.dart` | **NEW** — due-ledger row (`type` added/paid, `amount`, `due_before/after`, `note`, `order_number`, `created_at`) |
| `lib/features/parcel/model/parcel_booking_model.dart` | Parses new `delivery_charge_payer` |
| `lib/core/constants/api_endpoints.dart` | New `walletDues = '/user/wallet/dues'` |
| `lib/features/wallet/repository/wallet_repository(_impl).dart` | New paginated `getDues(page)` (`?page=N&per_page=20`), mirrors `getTransactions` |

### Wallet
| File | Change |
|---|---|
| `lib/features/wallet/provider/wallet_provider.dart` | `loadDues({refresh})` + pagination state; getters `dueAmount`/`hasDue`/`canPlaceCod`; **fix**: `refreshBalanceAfterTopup()` now also watches `due_amount` (a due-only top-up previously never signalled the change) |
| `lib/features/wallet/views/screen/wallet_screen.dart` | Red **"Outstanding due"** banner under the balance card when `hasDue`, with progress bar toward `due_limit`; tap → due history |
| `lib/features/wallet/views/screen/due_history_screen.dart` | **NEW** — paginated due ledger at route `/due-history` (`RouteNames.dueHistory`) |
| `lib/features/wallet/views/widgets/due_tile.dart` | **NEW** — ledger row: `added` red `+`, `paid` green `-`, note/order number, "Due after" |
| `lib/features/wallet/views/screen/topup_screen.dart` | After a successful top-up, if the due decreased, shows "৳X of your top-up cleared your delivery-charge due" instead of the plain success message |

### Parcel COD
| File | Change |
|---|---|
| `lib/features/parcel/views/widgets/cod_input.dart` | **Math fixed**: receiver pays = product only (was product + delivery); "you receive" = full product (was product − delivery); new red row "Delivery charge — billed to your wallet after delivery"; new `enabled` flag to disable the COD switch |
| `lib/features/parcel/provider/parcel_provider.dart` | `isDueLimitBlocked` set when `/parcel/book` returns **403**; cleared on retry/reset |
| `lib/features/parcel/views/screen/parcel_confirm_screen.dart` | On 403: dialog with the server message + **"Go to Wallet"** action (→ wallet tab) |
| `lib/features/parcel/views/screen/parcel_cod_screen.dart` | Pre-emptive gate: refreshes wallet on entry; when `can_place_cod == false` shows a red warning banner, disables the COD switch, forces COD off |

### Delete account
| File | Change |
|---|---|
| `lib/features/profile/provider/profile_provider.dart` | Records `deleteErrorStatus` (HTTP status of a failed delete) |
| `lib/features/profile/views/screen/settings_screen.dart` + `profile_screen.dart` | On **422**: dialog with the actual server message ("Please clear your outstanding due…") + "Go to Wallet" action. Other failures now also show the server message instead of the old static "Could not delete." |

### Translations
`assets/translations/en.json` + `ar.json` (full parity): 13 new keys (`wallet.outstanding_due`, `wallet.due_of_limit`, `wallet.due_history`, `wallet.no_dues(_msg)`, `wallet.due_added`, `wallet.due_paid`, `wallet.due_after_label`, `wallet.due_cleared_msg`, `wallet.go_to_wallet`, `parcel.cod_delivery_note`, `parcel.cod_blocked_title`, `parcel.cod_blocked_due`) and 2 changed formula strings (`parcel.cod_receiver_formula` → "Product price only", `parcel.cod_you_receive_formula` → "Full product price to your wallet").

### Verified / remaining
- `flutter analyze` — clean on all changed files.
- **Still to test manually against a `rasel`-branch backend:** due banner + history pagination; due-clearing top-up message; COD at/over the limit (pre-gate + 403 dialog); delete-account with due; Arabic locale pass.

---

## Part 2 — Driver (rider) app: DONE (this repo, 2026-07-11)

Per §2 of the contract. Small scope — mostly display logic.

### What was changed
| File | Change |
|---|---|
| `lib/features/parcel_order/model/active_parcel_model.dart` | `codCollectible` is now the product price only (`cod_amount`); it no longer adds `delivery_charge` when payment timing is "after". This is the figure used everywhere the rider is told how much to collect (COD reminder banner, collect-COD task tile, collection sheet, request payload). |
| `lib/features/parcel_order/views/widgets/cod_collection_sheet.dart` | Subtitle now says "(product price only — delivery charge is billed to the sender)"; on failure the sheet shows the **server's** error message (e.g. the reworded shortfall 422) instead of a generic "Could not record COD". |
| `lib/features/parcel_order/views/screen/parcel_order_screen.dart` | Passes the server error message into the COD sheet (falls back to the generic string). |
| `lib/features/parcel_order/views/screen/parcel_complete_screen.dart` | COD summary: "Sender payout" is now the full product price (was `collected − delivery charge`, correct only under the old model). |
| `assets/translations/en.json` + `ar.json` | `parcel.product_plus_delivery_charge` replaced by `parcel.product_price_only`. |

### Not needed (verified)
- The app never parses the collect-cod success `data` or 422 `errors` fields — it only checks `success` and shows `message`, so no model changes were required.
- `update-status`: the app doesn't read the API's `cod_reminder`; it renders its own reminder from `codCollectible`, which is now product-only.
- `request_details_card.dart` (incoming request) already showed `cod_amount` alone.
- `POST /driver/parcel/complete` — no change, per contract.

`flutter analyze` clean on the changed features. **Still to test manually against a `rasel`-branch backend:** collect exactly the product price → succeeds; collect less → server 422 message shown in the sheet; completion summary shows full product as sender payout.

### Original scope (for reference)

### 2.1 `POST /driver/parcel/collect-cod` — the main change
- The amount the rider must collect is now the **product price only** (`cod_amount`), **not** `cod_amount + delivery_charge`.
- **Action:** find every screen/widget that tells the rider how much cash to collect. If it displays `cod_amount + delivery_charge`, change it to `cod_amount` alone.
- Success `200` response has new fields — update the model if the app parses this response:
  `collected`, `product_price`, `delivery_charge`, `delivery_charged_to` (`"sender_wallet"`), `your_earning`.
- The `422` (only when the rider collects **less** than the product price) is reworded and its `errors` payload changed: `expected_amount`, `collected_amount`, `shortfall`, `product_price`, `delivery_charge`, `delivery_charged_to`. If the app parses these fields, update the model; if it only shows `message`, it works as-is.
- This change **fixes** the old bug where collecting the product price returned `"Collected amount is less than the expected 197.10"`.

### 2.2 `POST /driver/parcel/update-status`
- `cod_reminder.collect_amount` is now already the correct product-only figure (`breakdown` e.g. `"৳100 (product price)"`).
- **Action:** display `collect_amount` as-is. If the app currently adds the delivery charge on top before displaying, remove that.

### 2.3 `POST /driver/parcel/complete`
- **No app change.** Sender wallet/due settlement happens server-side.

### Suggested driver-app checklist
1. [x] Grep for `delivery_charge` usage in the COD-collect / cash-collection UI; remove it from any "amount to collect" math.
2. [x] Update the collect-cod response/error models for the new fields (if parsed). — *Not parsed; only `message` is shown, now surfaced in the COD sheet.*
3. [x] Verify `cod_reminder.collect_amount` is shown without modification. — *App builds its own reminder from `codCollectible` (now product-only).*
4. [ ] Test against a `rasel`-branch backend: collect exactly the product price → succeeds; collect less → new 422 message with shortfall.

---

*Customer-app implementation completed 2026-07-11 (Claude Code session). Rides, non-COD parcels, and the driver-due system are unchanged.*
