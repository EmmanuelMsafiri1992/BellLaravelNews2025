#!/bin/bash

################################################################################
# BellNews Laravel 7 - Professional Installer Script
# For Nano Pi and Debian/Ubuntu-based systems
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
PHP_VERSION=""  # Will be auto-detected
APP_PORT="8000"
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
    echo "║         BellNews Laravel 7 Installer               ║"
    echo "║         Professional Installation for Nano Pi      ║"
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

# Check system architecture
check_system() {
    print_message "Checking system requirements..."

    # Check if running on ARM (Nano Pi)
    ARCH=$(uname -m)
    print_message "Architecture: $ARCH"

    # Check OS
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        print_message "OS: $NAME $VERSION"
    fi
}

# Install system dependencies
install_dependencies() {
    print_message "Installing system dependencies..."

    # Install basic packages first
    apt-get update
    apt-get install -y software-properties-common curl wget git unzip sqlite3

    # Check if PHP is already installed
    if command -v php &> /dev/null; then
        INSTALLED_PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
        print_message "PHP $INSTALLED_PHP_VERSION is already installed"
        PHP_VERSION=$INSTALLED_PHP_VERSION
    else
        # Add ondrej/php PPA for Ubuntu/Debian systems
        print_message "Adding PHP repository..."
        add-apt-repository -y ppa:ondrej/php 2>/dev/null || {
            print_warning "Could not add PPA, trying to install default PHP..."
        }
        apt-get update

        # Try to install PHP 7.4, fall back to available version
        if apt-cache show php7.4 &> /dev/null; then
            PHP_VERSION="7.4"
        elif apt-cache show php7.3 &> /dev/null; then
            PHP_VERSION="7.3"
        elif apt-cache show php7.2 &> /dev/null; then
            PHP_VERSION="7.2"
        elif apt-cache show php7.0 &> /dev/null; then
            PHP_VERSION="7.0"
        else
            # Use default php package
            PHP_VERSION=""
            print_warning "Using default PHP version"
        fi
    fi

    # Install PHP and extensions
    if [ -n "$PHP_VERSION" ]; then
        print_message "Installing PHP $PHP_VERSION and extensions..."
        apt-get install -y \
            php${PHP_VERSION} \
            php${PHP_VERSION}-cli \
            php${PHP_VERSION}-mbstring \
            php${PHP_VERSION}-xml \
            php${PHP_VERSION}-bcmath \
            php${PHP_VERSION}-curl \
            php${PHP_VERSION}-zip \
            php${PHP_VERSION}-sqlite3 \
            php${PHP_VERSION}-gd \
            php${PHP_VERSION}-json 2>/dev/null || {
                # json extension may not be separate package in newer versions
                print_warning "Some PHP extensions might already be included"
            }
    else
        print_message "Installing default PHP and extensions..."
        apt-get install -y \
            php \
            php-cli \
            php-mbstring \
            php-xml \
            php-bcmath \
            php-curl \
            php-zip \
            php-sqlite3 \
            php-gd \
            php-json 2>/dev/null || true
    fi

    # Verify PHP installation
    if ! command -v php &> /dev/null; then
        print_error "PHP installation failed!"
        exit 1
    fi

    INSTALLED_VERSION=$(php -v | head -n 1)
    print_message "PHP installed: $INSTALLED_VERSION"

    # Install Composer
    if ! command -v composer &> /dev/null; then
        print_message "Installing Composer..."
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
        if [ $? -ne 0 ]; then
            print_error "Composer installation failed!"
            exit 1
        fi
    else
        print_message "Composer already installed: $(composer --version | head -n 1)"
    fi
}

# Create application user
create_app_user() {
    if id "$APP_USER" &>/dev/null; then
        print_message "User $APP_USER already exists"
    else
        print_message "Creating application user: $APP_USER"
        useradd -r -s /bin/bash -d $APP_DIR -m $APP_USER
    fi
}

