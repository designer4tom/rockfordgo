# Changelog

ReadyRide — Ride-Hailing & Parcel Delivery Platform

> **Upgrade note:** back up first. Versions 2.0.0, 2.4.0 and 2.5.0 add database tables/columns — after updating the files run `php artisan migrate`. Versions 2.1.0–2.3.0 are file-only. No existing data is modified.

---

## Version 2.5.0 — 12 August 2026 — In-Ride Chat & 30+ Payment Gateways

Riders and drivers can now **message each other during a trip**, and your customers can top up their wallet through **any of 35 payment gateways**.

**Added**
- **In-ride chat between the rider and the driver.** The moment a driver accepts, a message icon appears in both apps and the two can talk — "I'm at the main gate", "give me two minutes" — without either side sharing a phone number. Works the same for rides and for parcel deliveries.
- **Messages arrive instantly**, with **online / last-seen** status, **unread badges**, and **read receipts** so each side knows the message landed.
- **A push notification for every message**, so a driver with the app in their pocket still hears about it.
- **A fresh conversation for every trip.** When the trip ends the thread closes and becomes read-only history — nobody can be messaged after the fact. If the same rider and driver are matched again on a new booking, they start a clean thread instead of reopening the old one.
- **The chat closes by itself** when a trip is completed *or* cancelled, from any part of the system — the apps, the admin panel, or an expired scheduled booking.
- **35 online payment gateways** — Stripe, Razorpay, Paystack, Adyen, Square, Braintree, Flutterwave, Mollie, PayTabs and more — available for customer wallet top-up and driver recharge, configured from the admin panel.
- **A push notification self-test** (`php artisan push:test --driver=3 --user=12`) that sends a real notification to a chosen driver and customer and reports exactly what the notification service said back — so a delivery problem can be identified in one run instead of by guesswork.
- Integration document for the mobile team covering the whole chat feature, plus updated Postman collections for the customer and driver APIs.

**Improved**
- **Failed push notifications are no longer silent.** Previously, a device with a missing or expired notification token was recorded in the in-app list and then quietly skipped — the notification list looked healthy while the phone showed nothing. Both cases are now written to the log with the reason.
- **Expired notification tokens are cleaned up properly.** Dead tokens were only partly removed, so the system kept trying to reach a device that could never receive anything. They are now cleared everywhere they are stored.

**Fixed**
- **Chat did not open when a driver accepted a booking.** Accepting through the driver app uses a safeguard that stops two drivers claiming the same booking, and that path bypassed the step that opens the conversation — so the message icon never appeared. Accepting now opens the chat reliably, and a failure to do so can no longer interfere with accepting the booking itself.
- **Live chat updates could be rejected for one of the two people**, leaving them on a thread that only refreshed when reopened. The apps are now given the exact connection details to use.

---

## Version 2.4.0 — 11 August 2026 — Email Login, Passwords & Social Sign-in

Customers and drivers now sign up with **name, email and a password**, confirm their address with a 6-digit code, then sign in with just **email and password** — or with **Continue with Google** / **Continue with Apple**.

**Added**
- **Sign up with name, email and password** in both the customer and driver apps. Passwords are a minimum of 8 characters and stored encrypted.
- **Email verification at sign-up** — a 6-digit code is emailed and confirmed before the account can be used. The code is valid 5 minutes (adjustable in Settings → Email) with a 30-second resend, and the email carries your own app name and logo.
- **One-step sign-in afterwards** — email and password go straight to the dashboard, since the address was already verified.
- **Sign in with Google** and **Sign in with Apple**, verified through Firebase using the same service-account file already used for push. Signing in with Google using an address that already has an account lands on that same account — no duplicates.
- **Forgot password** — email a code, set a new password. Existing sessions are signed out when the password changes.
- **Admin "Set Password"** on the customer and driver pages, with the account's current password status and sign-in method at a glance, plus an optional "sign out of all devices". Ideal for accounts created before passwords existed, or when a user needs help getting back in.
- **Email (SMTP) settings page** — host, port, username, password (encrypted), encryption, from-address/name, and **how long a code stays valid**, plus a **Send Test Email** button. Configure email entirely from the panel, no server file editing.
- **Email address on every list.** Customers, Drivers and Pending Approvals now show an Email column, and the search box finds people by email as well as name or phone.
- **Setup alert now covers email and the queue worker** — the "Setup required" banner and the Setup Guide flag both, so a missing mail server or a stopped worker can't silently block sign-ups.
- Integration document for the mobile team describing every new authentication endpoint, plus updated Postman collections for the customer and driver APIs.

