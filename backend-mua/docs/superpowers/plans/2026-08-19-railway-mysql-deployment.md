# Railway MySQL Deployment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deploy `backend-mua` to Railway with a Railway MySQL service, external image URLs, production-safe Laravel startup, and verified MySQL migrations.

**Architecture:** Keep the existing Laravel application architecture and use Railway's Laravel auto-detection. Add repository-owned deployment scripts for pre-deploy migrations/cache and a Railway configuration for the Vite build. The app stores only external image URLs; Railway MySQL stores application data.

**Tech Stack:** Laravel 13, PHP 8.3+, Composer, Vite, Railway Railpack, Railway MySQL 8.0.16+.

---

## File Map

- Create: `backend-mua/railway/init-app.sh` — idempotent pre-deploy migration and production cache commands.
- Create: `backend-mua/railway.json` — Railway build/deploy command configuration, if supported by the repository's Railway setup.
- Modify: `backend-mua/database/migrations/2026_08_06_000003_create_service_images_table.php` — remove MySQL triggers during rollback and keep MySQL-specific constraint behavior reversible.
- Modify: `backend-mua/app/Http/Requests/StoreServiceImageRequest.php` — validate external image URLs and restrict the demo path to external URLs.
- Modify: `backend-mua/app/Http/Requests/UpdateServiceImageRequest.php` — apply the same external URL validation to updates.
- Create or modify: `backend-mua/tests/Feature/ServiceImageTest.php` — verify external URL acceptance and upload/path rejection.
- Create or modify: `backend-mua/tests/Feature/RailwayHealthTest.php` — verify the Laravel health endpoint if the existing test conventions support it.
- Do not modify: `frontend-mua/.env` — existing user change remains untouched.

### Task 1: Add the Railway pre-deploy script

**Files:**
- Create: `backend-mua/railway/init-app.sh`

- [ ] **Step 1: Create the script with strict failure behavior**

```bash
#!/usr/bin/env bash
set -euo pipefail

php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
```

The script must not generate an application key or seed demo data. `APP_KEY` and production secrets belong in Railway Variables.

- [ ] **Step 2: Verify the script is present and contains no secret values**

Run from `backend-mua`:

```bash
test -f railway/init-app.sh
! rg -n "APP_KEY=|MIDTRANS_SERVER_KEY=|DB_PASSWORD=" railway/init-app.sh
```

Expected: both commands exit successfully.

- [ ] **Step 3: Commit the isolated script change**

```bash
git add railway/init-app.sh
git commit -m "Add Railway Laravel init script"
```

### Task 2: Configure Railway build and deploy commands

**Files:**
- Create or modify: `backend-mua/railway.json`

- [ ] **Step 1: Confirm the repository's Railway configuration format**

Check the current Railway documentation and existing repository files before creating the file. If Railway auto-detection accepts dashboard commands without a repository config, use the smallest supported repository configuration rather than inventing an unsupported schema.

- [ ] **Step 2: Configure the build command to build frontend assets**

The resulting configuration must cause `npm run build` to run during deployment after Node dependencies are installed. The pre-deploy command must run:

```bash
bash railway/init-app.sh
```

Do not put `php artisan migrate --force` in the normal build command because builds should not mutate the production database.

- [ ] **Step 3: Ensure the runtime honors Railway's assigned port**

Use Railway's detected Laravel PHP-FPM/Caddy runtime unless validation proves it cannot serve this application. Do not hard-code port `8000`; Railway supplies `PORT`.

- [ ] **Step 4: Validate configuration syntax**

Run:

```bash
php -r "json_decode(file_get_contents('railway.json'), true, 512, JSON_THROW_ON_ERROR); echo 'valid';"
```

Expected: `valid`.

- [ ] **Step 5: Commit the deployment configuration**

```bash
git add railway.json
 git commit -m "Configure Railway Laravel deployment"
```

### Task 3: Make the service image migration reversible for MySQL

**Files:**
- Modify: `backend-mua/database/migrations/2026_08_06_000003_create_service_images_table.php`

- [ ] **Step 1: Write a migration regression test or a focused schema check**

The test/check must establish that the migration's `down()` path removes both MySQL triggers before dropping `service_images`. Prefer an existing migration test pattern; otherwise use a feature test that runs only when a MySQL connection is available.

Expected assertions on MySQL:

```php
expect(DB::select("SHOW TRIGGERS LIKE 'service_images'") === [])->toBeTrue();
expect(Schema::hasTable('service_images'))->toBeFalse();
```

Do not force SQLite to emulate MySQL trigger behavior.

- [ ] **Step 2: Run the focused test/check before implementation**

Run the project-specific focused command, for example:

```bash
php artisan test --compact tests/Feature/ServiceImageTest.php
```

Expected: the new rollback assertion fails or is skipped with a clear reason when no MySQL database is available.

- [ ] **Step 3: Update `down()` to drop MySQL triggers safely**

Use the database driver guard and `DROP TRIGGER IF EXISTS` for both trigger names before calling `Schema::dropIfExists('service_images')`:

```php
public function down(): void
{
    if (DB::getDriverName() === 'mysql') {
        DB::unprepared('DROP TRIGGER IF EXISTS service_images_one_cover_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS service_images_one_cover_update');
    }

    Schema::dropIfExists('service_images');
}
```

- [ ] **Step 4: Run formatting and the focused test**

