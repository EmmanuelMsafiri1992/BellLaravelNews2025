# NanoPi-NEO Deployment Guide for OnlyNews Laravel 7

## System Information
- **NanoPi OS**: Ubuntu 16.04.2 LTS (Xenial)
- **Required PHP**: 7.4
- **Laravel Version**: 7.30
- **Web Server**: Nginx + PHP-FPM

---

## Part 1: Prepare NanoPi-NEO

### Step 1: Install PHP 7.4 via PPA

```bash
# Update system
sudo apt-get update
sudo apt-get upgrade -y

# Install prerequisites
sudo apt-get install -y software-properties-common

# Add ondrej/php PPA (provides PHP 7.4 for Ubuntu 16.04)
sudo add-apt-repository ppa:ondrej/php
sudo apt-get update

# Install PHP 7.4 and required extensions
sudo apt-get install -y \
    php7.4 \
    php7.4-cli \
    php7.4-fpm \
    php7.4-sqlite3 \
    php7.4-mbstring \
    php7.4-xml \
    php7.4-curl \
    php7.4-zip \
    php7.4-gd \
    php7.4-bcmath \
    php7.4-json \
    php7.4-tokenizer

# Verify PHP installation
php -v
# Should show: PHP 7.4.x
```

### Step 2: Install Nginx and Composer

```bash
# Install Nginx
sudo apt-get install -y nginx

# Install Composer
cd ~
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
composer --version
```

### Step 3: Create Project Directory

```bash
# Create web root
sudo mkdir -p /var/www/onlynews

# Set temporary permissions for file transfer
sudo chown -R $(whoami):$(whoami) /var/www/onlynews
```

---

## Part 2: Transfer Files from Windows to NanoPi

### Option A: Using SCP (Recommended)

On your Windows machine (Git Bash or WSL):

```bash
# Navigate to project directory
cd /c/laragon/www/OnlyNews2025_Laravel7

# Create a tar archive (excluding heavy folders)
tar --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.git' \
    -czf onlynews-laravel7.tar.gz .

# Transfer to NanoPi (replace with your NanoPi IP)
scp onlynews-laravel7.tar.gz root@YOUR_NANOPI_IP:/tmp/
```

On NanoPi:

```bash
# Extract files
cd /var/www/onlynews
sudo tar -xzf /tmp/onlynews-laravel7.tar.gz -C /var/www/onlynews
sudo chown -R $(whoami):$(whoami) /var/www/onlynews
rm /tmp/onlynews-laravel7.tar.gz
```

### Option B: Using Git

On NanoPi (if you have the project in a Git repository):

```bash
cd /var/www/onlynews
git clone YOUR_GIT_REPO_URL .
```

---

## Part 3: Configure Laravel on NanoPi

### Step 1: Install Dependencies

```bash
cd /var/www/onlynews

# Install Composer dependencies (production mode)
composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
sudo chown -R www-data:www-data /var/www/onlynews
sudo chmod -R 755 /var/www/onlynews
sudo chmod -R 775 /var/www/onlynews/storage
sudo chmod -R 775 /var/www/onlynews/bootstrap/cache
```

### Step 2: Configure Environment

```bash
# Copy environment file (if not already present)
cp .env.example .env

# Edit .env file
nano .env
```

Update these values in `.env`:

```env
APP_NAME="OnlyNews"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://YOUR_NANOPI_IP

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/onlynews/database/database.sqlite

# Disable logging to prevent storage issues on embedded device
LOG_CHANNEL=single
LOG_LEVEL=error
```

### Step 3: Set Database Permissions

```bash
# Set database file permissions
sudo chmod 664 /var/www/onlynews/database/database.sqlite
sudo chown www-data:www-data /var/www/onlynews/database/database.sqlite
sudo chmod 775 /var/www/onlynews/database
sudo chown www-data:www-data /var/www/onlynews/database
```

### Step 4: Run Deployment Script

```bash
cd /var/www/onlynews
chmod +x deploy_to_nanopi.sh
./deploy_to_nanopi.sh
```

---

## Part 4: Configure Nginx

### Step 1: Create Nginx Configuration

```bash
sudo nano /etc/nginx/sites-available/onlynews
```

