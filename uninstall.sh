#!/bin/bash

################################################################################
# BellNews Laravel 7 - Uninstaller Script
# Removes BellNews from Nano Pi
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="BellNews"
APP_DIR="/opt/bellnews"
SERVICE_NAME="bellnews"
APP_USER="bellnews"

# Print colored message
print_message() {
    echo -e "${GREEN}[BellNews]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_header() {
    echo -e "${BLUE}"
    echo "╔════════════════════════════════════════════════════╗"
    echo "║         BellNews Laravel 7 Uninstaller             ║"
    echo "╚════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

# Check if running as root
check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "Please run as root (use sudo)"
        exit 1
    fi
}

# Uninstall application
uninstall() {
    print_header

    echo -e "${RED}WARNING: This will completely remove BellNews and all its data!${NC}"
    echo -e "The following will be removed:"
    echo -e "  • Application directory: $APP_DIR"
    echo -e "  • System service: $SERVICE_NAME"
    echo -e "  • Application user: $APP_USER"
    echo -e "  • All databases and uploaded files"
    echo -e ""
    read -p "Are you sure you want to continue? (yes/no) " -r
    echo

    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        print_message "Uninstallation cancelled."
        exit 0
    fi

    # Stop service
    if systemctl is-active --quiet ${SERVICE_NAME}.service; then
        print_message "Stopping service..."
        systemctl stop ${SERVICE_NAME}.service
    fi

    # Disable service
    if systemctl is-enabled --quiet ${SERVICE_NAME}.service; then
        print_message "Disabling service..."
        systemctl disable ${SERVICE_NAME}.service
    fi

    # Remove service file
    if [ -f /etc/systemd/system/${SERVICE_NAME}.service ]; then
        print_message "Removing service file..."
        rm /etc/systemd/system/${SERVICE_NAME}.service
        systemctl daemon-reload
    fi

    # Remove application directory
    if [ -d "$APP_DIR" ]; then
        print_message "Removing application directory..."
        rm -rf $APP_DIR
    fi

    # Remove application user
    if id "$APP_USER" &>/dev/null; then
        print_message "Removing application user..."
        userdel -r $APP_USER 2>/dev/null || print_warning "Could not remove user home directory"
    fi

    echo -e "\n${GREEN}╔════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║         BellNews Successfully Uninstalled          ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════╝${NC}\n"

    print_message "BellNews has been completely removed from your system."
    echo -e ""
}

# Run uninstall
check_root
uninstall