Run:

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact tests/Feature/ServiceImageTest.php
```

Expected: Pint completes successfully and the focused test passes in the configured test environment.

- [ ] **Step 5: Commit the migration change**

```bash
git add database/migrations/2026_08_06_000003_create_service_images_table.php tests/Feature/ServiceImageTest.php
git commit -m "Make service image triggers reversible"
```

### Task 4: Enforce external image URLs for the demo flow

**Files:**
- Modify: `backend-mua/app/Http/Requests/StoreServiceImageRequest.php`
- Modify: `backend-mua/app/Http/Requests/UpdateServiceImageRequest.php`
- Modify: `backend-mua/tests/Feature/ServiceImageTest.php`

- [ ] **Step 1: Inspect existing request rules and write failing cases**

Add tests for these exact behaviors:

```php
it('accepts an https external image URL', function () {
    // Authenticate an authorized admin and create a service.
    // POST the service image payload with image_source=external and image_url=https://...
    // Assert 201 and assert the stored image_url is the submitted URL.
});

it('rejects a non-external image source', function () {
    // POST the same payload with image_source=upload.
    // Assert 422.
});

it('rejects a non-http image URL', function () {
    // POST with image_url=javascript:alert(1) and image_source=external.
    // Assert 422.
});
```

Use existing factories, authentication helpers, and test conventions from the repository; do not add a new dependency.

- [ ] **Step 2: Run the focused tests before implementation**

```bash
php artisan test --compact tests/Feature/ServiceImageTest.php
```

Expected: the new cases fail against the current rules if upload and unsafe schemes are currently accepted.

- [ ] **Step 3: Implement the smallest request-rule change**

For both store and update requests, use Laravel validation rules equivalent to:

```php
'image_url' => ['sometimes', 'required', 'url:http,https', 'max:2048'],
'image_source' => ['sometimes', Rule::in(['external'])],
```

For the store request, retain requiredness where the existing API contract requires both fields. For update, preserve partial update behavior while validating a supplied `image_url` and `image_source`.

- [ ] **Step 4: Run formatting and focused tests**

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact tests/Feature/ServiceImageTest.php
```

Expected: all service-image validation tests pass.

- [ ] **Step 5: Commit the URL validation change**

```bash
git add app/Http/Requests/StoreServiceImageRequest.php app/Http/Requests/UpdateServiceImageRequest.php tests/Feature/ServiceImageTest.php
git commit -m "Restrict service images to external URLs"
```

### Task 5: Verify application and production asset behavior

**Files:**
- No source changes unless a validation failure identifies a root cause.

- [ ] **Step 1: Verify the Laravel health route**

Run:

```bash
php artisan route:list --path=up
```

Expected: the `/up` health route is registered.

- [ ] **Step 2: Run the complete backend test suite**

```bash
php artisan test --compact
```

Expected: all tests pass.

- [ ] **Step 3: Build production frontend assets**

```bash
npm run build
```

Expected: Vite completes successfully and creates `public/build`.

- [ ] **Step 4: Check MySQL-specific migration compatibility**

If a MySQL 8.0.16+ instance is available, configure a temporary test environment with `DB_CONNECTION=mysql`, run:

```bash
php artisan migrate:fresh --force
php artisan migrate:status
```

Expected: all migrations complete and show `Ran`.

If no MySQL instance is available locally, report that the MySQL smoke test remains pending rather than claiming MySQL compatibility was fully verified.

- [ ] **Step 5: Check the working tree and avoid unrelated changes**

```bash
git --no-optional-locks status --short
```

Expected: only intended backend deployment files are changed; the existing `frontend-mua/.env` modification remains untouched.

### Task 6: Configure Railway services and smoke test deployment

**Files:**
- No repository changes expected.

- [ ] **Step 1: Create a Railway project and add a MySQL service**

Create a MySQL service in the same Railway project as the Laravel app. Use MySQL 8.0.16+.

- [ ] **Step 2: Deploy `backend-mua` as the app service**

Set the service root directory to `backend-mua` if the repository contains both frontend and backend directories. Connect the repository and deploy the backend service.

- [ ] **Step 3: Add production variables**

Set these variables in the app service:

```text
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generated Laravel application key>
APP_URL=https://<generated-railway-domain>
LOG_CHANNEL=stderr
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
MIDTRANS_SERVER_KEY=<sandbox server key>
MIDTRANS_SNAP_URL=https://app.sandbox.midtrans.com
```

Do not commit any values for `APP_KEY`, database password, or Midtrans server key.

- [ ] **Step 4: Set the public domain and Midtrans webhook**

Generate a Railway domain, update `APP_URL`, and configure Midtrans's notification URL as:

```text
https://<generated-railway-domain>/api/webhooks/midtrans
```

- [ ] **Step 5: Verify the deployed service**

Check:

```text
GET https://<generated-railway-domain>/up
GET https://<generated-railway-domain>/api/services
```

Expected: `/up` returns a healthy response and `/api/services` returns a valid JSON API response.

- [ ] **Step 6: Verify demo-specific behavior**

From the frontend configured with the Railway API URL:

1. List services.
2. Confirm external image URLs render directly in the browser.
3. Create a booking.
4. Create a sandbox Snap transaction.
5. Confirm the Midtrans webhook reaches `/api/webhooks/midtrans`.

Do not test production payments until the sandbox flow is confirmed.

## Final Self-Review Checklist

- [ ] Every design requirement has a task: Railway runtime, MySQL variables, external image URLs, reversible triggers, logs, health check, and Midtrans webhook.
- [ ] No task depends on an unintroduced function or file.
- [ ] No secrets are placed in repository files.
- [ ] No local image persistence is added.
- [ ] Existing `frontend-mua/.env` changes are preserved.
- [ ] MySQL compatibility is explicitly validated rather than inferred from SQLite tests.