Paste the contents of `nanopi_nginx.conf` (provided in project), and update `YOUR_NANOPI_IP` to your actual IP.

### Step 2: Enable Site

```bash
# Create symlink to enable site
sudo ln -s /etc/nginx/sites-available/onlynews /etc/nginx/sites-enabled/

# Remove default site
sudo rm /etc/nginx/sites-enabled/default

# Test Nginx configuration
sudo nginx -t

# Restart services
sudo systemctl restart php7.4-fpm
sudo systemctl restart nginx

# Enable services to start on boot
sudo systemctl enable php7.4-fpm
sudo systemctl enable nginx
```

---

## Part 5: Optimize for Embedded Device

### PHP-FPM Optimization (Low Memory)

```bash
sudo nano /etc/php/7.4/fpm/pool.d/www.conf
```

Adjust these values for NanoPi's limited RAM:

```ini
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500
```

```bash
sudo systemctl restart php7.4-fpm
```

### Disable Unnecessary Services

```bash
# Free up RAM by disabling services you don't need
sudo systemctl disable bluetooth
sudo systemctl stop bluetooth
```

---

## Part 6: Test the Application

### Access the Application

Open a browser and navigate to:
```
http://YOUR_NANOPI_IP
```

You should see the OnlyNews homepage!

### Test API Endpoints

```bash
# From NanoPi or another machine
curl http://YOUR_NANOPI_IP/api/news
curl http://YOUR_NANOPI_IP/api/settings
```

---

## Troubleshooting

### Check Nginx Logs
```bash
sudo tail -f /var/log/nginx/onlynews_error.log
```

### Check PHP-FPM Logs
```bash
sudo tail -f /var/log/php7.4-fpm.log
```

### Check Laravel Logs
```bash
tail -f /var/www/onlynews/storage/logs/laravel.log
```

### Permission Issues
```bash
# Reset all permissions
cd /var/www/onlynews
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 storage bootstrap/cache
sudo chmod 664 database/database.sqlite
```

### Clear All Caches
```bash
cd /var/www/onlynews
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Performance Monitoring

```bash
# Check memory usage
free -h

# Check running processes
top

# Check Nginx connections
sudo netstat -tulpn | grep nginx
```

---

## Security Notes

⚠️ **IMPORTANT**: Ubuntu 16.04 reached End of Life (EOL) in April 2021. Consider upgrading to Ubuntu 20.04 or 22.04 for:
- Security patches
- Better PHP performance
- Long-term support

If upgrade is not possible, at least:
- Keep PHP updated via ondrej PPA
- Disable SSH password authentication (use keys only)
- Configure firewall: `sudo ufw allow 80/tcp && sudo ufw enable`
- Change default SSH port
- Regularly backup your database

---

## Quick Commands Reference

```bash
# Restart services
sudo systemctl restart nginx php7.4-fpm

# View logs
sudo tail -f /var/log/nginx/onlynews_error.log
tail -f /var/www/onlynews/storage/logs/laravel.log

# Clear Laravel cache
cd /var/www/onlynews && php artisan cache:clear

# Check PHP version
php -v

# Check Nginx status
sudo systemctl status nginx

# Check disk space
df -h
```

---

## Maintenance

### Update Laravel Application

```bash
cd /var/www/onlynews
git pull  # if using git
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart php7.4-fpm
```

### Backup Database

```bash
# Create backup
cp /var/www/onlynews/database/database.sqlite ~/backup-$(date +%Y%m%d).sqlite

# Restore from backup
cp ~/backup-YYYYMMDD.sqlite /var/www/onlynews/database/database.sqlite
sudo chown www-data:www-data /var/www/onlynews/database/database.sqlite
sudo chmod 664 /var/www/onlynews/database/database.sqlite
```

---

## Success Checklist

- [ ] PHP 7.4 installed and verified
- [ ] Nginx and PHP-FPM installed
- [ ] Composer installed
- [ ] Project files transferred to /var/www/onlynews
- [ ] Dependencies installed via composer
- [ ] .env file configured
- [ ] Database permissions set
- [ ] Nginx site configuration created
- [ ] Services restarted
- [ ] Application accessible via browser
- [ ] API endpoints working
- [ ] Admin panel accessible

---

**Your OnlyNews Laravel 7 application is now running on NanoPi-NEO!** 🎉
