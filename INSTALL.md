# BellNews Laravel 7 - Installation Guide for Nano Pi

Professional installation guide for deploying BellNews on Nano Pi or any Debian/Ubuntu-based ARM system.

## 📋 Table of Contents

- [System Requirements](#system-requirements)
- [Quick Installation](#quick-installation)
- [Detailed Installation Steps](#detailed-installation-steps)
- [Post-Installation](#post-installation)
- [Managing the Application](#managing-the-application)
- [Troubleshooting](#troubleshooting)
- [Uninstallation](#uninstallation)

---

## 🖥️ System Requirements

- **Hardware**: Nano Pi (any model) or ARM-based single-board computer
- **OS**: Debian 10+, Ubuntu 18.04+, or compatible Linux distribution
- **RAM**: Minimum 512MB (1GB+ recommended)
- **Storage**: Minimum 500MB free space
- **Network**: Active internet connection for initial installation
- **Access**: Root/sudo privileges

### Required Software (Auto-installed)
- PHP 7.4+ with extensions (mbstring, xml, bcmath, curl, zip, sqlite3, gd, json)
- Composer
- SQLite3
- Git, Curl, Unzip

---

## 🚀 Quick Installation

### Step 1: Clone Repository on Nano Pi

```bash
# SSH into your Nano Pi
ssh your-user@nano-pi-ip

# Clone the repository
cd ~
git clone https://github.com/EmmanuelMsafiri1992/BellLaravelNews2025.git
cd BellLaravelNews2025
```

### Step 2: Run Installer

```bash
# Make installer executable
chmod +x install.sh

# Run installer as root
sudo bash install.sh
```

### Step 3: Access Application

Once installation is complete, open your browser and navigate to:

```
http://YOUR_NANO_PI_IP:8000
```

**Default Credentials:**
- Admin Password: `adminpassword`
- Super User Password: `superpassword`

**⚠️ IMPORTANT:** Change these passwords immediately after first login!

---

## 📝 Detailed Installation Steps

### 1. Prepare Your Nano Pi

```bash
# Update system packages
sudo apt-get update
sudo apt-get upgrade -y

# Install Git if not already installed
sudo apt-get install -y git
```

### 2. Clone the Repository

```bash
# Navigate to home directory
cd ~

# Clone from GitHub
git clone https://github.com/EmmanuelMsafiri1992/BellLaravelNews2025.git

# Enter directory
cd BellLaravelNews2025

# Verify files
ls -la
```

### 3. Make Installer Executable

```bash
chmod +x install.sh
chmod +x uninstall.sh
```

### 4. Run the Installer

```bash
sudo bash install.sh
```

The installer will:
1. ✅ Check system requirements
2. ✅ Install PHP 7.4+ and required extensions
3. ✅ Install Composer
4. ✅ Create application user `bellnews`
5. ✅ Copy application to `/opt/bellnews`
6. ✅ Install Composer dependencies
7. ✅ Set up SQLite database
8. ✅ Run database migrations
9. ✅ Seed default users
10. ✅ Configure permissions
11. ✅ Create systemd service
12. ✅ Enable auto-start on boot
13. ✅ Start the application

### 5. Installation Progress

You'll see output like:

```
╔════════════════════════════════════════════════════╗
║         BellNews Laravel 7 Installer               ║
║         Professional Installation for Nano Pi      ║
╚════════════════════════════════════════════════════╝

[BellNews] Checking system requirements...
[BellNews] Architecture: aarch64
[BellNews] OS: Ubuntu 20.04.3 LTS
[BellNews] Installing system dependencies...
[BellNews] Installing PHP 7.4 and extensions...
[BellNews] Installing Composer...
[BellNews] Creating application user: bellnews
[BellNews] Installing application to /opt/bellnews...
[BellNews] Installing Composer dependencies...
[BellNews] Creating .env file...
[BellNews] Generating application key...
[BellNews] Setting up database...
[BellNews] Running database migrations...
[BellNews] Seeding database...
[BellNews] Setting permissions...
[BellNews] Optimizing application...
[BellNews] Creating systemd service...
[BellNews] Enabling service to start on boot...
[BellNews] Starting service...

╔════════════════════════════════════════════════════╗
║           Installation Completed Successfully!     ║
╚════════════════════════════════════════════════════╝
```

---

## 🎯 Post-Installation

### 1. Verify Installation

```bash
# Check service status
sudo systemctl status bellnews

# Should show: Active: active (running)
```

### 2. Find Your Nano Pi IP Address

```bash
hostname -I | awk '{print $1}'
```

### 3. Access the Application

Open browser and go to: `http://YOUR_NANO_PI_IP:8000`

### 4. First Login

1. Enter the admin password: `adminpassword`
2. You'll be logged into the dashboard
3. Go to Settings and change your password immediately

### 5. Upload Sound Files

1. Navigate to the "Sounds" tab
2. Upload audio files (MP3, WAV, etc.) for alarms
3. Maximum file size: 2MB per file

### 6. Create Alarms

1. Navigate to the "Alarms" tab
2. Click "Add Alarm"
3. Set day, time, label, and select a sound
4. Alarms will trigger automatically when dashboard is open

---

## 🔧 Managing the Application

### Service Management

```bash
# Check status
sudo systemctl status bellnews

# Start service
sudo systemctl start bellnews

# Stop service
sudo systemctl stop bellnews

# Restart service
sudo systemctl restart bellnews

# Enable auto-start on boot
sudo systemctl enable bellnews

# Disable auto-start
sudo systemctl disable bellnews
```

### View Logs

```bash
# Real-time logs
sudo journalctl -u bellnews -f

# Last 100 lines
sudo journalctl -u bellnews -n 100

# Today's logs
sudo journalctl -u bellnews --since today
```

### Application Files

```bash
# Application directory
cd /opt/bellnews

# View logs
tail -f /opt/bellnews/storage/logs/laravel.log

# Database location
ls -lh /opt/bellnews/database/database.sqlite

# Audio files
ls -lh /opt/bellnews/public/audio/
```

### Update Application

```bash
# Stop service
sudo systemctl stop bellnews

# Navigate to temp directory
cd ~
rm -rf BellLaravelNews2025
git clone https://github.com/EmmanuelMsafiri1992/BellLaravelNews2025.git
cd BellLaravelNews2025

# Backup current database
sudo cp /opt/bellnews/database/database.sqlite ~/database.sqlite.backup

# Copy new files (preserve database and .env)
sudo cp -r app routes resources public config bootstrap /opt/bellnews/
sudo chown -R bellnews:bellnews /opt/bellnews

# Update dependencies
cd /opt/bellnews
sudo -u bellnews composer install --no-dev --optimize-autoloader

# Run migrations (if any new ones)
sudo -u bellnews php artisan migrate --force

# Clear caches
sudo -u bellnews php artisan config:cache
sudo -u bellnews php artisan route:cache
sudo -u bellnews php artisan view:cache

# Restart service
sudo systemctl start bellnews
```

---

## 🔍 Troubleshooting

### Service Won't Start

```bash
# Check service status
sudo systemctl status bellnews

# Check detailed logs
sudo journalctl -u bellnews -n 50 --no-pager

# Check PHP errors
tail -f /opt/bellnews/storage/logs/laravel.log
```

### Can't Access Application

1. **Check if service is running:**
   ```bash
   sudo systemctl status bellnews
   ```

2. **Check if port 8000 is listening:**
   ```bash
   sudo netstat -tulpn | grep 8000
   ```

3. **Check firewall:**
   ```bash
   sudo ufw status
   # If firewall is active, allow port 8000
   sudo ufw allow 8000
   ```

4. **Verify IP address:**
   ```bash
   hostname -I
   ```

### Permission Issues

```bash
# Fix permissions
cd /opt/bellnews
sudo chown -R bellnews:bellnews .
sudo chmod -R 755 storage bootstrap/cache
sudo chmod 664 database/database.sqlite
```

### Database Issues

```bash
# Reset database (WARNING: This will delete all data!)
cd /opt/bellnews
sudo systemctl stop bellnews
sudo -u bellnews rm database/database.sqlite
sudo -u bellnews touch database/database.sqlite
sudo -u bellnews php artisan migrate --force
sudo -u bellnews php artisan db:seed --force
sudo systemctl start bellnews
```

### Memory Issues

If Nano Pi runs out of memory:

```bash
# Check memory usage
free -h

# Add swap file (if not exists)
sudo fallocate -l 1G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# Make swap permanent
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

---

## 🗑️ Uninstallation

To completely remove BellNews from your Nano Pi:

```bash
# Navigate to installation directory
cd /opt/bellnews

# Run uninstaller
sudo bash uninstall.sh
```

Or if source directory is still available:

```bash
cd ~/BellLaravelNews2025
sudo bash uninstall.sh
```

**What gets removed:**
- Application files in `/opt/bellnews`
- Systemd service
- Application user `bellnews`
- All databases and uploaded files

**What is NOT removed:**
- PHP and system dependencies
- Composer

---

## 🌐 Network Configuration

### Access from Other Devices

To access from other devices on the same network:

1. Find Nano Pi IP: `hostname -I`
2. On other device, open: `http://NANO_PI_IP:8000`

### Set Static IP (Recommended)

```bash
# Edit network configuration
sudo nano /etc/netplan/01-netcfg.yaml

# Example configuration:
network:
  version: 2
  renderer: networkd
  ethernets:
    eth0:
      dhcp4: no
      addresses:
        - 192.168.1.100/24
      gateway4: 192.168.1.1
      nameservers:
        addresses: [8.8.8.8, 8.8.4.4]

# Apply changes
sudo netplan apply
```

### Change Application Port

```bash
# Edit service file
sudo nano /etc/systemd/system/bellnews.service

# Change line:
# ExecStart=/usr/bin/php artisan serve --host=0.0.0.0 --port=8000
# To (example port 8080):
# ExecStart=/usr/bin/php artisan serve --host=0.0.0.0 --port=8080

# Reload and restart
sudo systemctl daemon-reload
sudo systemctl restart bellnews
```

---

## 📱 Features

- **Password-Only Authentication** - Secure login without usernames
- **Alarm Management** - Schedule alarms by day and time
- **Sound Library** - Upload and manage alarm sounds
- **User Management** - Admin and superuser roles
- **License Management** - Feature activation system
- **Network Settings** - Configure static/dynamic IP
- **Time Settings** - NTP or manual time configuration
- **Real-Time Dashboard** - Live system metrics
- **Auto-Start** - Runs automatically on boot
- **Systemd Integration** - Professional service management

---

## 🆘 Support

For issues, questions, or feature requests:
- GitHub: https://github.com/EmmanuelMsafiri1992/BellLaravelNews2025/issues

---

## 📄 License

This application is provided as-is for use on authorized systems.

---

**Installed Version:** Laravel 7.x
**Target Platform:** Nano Pi / ARM Linux
**Last Updated:** 2025-11-20