# Install application
install_application() {
    print_message "Installing application to $APP_DIR..."

    # Create application directory
    mkdir -p $APP_DIR

    # Copy files
    print_message "Copying application files..."
    cp -r . $APP_DIR/

    # Set ownership
    chown -R $APP_USER:$APP_USER $APP_DIR

    # Install Composer dependencies
    print_message "Installing Composer dependencies..."
    cd $APP_DIR
    sudo -u $APP_USER composer install --no-dev --optimize-autoloader

    # Set up environment file
    if [ ! -f $APP_DIR/.env ]; then
        print_message "Creating .env file..."
        sudo -u $APP_USER cp $APP_DIR/.env.example $APP_DIR/.env

        # Generate application key
        print_message "Generating application key..."
        sudo -u $APP_USER php artisan key:generate
    else
        print_message ".env file already exists, skipping..."
    fi

    # Set up database
    print_message "Setting up database..."
    sudo -u $APP_USER mkdir -p $APP_DIR/database
    sudo -u $APP_USER touch $APP_DIR/database/database.sqlite

    # Run migrations
    print_message "Running database migrations..."
    sudo -u $APP_USER php artisan migrate --force

    # Seed database with default data
    print_message "Seeding database..."
    sudo -u $APP_USER php artisan db:seed --force || print_warning "Database seeding failed or already completed"

    # Set permissions
    print_message "Setting permissions..."
    chmod -R 755 $APP_DIR/storage
    chmod -R 755 $APP_DIR/bootstrap/cache
    chmod 664 $APP_DIR/database/database.sqlite

    # Create audio directory
    mkdir -p $APP_DIR/public/audio
    chown -R $APP_USER:$APP_USER $APP_DIR/public/audio
    chmod 755 $APP_DIR/public/audio

    # Optimize Laravel
    print_message "Optimizing application..."
    sudo -u $APP_USER php artisan config:cache
    sudo -u $APP_USER php artisan route:cache
    sudo -u $APP_USER php artisan view:cache
}

# Create systemd service
create_service() {
    print_message "Creating systemd service..."

    cat > /etc/systemd/system/${SERVICE_NAME}.service << EOF
[Unit]
Description=BellNews Laravel Application
After=network.target

[Service]
Type=simple
User=$APP_USER
Group=$APP_USER
WorkingDirectory=$APP_DIR
ExecStart=/usr/bin/php artisan serve --host=0.0.0.0 --port=$APP_PORT
Restart=always
RestartSec=3
StandardOutput=journal
StandardError=journal
SyslogIdentifier=$SERVICE_NAME

# Environment
Environment=APP_ENV=production

[Install]
WantedBy=multi-user.target
EOF

    # Reload systemd
    systemctl daemon-reload

    # Enable service
    print_message "Enabling service to start on boot..."
    systemctl enable ${SERVICE_NAME}.service

    # Start service
    print_message "Starting service..."
    systemctl start ${SERVICE_NAME}.service
}

# Display installation summary
display_summary() {
    echo -e "\n${GREEN}╔════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║           Installation Completed Successfully!     ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════╝${NC}\n"

    echo -e "Application Details:"
    echo -e "  • Installation Directory: ${BLUE}$APP_DIR${NC}"
    echo -e "  • Application URL: ${BLUE}http://$(hostname -I | awk '{print $1}'):$APP_PORT${NC}"
    echo -e "  • Service Name: ${BLUE}$SERVICE_NAME${NC}"
    echo -e "  • Application User: ${BLUE}$APP_USER${NC}"
    echo -e ""
    echo -e "Default Credentials:"
    echo -e "  • Admin Password: ${YELLOW}adminpassword${NC}"
    echo -e "  • Super User Password: ${YELLOW}superpassword${NC}"
    echo -e ""
    echo -e "Useful Commands:"
    echo -e "  • Check status: ${BLUE}sudo systemctl status $SERVICE_NAME${NC}"
    echo -e "  • Stop service: ${BLUE}sudo systemctl stop $SERVICE_NAME${NC}"
    echo -e "  • Start service: ${BLUE}sudo systemctl start $SERVICE_NAME${NC}"
    echo -e "  • Restart service: ${BLUE}sudo systemctl restart $SERVICE_NAME${NC}"
    echo -e "  • View logs: ${BLUE}sudo journalctl -u $SERVICE_NAME -f${NC}"
    echo -e "  • Uninstall: ${BLUE}sudo bash $APP_DIR/uninstall.sh${NC}"
    echo -e ""
    echo -e "${YELLOW}⚠ Important:${NC} Please change the default passwords immediately after login!"
    echo -e ""
}

# Main installation process
main() {
    print_header

    check_root
    check_system

    read -p "This will install BellNews to $APP_DIR. Continue? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_message "Installation cancelled."
        exit 0
    fi

    install_dependencies
    create_app_user
    install_application
    create_service
    display_summary
}

# Run main function
main
