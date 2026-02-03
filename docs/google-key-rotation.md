# Google Maps API Key Rotation Checklist

Use this checklist for local/dev/prod key rotation and cutover.

## 1) Create new keys per environment

Create three separate keys in Google Cloud Console:

- `GOOGLE_MAPS_API_KEY_LOCAL`
- `GOOGLE_MAPS_API_KEY_DEV`
- `GOOGLE_MAPS_API_KEY_PROD`

## 2) Restrict API scope

Allow only required APIs:

- Places API (Text Search / Find Place / Place Details / Nearby Search / Place Photos)

## 3) Restrict by origin/network

- **Local:** short-lived developer key, limited scope.
- **Dev:** IP allowlist for dev egress addresses.
- **Prod:** IP allowlist for production egress addresses.

## 4) Deploy and verify

1. Set `GOOGLE_MAPS_API_KEY` in each environment to the new key.
2. Run smoke tests on:
   - `api/extract-coordinates.php`
   - `api/admin/quick-add-beach.php`
   - `api/admin/audit-place-ids.php`
   - `scripts/verify-coordinates.php`
   - `migrations/010-import-beach-json.php` (only when intentionally run)

## 5) Revoke old keys

After successful smoke checks in all environments, revoke old exposed keys immediately.

## 6) Post-rotation checks

- CI secret scan passes.
- `grep -R "AI""za" . --exclude-dir=.git --exclude-dir=node_modules` returns no committed keys.
- Monitor Google API error rate for 24 hours after cutover.
