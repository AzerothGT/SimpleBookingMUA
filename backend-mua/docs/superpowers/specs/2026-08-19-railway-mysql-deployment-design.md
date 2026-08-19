# Railway MySQL Deployment Design

## Goal

Deploy `backend-mua` to Railway as a Laravel API backed by a MySQL service in the same Railway project. Service images remain external URLs and are never uploaded to the Laravel container.

## Approach

Use Railway's Laravel auto-detection (Railpack) rather than adding a custom Dockerfile. Configure the build and pre-deploy commands in repository configuration or Railway service settings, while keeping the application runtime compatible with Railway's assigned `PORT`.

## Runtime and configuration

The application service will:

1. Install Composer dependencies.
2. Build Vite assets with `npm run build`.
3. Run `php artisan migrate --force` against the Railway MySQL service.
4. Clear and rebuild Laravel caches.
5. Start the Laravel application using Railway's detected PHP-FPM/Caddy runtime.

Required Railway variables include:

- `APP_KEY`
- `APP_URL` set to the generated Railway public domain
- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_CONNECTION=mysql`
- `DB_HOST=${{MySQL.MYSQLHOST}}`
- `DB_PORT=${{MySQL.MYSQLPORT}}`
- `DB_DATABASE=${{MySQL.MYSQLDATABASE}}`
- `DB_USERNAME=${{MySQL.MYSQLUSER}}`
- `DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}`
- `LOG_CHANNEL=stderr`
- `MIDTRANS_SERVER_KEY`
- `MIDTRANS_SNAP_URL` set to the intended sandbox or production endpoint

The frontend must use the backend Railway URL as its API base URL. Midtrans's notification URL must point to `/api/webhooks/midtrans` on the public backend domain.

## Database compatibility

The target is MySQL 8.0.16 or newer. Existing migrations use standard Laravel/MySQL-compatible types and operations, including UUID strings, transactions, JSON, indexes, foreign keys, and row locks.

The service image migration already uses MySQL triggers to enforce one cover image per service. The deployment change will make its rollback explicitly remove those triggers before dropping the table. External-image mode remains the supported demo path; `image_url` is stored as a URL and `image_source` is set to `external`.

## Error handling and operations

- Production debug output remains disabled.
- Logs are sent to Railway's console through `stderr`.
- Migrations run before the application starts and fail the deployment if unsuccessful.
- No local image persistence is required.
- MySQL remains the source of truth for application data.
- Midtrans secrets are configured only through Railway Variables.

## Validation

Before deployment, run:

- Laravel migration/test suite against the existing local environment.
- PHP formatting for modified PHP files.
- Production asset build with `npm run build`.
- A MySQL migration smoke test, if a local or temporary MySQL 8 instance is available.

After deployment, verify:

- Railway health endpoint `/up`.
- API endpoint under `/api`.
- MySQL connectivity and migration status.
- External image rendering from the frontend.
- Midtrans Snap creation and webhook delivery in sandbox.
