# OnlyNews Laravel 7 - NanoPi-NEO Edition

## 🎯 Project Overview

This is **OnlyNews** converted to **Laravel 7** specifically for deployment on **NanoPi-NEO** running **Ubuntu 16.04**.

### Why Laravel 7?
- Ubuntu 16.04 default PHP (7.0) doesn't support Laravel 8
- Laravel 7 works perfectly with PHP 7.4 (installable via PPA)
- Lighter and faster on embedded hardware
- Fully compatible with the original Laravel 8 features

---

## 📦 What's Included

### Deployment Files (New)
- **`NANOPI_DEPLOYMENT_GUIDE.md`** - Complete step-by-step deployment guide
- **`deploy_to_nanopi.sh`** - Automated deployment script for NanoPi
- **`nanopi_nginx.conf`** - Optimized Nginx configuration for embedded device

### Application Files (Converted from Laravel 8)
- ✅ All routes converted to Laravel 7 syntax
- ✅ All migrations converted to Laravel 7 syntax
- ✅ All models updated (removed HasFactory)
- ✅ Vue 3 → Vue 2 conversion completed
- ✅ Vite → Laravel Mix conversion completed
- ✅ Frontend assets compiled and ready
- ✅ Database file copied from original project

---

## 🚀 Quick Start for NanoPi Deployment

### 1. On NanoPi-NEO

```bash
# Install PHP 7.4
sudo add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get install -y php7.4 php7.4-fpm php7.4-sqlite3 php7.4-mbstring \
    php7.4-xml php7.4-curl php7.4-zip php7.4-gd php7.4-bcmath nginx

# Install Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Create project directory
sudo mkdir -p /var/www/onlynews
```

### 2. Transfer Files from Windows

```bash
# On Windows (Git Bash)
cd /c/laragon/www/OnlyNews2025_Laravel7
tar --exclude='node_modules' --exclude='vendor' --exclude='.git' -czf onlynews.tar.gz .
scp onlynews.tar.gz root@YOUR_NANOPI_IP:/tmp/

# On NanoPi
cd /var/www/onlynews
tar -xzf /tmp/onlynews.tar.gz
```

### 3. Deploy

```bash
cd /var/www/onlynews
chmod +x deploy_to_nanopi.sh
./deploy_to_nanopi.sh
```

### 4. Configure Nginx

```bash
sudo cp nanopi_nginx.conf /etc/nginx/sites-available/onlynews
# Edit and replace YOUR_NANOPI_IP with actual IP
sudo nano /etc/nginx/sites-available/onlynews

sudo ln -s /etc/nginx/sites-available/onlynews /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx php7.4-fpm
```

### 5. Access Your App

```
http://YOUR_NANOPI_IP
```

---

## 📁 Project Structure

```
OnlyNews2025_Laravel7/
├── NANOPI_DEPLOYMENT_GUIDE.md    # Detailed deployment instructions
├── README_DEPLOYMENT.md           # This file
├── deploy_to_nanopi.sh            # Deployment automation script
├── nanopi_nginx.conf              # Nginx server configuration
├── app/                           # Laravel application code
├── database/
│   └── database.sqlite            # Pre-populated database
├── public/
│   ├── js/app.js                  # Compiled Vue 2 application
│   ├── css/app.css                # Compiled styles
│   └── css/tailwind.css           # Compiled Tailwind CSS
├── resources/
│   ├── js/                        # Vue 2 source files
│   ├── views/                     # Blade templates
│   └── lang/                      # Translations (EN, HE, AR)
├── routes/
│   ├── web.php                    # Web routes (Laravel 7 syntax)
│   └── api.php                    # API routes (Laravel 7 syntax)
└── webpack.mix.js                 # Laravel Mix configuration
```

---

## ⚙️ Technical Details

### System Requirements (NanoPi)
- **OS**: Ubuntu 16.04+ (tested on 16.04.2 LTS Xenial)
- **PHP**: 7.4 (via ondrej/php PPA)
- **Web Server**: Nginx + PHP-FPM
- **Database**: SQLite
- **RAM**: Minimum 256MB (512MB recommended)
- **Storage**: 500MB free space minimum

