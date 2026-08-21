#!/usr/bin/env bash
set -euo pipefail

php artisan migrate --force
php artisan optimize:clear
php artisan l5-swagger:generate
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
