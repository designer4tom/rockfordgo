# RockfordGo

RockfordGo rideshare platform source, based on the ReadyRide source purchased by BT Ventures Inc.

## Repository layout
- Laravel backend/admin: repository root
- Rider Flutter app: `apps/rider`
- Driver Flutter app: `apps/driver`

## Deployment
The Laravel backend/admin is configured for Render using Docker. Rider and Driver Flutter apps are built separately for iOS and Android.

## Security
Never commit `.env`, API keys, Stripe secrets, Firebase private keys, certificates, or production credentials.