### Laravel 7 Features Used
- Routing (web + API)
- Eloquent ORM
- Blade Templates
- Vue 2 + Vue Router
- Multi-language support (i18n)
- Image uploads (Intervention/Image)
- SQLite database
- Laravel Mix for asset compilation

### Frontend Stack
- Vue 2.6.14
- Vue Router 3.5.4
- Vue i18n 8.28.0
- Tailwind CSS 2.2.17 (PostCSS 7 compat)
- Bootstrap 5.2.3
- Laravel Mix 5.0.9

---

## 🔧 Development vs Production

### Development (Windows - Laragon)
The application currently runs on PHP 8.3.19 but encounters deprecation warnings because Laravel 7 was designed for PHP 7.2-8.0.

**To develop on Windows**: Switch to PHP 7.4 in Laragon or ignore the warnings (they don't affect functionality).

### Production (NanoPi)
Use PHP 7.4 on the NanoPi for optimal compatibility.

---

## 🎨 Features

- ✅ Multi-language news management (English, Hebrew, Arabic)
- ✅ RTL support for Hebrew and Arabic
- ✅ Image upload and management
- ✅ Category-based news organization
- ✅ Screen/TV display mode
- ✅ User authentication and authorization
- ✅ Admin panel with role-based access
- ✅ License system with expiration
- ✅ API endpoints for mobile/external access
- ✅ Responsive design (works on all devices)

---

## 📚 Documentation

1. **NANOPI_DEPLOYMENT_GUIDE.md** - Complete deployment walkthrough
2. **deploy_to_nanopi.sh** - Comments explain each deployment step
3. **nanopi_nginx.conf** - Server configuration with performance optimizations

---

## 🐛 Troubleshooting

### Application doesn't load
```bash
# Check Nginx logs
sudo tail -f /var/log/nginx/onlynews_error.log

# Check PHP-FPM
sudo systemctl status php7.4-fpm

# Check permissions
sudo chown -R www-data:www-data /var/www/onlynews
sudo chmod 664 /var/www/onlynews/database/database.sqlite
```

### Images don't display
```bash
# Create storage link
cd /var/www/onlynews
php artisan storage:link
sudo chown -R www-data:www-data storage public/storage
```

### Performance is slow
```bash
# Clear and cache routes/config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize Composer autoloader
composer dump-autoload --optimize
```

---

## 🔒 Security

⚠️ **Ubuntu 16.04 is EOL (End of Life)** - Consider upgrading to Ubuntu 20.04/22.04

Minimum security measures:
- Change default SSH port
- Disable SSH password authentication (use keys)
- Enable firewall: `sudo ufw allow 80/tcp && sudo ufw enable`
- Keep PHP updated via ondrej/php PPA
- Regular database backups

---

## 📞 Support

For issues:
1. Check the logs (see Troubleshooting section)
2. Review NANOPI_DEPLOYMENT_GUIDE.md
3. Verify all steps in the deployment checklist

---

## 📝 Conversion History

**Original Project**: OnlyNews2025 (Laravel 8 + Vue 3 + Vite)
**Converted To**: OnlyNews2025_Laravel7 (Laravel 7 + Vue 2 + Laravel Mix)
**Reason**: Deployment on NanoPi-NEO with Ubuntu 16.04 (supports PHP 7.4 max)
**Date**: November 2025

### Major Changes
1. Laravel 8 → Laravel 7 (routes, migrations, models)
2. Vue 3 → Vue 2 (components, initialization)
3. Vite → Laravel Mix (build system)
4. Webpack 5 → Webpack 4
5. Tailwind 3 → Tailwind 2 (PostCSS 7 compat)
6. Added PHP 8.3 deprecation suppression for local development

---

## ✅ Deployment Checklist

- [ ] PHP 7.4 installed on NanoPi
- [ ] Nginx and PHP-FPM installed
- [ ] Composer installed
- [ ] Files transferred to /var/www/onlynews
- [ ] Composer dependencies installed
- [ ] .env file configured
- [ ] Database permissions set correctly
- [ ] Nginx configured and restarted
- [ ] Application accessible at http://NANOPI_IP
- [ ] Admin login works
- [ ] Images upload successfully
- [ ] Multi-language switching works
- [ ] API endpoints respond correctly

---

**Ready to deploy? Start with NANOPI_DEPLOYMENT_GUIDE.md** 🚀
