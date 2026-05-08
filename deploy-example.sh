#!/bin/bash

# =============================================================================
# deploy-example.sh — Contoh Script Deploy CRM Krakatau
# =============================================================================
# PERHATIAN: Ini adalah file CONTOH. Sesuaikan path dan konfigurasi dengan
# kondisi server Anda sebelum digunakan.
# Jangan jalankan file ini secara otomatis tanpa review terlebih dahulu.
# =============================================================================

set -e  # Hentikan script jika ada command yang gagal

APP_DIR="/var/www/crm"
BRANCH="main"

echo "=== [1/9] Masuk ke direktori aplikasi ==="
cd "$APP_DIR"

echo "=== [2/9] Pull kode terbaru dari GitLab ==="
git pull origin "$BRANCH"

echo "=== [3/9] Install dependensi PHP (production only) ==="
composer install --no-dev --optimize-autoloader

echo "=== [4/9] Install dependensi Node ==="
npm ci

echo "=== [5/9] Build asset frontend ==="
npm run build

echo "=== [6/9] Jalankan migrasi database ==="
php artisan migrate --force

echo "=== [7/9] Bersihkan cache lama ==="
php artisan optimize:clear

echo "=== [8/9] Buat cache baru ==="
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== [9/9] Set permission folder ==="
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

echo ""
echo "✅ Deploy selesai!"
echo "   URL: $(grep APP_URL .env | cut -d'=' -f2)"
