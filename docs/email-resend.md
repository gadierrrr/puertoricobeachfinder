# Resend Email Operations Runbook

This runbook covers configuration, rotation, troubleshooting, and production verification for email delivery via Resend.

## Scope

Email flows using `inc/email.php`:
- Magic link auth email (`magic-link`)
- Welcome email (`welcome`)
- List send email (`list-send`)
- Quiz results email (`quiz-results`)
- Admin test send (`admin_test`)

## Configuration

Set in `.env`:

- `RESEND_API_KEY=re_...`
- `RESEND_WEBHOOK_SECRET=whsec_...` (optional, for webhook signature verification)
- `EMAIL_PROVIDER=resend`

Domain: `puertoricobeachfinder.com` — verified on Resend (us-east-1).

Related endpoints:
- Webhook receiver: `/api/webhooks/resend.php`
- Health probe: `/api/health/email.php`

## Key Rotation

### API key (`RESEND_API_KEY`)

1. Create a new API key at https://resend.com/api-keys.
2. Update `.env` with the new key.
3. Deploy (`./deploy.sh`).
4. Verify health endpoint returns `ok: true`.
5. Delete old key in Resend dashboard.

### Webhook secret (`RESEND_WEBHOOK_SECRET`)

1. Create/update webhook at https://resend.com/webhooks with a new signing secret.
2. Update `.env` with the new `whsec_...` value.
3. Deploy.
4. Verify webhook delivery in the Resend dashboard.

## Health Checks

### Provider health

Expected:
- HTTP `200`, JSON `ok: true`
- `checks.api.reachable: true`, `checks.api.authenticated: true`

### Telemetry spot-check

Query `email_messages` for recent delivery statuses.

## Webhook Events

Resend uses Svix for webhook delivery. Supported events:
- `email.sent`, `email.delivered`, `email.delivery_delayed`
- `email.opened`, `email.clicked`
- `email.bounced`, `email.complained`

Signature verification uses `svix-id`, `svix-timestamp`, `svix-signature` headers.

## Troubleshooting

### Email sending is not configured

Likely causes:
- `RESEND_API_KEY` missing/blank
- `EMAIL_PROVIDER` not set to `resend`

### 401 Unauthorized from Resend

Likely causes:
- Wrong or revoked API key
- Key does not have sending permissions

### Webhook signature failures

Likely causes:
- `RESEND_WEBHOOK_SECRET` mismatch between Resend dashboard and `.env`
- Timestamp drift (webhook validates within 5 minute window)

## Data Model Notes

Migration `021-add-email-delivery-tracking.php` adds:
- `email_messages`
- `email_events`
- `email_contacts`

Use these tables for incident triage and delivery audits.
