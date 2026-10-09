#!/bin/bash
# Tras rsync/deploy: SQLite y sesiones deben ser escribibles por PHP-FPM (www-data)
set -euo pipefail
APP="${1:-/var/www/samuel-memorias}"
chown -R www-data:www-data "$APP/database" "$APP/storage" "$APP/bootstrap/cache"
chmod 775 "$APP/database"
chmod -R ug+rwX "$APP/bootstrap/cache" "$APP/storage"
mkdir -p "$APP/storage/app/tmp"
chmod 775 "$APP/storage/app/tmp"
chmod 664 "$APP/database/database.sqlite" 2>/dev/null || true
if command -v php >/dev/null 2>&1; then
  (cd "$APP" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader -q && php artisan optimize:clear -q) || true
fi
if systemctl is-active --quiet php8.3-fpm 2>/dev/null; then
  systemctl reload php8.3-fpm || systemctl restart php8.3-fpm
fi
