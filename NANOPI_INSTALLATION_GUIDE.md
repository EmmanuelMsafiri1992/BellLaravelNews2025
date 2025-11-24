# NanoPi Installation Guide - OnlyBell Timer Application

Complete guide for installing the OnlyBell Timer Application on a fresh NanoPi device.

---

## 📋 Prerequisites

### Hardware Requirements
- **NanoPi** (any model with 512MB+ RAM recommended)
- **SD Card** (16GB+ recommended)
- **Network connection** (Ethernet or WiFi)
- **Power supply** (5V 2A+)

### Software Requirements
- **OS**: Armbian or Ubuntu for ARM
- **Access**: SSH access with root privileges
- **Network**: Internet connection for package installation

---

## 🚀 Installation Steps

### Step 1: Prepare the NanoPi

1. **Flash the OS to SD card** (if fresh device)
   ```bash
   # Use Etcher or similar tool to flash Armbian/Ubuntu ARM image
   # Insert SD card and boot NanoPi
   ```

2. **Connect to NanoPi via SSH**
   ```bash
   ssh root@<nanopi-ip-address>
   # Default password usually: 1234 (Armbian) or your set password
   ```

3. **Update system packages**
   ```bash
   apt update && apt upgrade -y
   ```

---

### Step 2: Install Required Packages

```bash
# Install PHP 7.4 and required extensions
apt install -y php7.4 php7.4-fpm php7.4-cli php7.4-sqlite3 \
  php7.4-curl php7.4-xml php7.4-mbstring php7.4-zip php7.4-gd \
  php7.4-json php7.4-tokenizer php7.4-bcmath php7.4-fileinfo

# Install Nginx web server
apt install -y nginx

# Install Git
apt install -y git

# Install Composer (PHP dependency manager)
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Install SQLite
apt install -y sqlite3

# Install Node.js and npm (for asset compilation - optional, can compile elsewhere)
curl -fsSL https://deb.nodesource.com/setup_16.x | bash -
apt install -y nodejs
```

---

### Step 3: Configure Nginx

1. **Create Nginx site configuration**
   ```bash
   nano /etc/nginx/sites-available/bellapp
   ```

2. **Add the following configuration:**
   ```nginx
   server {
       listen 80;
       server_name _;
       root /var/www/bellapp/public;
       index index.php index.html;

       # Logging
       access_log /var/log/nginx/bellapp-access.log;
       error_log /var/log/nginx/bellapp-error.log;

       # Laravel rewrite rules
       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       # PHP-FPM configuration
       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
           fastcgi_index index.php;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
           fastcgi_read_timeout 300;
       }

       # Deny access to hidden files
       location ~ /\. {
           deny all;
       }

       # Static files caching
       location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
           expires 1y;
           add_header Cache-Control "public, immutable";
       }
   }
   ```

3. **Enable the site and restart Nginx**
   ```bash
   ln -s /etc/nginx/sites-available/bellapp /etc/nginx/sites-enabled/
   rm /etc/nginx/sites-enabled/default  # Remove default site
   nginx -t  # Test configuration
   systemctl restart nginx
   systemctl enable nginx
   ```

---

### Step 4: Configure PHP-FPM

1. **Optimize PHP for NanoPi (limited RAM)**
   ```bash
   nano /etc/php/7.4/fpm/php.ini
   ```

2. **Set recommended values:**
   ```ini
   memory_limit = 256M
   max_execution_time = 60
   max_input_time = 60
   post_max_size = 8M
   upload_max_filesize = 2M
   date.timezone = Africa/Kigali  # Or your timezone
   ```

3. **Configure PHP-FPM pool**
   ```bash
   nano /etc/php/7.4/fpm/pool.d/www.conf
   ```

4. **Adjust for low-resource device:**
   ```ini
   pm = dynamic
   pm.max_children = 10
   pm.start_servers = 2
   pm.min_spare_servers = 1
   pm.max_spare_servers = 4
   pm.max_requests = 500
   ```

5. **Restart PHP-FPM**
   ```bash
   systemctl restart php7.4-fpm
   systemctl enable php7.4-fpm
   ```

---

### Step 5: Clone and Setup Application

1. **Create application directory**
   ```bash
   mkdir -p /var/www
   cd /var/www
   ```

2. **Clone the repository**
   ```bash
   git clone https://github.com/EmmanuelMsafiri1992/BellLaravelNews2025.git bellapp
   cd bellapp
   ```

3. **Install PHP dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. **Create .env file**
   ```bash
   cp .env.example .env
   nano .env
   ```

5. **Configure .env file:**
   ```env
   APP_NAME="OnlyBell Timer"
   APP_ENV=production
   APP_KEY=
   APP_DEBUG=false
   APP_URL=http://192.168.1.75

   LOG_CHANNEL=stack

   DB_CONNECTION=sqlite

   BROADCAST_DRIVER=log
   CACHE_DRIVER=file
   QUEUE_CONNECTION=database
   SESSION_DRIVER=database
   SESSION_LIFETIME=120
   ```

6. **Generate application key**
   ```bash
   php artisan key:generate
   ```

7. **Create SQLite database**
   ```bash
   touch database/database.sqlite
   ```

