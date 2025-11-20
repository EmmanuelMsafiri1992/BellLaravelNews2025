#!/bin/bash

# Deployment script for NanoPi-NEO
# This script should be run ON THE NANOPI after transferring files

echo "==================================="
echo "OnlyNews Laravel 7 - NanoPi Setup"
echo "==================================="

# Set project directory
PROJECT_DIR="/var/www/onlynews"

# Install composer dependencies (production only, no dev packages)
echo "Installing Composer dependencies..."
cd $PROJECT_DIR
composer install --no-dev --optimize-autoloader --no-interaction

# Set proper permissions
echo "Setting permissions..."
sudo chown -R www-data:www-data $PROJECT_DIR
sudo chmod -R 755 $PROJECT_DIR
sudo chmod -R 775 $PROJECT_DIR/storage
sudo chmod -R 775 $PROJECT_DIR/bootstrap/cache

# Create storage link for public uploads
echo "Creating storage symlink..."
php artisan storage:link

# Clear and cache configuration (optimization for embedded device)
echo "Optimizing Laravel..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set up database permissions
echo "Setting database permissions..."
sudo chmod 664 $PROJECT_DIR/database/database.sqlite
sudo chown www-data:www-data $PROJECT_DIR/database/database.sqlite

echo ""
echo "==================================="
echo "Deployment completed!"
echo "==================================="
echo ""
echo "Next steps:"
echo "1. Configure Nginx: sudo nano /etc/nginx/sites-available/onlynews"
echo "2. Enable site: sudo ln -s /etc/nginx/sites-available/onlynews /etc/nginx/sites-enabled/"
echo "3. Test Nginx: sudo nginx -t"
echo "4. Restart Nginx: sudo systemctl restart nginx"
echo "5. Access your site at http://YOUR_NANOPI_IP"
echo ""
