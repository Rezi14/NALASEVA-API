#!/usr/bin/env bash
# Keluar dari skrip jika ada error
set -o errexit

echo "Menginstall dependencies dengan Composer..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "Mengoptimalkan konfigurasi Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Menjalankan migrasi database..."
# Bendera --force wajib digunakan di production agar tidak meminta konfirmasi y/n
php artisan migrate --force