8. **Run migrations and seeders**
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```

9. **Set proper permissions**
   ```bash
   chown -R www-data:www-data /var/www/bellapp
   chmod -R 775 /var/www/bellapp/storage
   chmod -R 775 /var/www/bellapp/bootstrap/cache
   ```

10. **Clear and cache configuration**
    ```bash
    php artisan config:cache
    php artisan view:clear
    ```

---

### Step 6: (Optional) Compile Frontend Assets

**Note:** You can compile assets on your development machine and just deploy the `public/` folder.

If compiling on NanoPi:
```bash
cd /var/www/bellapp
npm install
npm run production
```

**Recommended:** Compile on development machine, then copy `public/css/` and `public/js/` to NanoPi.

---

### Step 7: Verify Installation

1. **Check PHP-FPM status**
   ```bash
   systemctl status php7.4-fpm
   ```

2. **Check Nginx status**
   ```bash
   systemctl status nginx
   ```

3. **Check file permissions**
   ```bash
   ls -la /var/www/bellapp/storage
   ls -la /var/www/bellapp/bootstrap/cache
   ```

4. **Test the application**
   ```bash
   curl http://localhost/
   ```

5. **Check from browser**
   ```
   http://<nanopi-ip-address>/
   ```

---

## 🔐 Default Credentials

After installation, the application comes with these default users (from seeder):

- **Superadmin**
  - Username: `superadmin`
  - Password: `password`

- **Admin**
  - Username: `admin`
  - Password: `password`

- **Regular User**
  - Username: `user`
  - Password: `password`

**⚠️ IMPORTANT:** Change these passwords immediately after first login!

---

## 🛠️ Post-Installation Configuration

### 1. Configure Network Settings

Log in as superadmin and go to Settings → Network to configure:
- Static or DHCP IP
- Gateway
- DNS servers

### 2. Configure License

Go to License Management to:
- Generate or enter license key
- Activate features
- Manage licensed users

### 3. Add Alarms

Go to Alarms section to:
- Create alarm schedules
- Upload sound files
- Test alarm functionality

---

## 🔄 Updating the Application

To update to the latest version:

```bash
cd /var/www/bellapp

# Backup database
cp database/database.sqlite database/database.sqlite.backup

# Pull latest changes
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Set permissions
chown -R www-data:www-data /var/www/bellapp
chmod -R 775 storage bootstrap/cache

# Restart services
systemctl restart php7.4-fpm
systemctl restart nginx
```

---

## 🐛 Troubleshooting

### Application shows 500 error

1. **Check Laravel logs**
   ```bash
   tail -50 /var/www/bellapp/storage/logs/laravel.log
   ```

2. **Check Nginx logs**
   ```bash
   tail -50 /var/log/nginx/bellapp-error.log
   ```

3. **Check permissions**
   ```bash
   ls -la /var/www/bellapp/storage
   chown -R www-data:www-data /var/www/bellapp
   chmod -R 775 storage bootstrap/cache
   ```

### Cannot write to database

```bash
chmod 664 /var/www/bellapp/database/database.sqlite
chown www-data:www-data /var/www/bellapp/database/database.sqlite
```

### PHP-FPM not running

```bash
systemctl status php7.4-fpm
systemctl restart php7.4-fpm
journalctl -u php7.4-fpm -n 50
```

### Nginx not serving the site

```bash
nginx -t  # Test configuration
systemctl restart nginx
journalctl -u nginx -n 50
```

---

## 📊 Monitoring

### Check system resources

```bash
# Memory usage
free -m

# Disk usage
df -h

# CPU usage
top

# Check running processes
ps aux | grep php
ps aux | grep nginx
```

### Monitor logs in real-time

```bash
# Laravel logs
tail -f /var/www/bellapp/storage/logs/laravel.log

# Nginx access logs
tail -f /var/log/nginx/bellapp-access.log

# Nginx error logs
tail -f /var/log/nginx/bellapp-error.log

# PHP-FPM logs
tail -f /var/log/php7.4-fpm.log
```

---

## 🔒 Security Recommendations

1. **Change default passwords** immediately after installation

2. **Setup firewall**
   ```bash
   apt install -y ufw
   ufw allow 22/tcp   # SSH
   ufw allow 80/tcp   # HTTP
   ufw allow 443/tcp  # HTTPS (if using SSL)
   ufw enable
   ```

3. **Disable root SSH login** (after creating admin user)
   ```bash
   nano /etc/ssh/sshd_config
   # Set: PermitRootLogin no
   systemctl restart sshd
   ```

4. **Setup automatic security updates**
   ```bash
   apt install -y unattended-upgrades
   dpkg-reconfigure --priority=low unattended-upgrades
   ```

5. **Regular backups**
   ```bash
   # Backup database
   cp /var/www/bellapp/database/database.sqlite ~/backups/database-$(date +%Y%m%d).sqlite

   # Backup .env
   cp /var/www/bellapp/.env ~/backups/.env-$(date +%Y%m%d)
   ```

---

## 📞 Support

For issues or questions:
- GitHub Issues: https://github.com/EmmanuelMsafiri1992/BellLaravelNews2025/issues
- Check logs: `/var/www/bellapp/storage/logs/laravel.log`

---

## ✅ Installation Complete!

Your OnlyBell Timer Application is now installed and running on your NanoPi!

Access it at: `http://<nanopi-ip-address>/`

Login with default credentials and configure your system.
