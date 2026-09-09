#!/usr/bin/env bash

# Exit immediately if a command exits with a non-zero status
set -e

echo "=================================================="
echo " Starting Deployment on Hostinger..."
echo "=================================================="

# 1. Detect PHP 8.3 binary (Hostinger often has multiple PHP binaries)
PHP_BIN="php"

if command -v php8.3 &> /dev/null; then
    PHP_BIN="php8.3"
elif command -v /usr/bin/php8.3 &> /dev/null; then
    PHP_BIN="/usr/bin/php8.3"
elif command -v /opt/alt/php83/usr/bin/php &> /dev/null; then
    PHP_BIN="/opt/alt/php83/usr/bin/php"
elif command -v /usr/local/bin/php8.3 &> /dev/null; then
    PHP_BIN="/usr/local/bin/php8.3"
fi

echo "--> Using PHP Binary: $PHP_BIN"
$PHP_BIN -v | head -n 1

# 2. Check if .env exists
if [ ! -f ".env" ]; then
    echo "--> .env file not found! Copying from .env.example..."
    cp .env.example .env
    echo "--> WARNING: Please make sure to configure your DB credentials in .env!"
fi

# 3. Put Application in Maintenance Mode (if artisan works)
if [ -f "artisan" ]; then
    echo "--> Enabling maintenance mode..."
    $PHP_BIN artisan down --retry=60 || true
fi

# 4. Pull latest changes if it's a Git repository
if [ -d ".git" ]; then
    echo "--> Pulling latest changes from git..."
    CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "main")
    git config pull.rebase false 2>/dev/null || true
    git pull origin "$CURRENT_BRANCH" --no-edit || echo "Git pull skipped or failed, continuing..."
fi

# 5. Install / Update Composer dependencies
echo "--> Installing Composer dependencies..."
if command -v composer &> /dev/null; then
    $PHP_BIN $(which composer) install --no-interaction --prefer-dist --optimize-autoloader --no-dev
elif [ -f "composer.phar" ]; then
    $PHP_BIN composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev
else
    echo "Downloading composer.phar..."
    curl -sS https://getcomposer.org/installer | $PHP_BIN
    $PHP_BIN composer.phar install --no-interaction --prefer-dist --optimize-autoloader --no-dev
fi

# 6. Generate app key if not set
if ! grep -q "^APP_KEY=base64:" .env; then
    echo "--> Generating APP_KEY..."
    $PHP_BIN artisan key:generate --force
fi

# 7. Create storage symlink
echo "--> Linking storage directory..."
$PHP_BIN artisan storage:link || true

# 8. Run Database Migrations
echo "--> Running database migrations..."
$PHP_BIN artisan migrate --force

# 9. Clear & Cache Configurations, Routes, Views
echo "--> Caching configuration, routes, and views..."
$PHP_BIN artisan config:clear
$PHP_BIN artisan route:clear
$PHP_BIN artisan view:clear

$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# 10. Set proper directory permissions
echo "--> Setting permissions for storage and bootstrap/cache..."
chmod -R 775 storage bootstrap/cache

# 11. Bring Application back Up
echo "--> Bringing application live..."
$PHP_BIN artisan up

echo "=================================================="
echo " Deployment Completed Successfully! 🚀"
echo "=================================================="