**Changed**
- **Email is now the login identity** (it used to be the phone number). The phone number is collected once on the profile screen after signing up, and is still required for drivers and support to reach a customer.
- **Adding a customer or driver from the panel now requires an email** (it's how they sign in) and treats the phone number as optional, matching how accounts are created in the apps.
- **Vehicle category is now required when adding a driver** — it decides which ride requests that driver can receive, so a driver without one would never be matched.
- The driver app keeps its existing approval routing (pending / approved / rejected) unchanged after signing in.

**Improved**
- An account cannot be used until its email address has been confirmed, and an abandoned sign-up can simply be repeated — the address is never left unusable.
- Wrong password and unknown email return the same message, so registered addresses can't be discovered by guessing.
- Password-reset codes are separate from sign-up codes and cannot be used in place of one another.
- **Verification emails are sent in the background**, so sign-up responds instantly instead of waiting on the mail server. They use a dedicated priority queue, so a code is never stuck behind a bulk notification and expiring before it arrives.
- Phone-based login now also delivers its code by **SMS** when a provider is configured (previously WhatsApp only).
- The previous phone login endpoints remain available so already-released apps keep working during the rollout.

**Fixed**
- Editing a driver could save an email address already used by another driver, which would have broken sign-in for both accounts. It is now rejected.
- The Setup Guide had two sections sharing the same number, and its queue-worker command left out the queue that carries login emails — both corrected.

---

## Version 2.3.0 — 08 August 2026 — SMS Notifications

**Added**
- **Twilio SMS support.** Configure Account SID, Auth Token and your Twilio number in Settings → Notifications, then send a real **test message** to confirm it works. A generic gateway option is also available for other providers.
- **Notification Rules now work.** The per-event Push/SMS checkboxes previously had no effect — they now genuinely control delivery for all eight events (order accepted, driver arrived, delivery complete, document expiry 30/7 days, due limit reached, withdrawal approved, driver approval).
- **Due-limit alert** — drivers are now notified the moment their outstanding due crosses the limit and they can no longer accept orders.

**Fixed**
- International phone numbers entered with a leading `+` are no longer given the local country code, so test and overseas numbers reach the right destination.

---

## Version 2.2.0 — 04 August 2026

**Added**
- **Public info & legal pages.** Admin-managed pages (Privacy Policy, Terms & Conditions, etc.) now have a real public website URL and render inside the site shell (navigation, footer, dark mode). Previously they were available only to the mobile apps.
- **Copy URL for pages.** Every page in Admin → Pages has a one-click **Copy URL** (and **View**) action, so the link can be pasted straight into Website → Footer · Legal links.

**Fixed**
- **Wallet Recharge 500 error.** The admin **Recharge** action on both Customer and Driver wallets threw an Internal Server Error; it now completes correctly — clearing any outstanding due first, then topping up the balance.
- Website FAQ "View more / View less" toggle.
- Dark/light mode display issues.

---

## Version 2.1.0 — 16 July 2026 — White-Label Branding

**Added**
- **Admin Panel Logo.** Upload your own logo (Settings → General) — shown in the admin sidebar and on the login screen — with one-click **Remove logo** to revert to the default.
- **Configurable favicon** on both the public website and the admin panel tabs.
- **Full white-label naming.** The App Name and Website brand name now drive every browser tab title, the admin sidebar, the login screen, footers, and invoices — rebrand the whole product from the panel.

**Fixed**
- The favicon was never rendered on any page and the bundled default was an empty file — both fixed, with a proper default icon included.
- Browser tab titles were hardcoded to the original brand and ignored the App Name setting.
- The website header and footer showed the brand name twice next to an uploaded logo.

---

## Version 2.0.0 — 11 July 2026 — Parcel COD: Sender-Pays-Delivery & Sender Due

Major update: COD parcels now bill the delivery charge to the sender (merchant) instead of forcing the receiver to pay it, with a complete due-tracking system across the app and the admin panel.

**Added**
- **Sender-pays-delivery COD model.** The receiver pays only the product price; the delivery charge is billed to the sender's wallet at delivery, and anything the wallet cannot cover becomes a sender **due**.
- **Sender Due ledger** — customers now have a due balance and full due history, mirroring the driver-due system.
- **Admin → Customer Dues page** with totals, at-limit counts, filters and one-click Clear Due.
- **Customer Wallet tab** showing due amount, limit-usage bar, due history, Recharge and Clear Due.
- **Sender Due Limit setting** — customers over the limit are blocked from placing new COD orders.
- **Auto due-clearing on top-up** — wallet top-ups pay off the outstanding due first.
- Customer app API for due amount/limit/history, a Due column in the CSV export, an integration document and a demo seeder.

**Improved**
- A customer with an outstanding due can no longer delete their account until it is cleared.
- The rider is told to collect the product price only.

**Fixed**
- **"Collected amount is less than the expected …" error** — riders could not complete a COD delivery because the app demanded product + delivery. Riders now collect exactly the product price.
- The sender always receives their full product price; a short wallet is recorded as a due instead of being silently swallowed.
