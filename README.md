# BellNews - Laravel 7 Alarm System

Professional alarm and scheduling system built with Laravel 7, optimized for Nano Pi and ARM-based single-board computers.

## 🎯 Overview

BellNews is a web-based alarm management system that allows you to:
- Schedule alarms by day and time
- Manage audio files for alarm sounds
- Monitor system metrics in real-time
- Configure network and time settings
- Manage users with role-based access control

Originally developed in Python Flask, now professionally ported to Laravel 7 with enhanced features and stability.

## ✨ Features

- **Password-Only Authentication** - Secure login without usernames
- **Alarm Management** - Schedule alarms for specific days and times
- **Sound Library** - Upload and manage audio files (MP3, WAV, etc.)
- **Real-Time Dashboard** - Live system metrics and clock
- **User Management** - Admin and superuser roles
- **License Management** - Feature activation system
- **Network Configuration** - Static/DHCP IP settings
- **Time Settings** - NTP server or manual time configuration
- **Auto-Start Service** - Runs automatically on boot via systemd
- **Browser-Based Alarm Triggering** - JavaScript alarm monitoring system

## 🖥️ System Requirements

- **Hardware**: Nano Pi (any model) or ARM-based SBC
- **OS**: Debian 10+, Ubuntu 18.04+, or compatible
- **RAM**: Minimum 512MB (1GB+ recommended)
- **Storage**: Minimum 500MB free space
- **PHP**: 7.4+ with required extensions
- **Database**: SQLite3

## 🚀 Quick Installation on Nano Pi

```bash
# Clone repository
git clone https://github.com/EmmanuelMsafiri1992/BellLaravelNews2025.git
cd BellLaravelNews2025

# Make installer executable
chmod +x install.sh

# Run installer
sudo bash install.sh
```

Access at: **http://YOUR_NANO_PI_IP:8000**

**Default Credentials:**
- Admin Password: `adminpassword`
- Super User Password: `superpassword`

> ⚠️ **IMPORTANT:** Change default passwords immediately after first login!

## 📖 Full Documentation

See **[INSTALL.md](INSTALL.md)** for:
- Detailed installation steps
- Troubleshooting guide
- Advanced configuration
- Service management

## 🔧 Quick Commands

```bash
# Check service status
sudo systemctl status bellnews

# View logs
sudo journalctl -u bellnews -f

# Restart service
sudo systemctl restart bellnews

# Uninstall
sudo bash uninstall.sh
```

## 🛠️ Technology Stack

- **Backend**: Laravel 7.x (PHP)
- **Database**: SQLite3
- **Frontend**: Vanilla JavaScript (ES6 Modules)
- **Styling**: Tailwind CSS
- **Service**: systemd
- **Web Server**: PHP built-in server

---

**Built for Nano Pi | Powered by Laravel 7 | Auto-Starts on Boot**
