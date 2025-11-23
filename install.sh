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
SKIP_PHP_VERSION_CHECK=false  # Set to true if user explicitly accepts old PHP

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

# Check PHP version compatibility
check_php_version() {
    if ! command -v php &> /dev/null; then
        return 1  # PHP not installed
    fi

    local PHP_VERSION_FULL=$(php -r "echo PHP_VERSION;")
    local PHP_MAJOR=$(php -r "echo PHP_MAJOR_VERSION;")
    local PHP_MINOR=$(php -r "echo PHP_MINOR_VERSION;")
    local PHP_PATCH=$(php -r "echo PHP_RELEASE_VERSION;")

    print_message "Detected PHP $PHP_VERSION_FULL"

    # Laravel 7 requires PHP >= 7.2.5
    if [ "$PHP_MAJOR" -lt 7 ]; then
        return 1  # Too old
    elif [ "$PHP_MAJOR" -eq 7 ] && [ "$PHP_MINOR" -lt 2 ]; then
        return 1  # Too old
    elif [ "$PHP_MAJOR" -eq 7 ] && [ "$PHP_MINOR" -eq 2 ] && [ "$PHP_PATCH" -lt 5 ]; then
        return 1  # Too old
    fi

    return 0  # Version is compatible
}

# Install system dependencies
install_dependencies() {
    print_message "Installing system dependencies..."

    # Install basic packages first
    apt-get update
    apt-get install -y software-properties-common curl wget git unzip sqlite3

    # Detect current PHP version
    PHP_VERSION_DETECTED=""
    if command -v php &> /dev/null; then
        PHP_VERSION_DETECTED=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    fi

    # Check if PHP is installed and if version is compatible
    if check_php_version; then
        INSTALLED_PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;")
        print_message "PHP $INSTALLED_PHP_VERSION is compatible with Laravel 7 ✓"

        # Install/ensure all required PHP extensions are present
        # Note: php-xml package includes dom, simplexml, xml, xmlreader, xmlwriter extensions
        print_message "Ensuring all required PHP extensions are installed..."
        apt-get install -y \
            php${PHP_VERSION_DETECTED}-cli \
            php${PHP_VERSION_DETECTED}-mbstring \
            php${PHP_VERSION_DETECTED}-xml \
            php${PHP_VERSION_DETECTED}-bcmath \
            php${PHP_VERSION_DETECTED}-curl \
            php${PHP_VERSION_DETECTED}-zip \
            php${PHP_VERSION_DETECTED}-sqlite3 \
            php${PHP_VERSION_DETECTED}-gd \
            php${PHP_VERSION_DETECTED}-tokenizer \
            php${PHP_VERSION_DETECTED}-fileinfo \
            php${PHP_VERSION_DETECTED}-json 2>/dev/null || {
                print_warning "Some PHP extensions might already be included or unavailable"
            }

        # Verify critical extensions are loaded
        print_message "Verifying PHP extensions..."
        php -m | grep -q dom || print_warning "DOM extension may not be loaded - check PHP configuration"
    else
        if command -v php &> /dev/null; then
            OLD_PHP_VERSION=$(php -r "echo PHP_VERSION;")
            print_warning "PHP $OLD_PHP_VERSION is too old for Laravel 7 (requires >= 7.2.5)"
            print_message "Upgrading PHP..."
        else
            print_message "PHP not found, installing..."
        fi

        # Add ondrej/php PPA for Ubuntu/Debian systems
        print_message "Adding PHP repository..."
        LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php 2>/dev/null || {
            print_warning "Could not add PPA, trying default repositories..."
        }
        apt-get update

        # Determine which PHP version to install
        TARGET_PHP_VERSION=""
        for version in 7.4 7.3 7.2; do
            if apt-cache show php${version} &> /dev/null 2>&1; then
                TARGET_PHP_VERSION=$version
                break
            fi
        done

        if [ -z "$TARGET_PHP_VERSION" ]; then
            print_error "Could not find compatible PHP version (>= 7.2.5) in repositories"
            echo ""
            echo -e "${YELLOW}╔════════════════════════════════════════════════════╗${NC}"
            echo -e "${YELLOW}║  Ubuntu 16.04 ARM doesn't have PHP 7.2+ packages  ║${NC}"
            echo -e "${YELLOW}╚════════════════════════════════════════════════════╝${NC}"
            echo ""
            echo -e "Your options:"
            echo -e "  1. ${GREEN}Upgrade to Ubuntu 18.04/20.04${NC} (recommended)"
            echo -e "     Run: ${BLUE}sudo do-release-upgrade${NC}"
            echo -e ""
            echo -e "  2. ${GREEN}Continue with PHP 7.0 anyway${NC} (may have issues)"
            echo -e "     Some Laravel packages might not work"
            echo -e ""
            read -p "Do you want to continue with PHP 7.0 anyway? (yes/no) " -r
            echo
            if [[ $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
                print_warning "Continuing with PHP 7.0 - expect compatibility issues"
                SKIP_PHP_VERSION_CHECK=true
                return 0  # Continue with existing PHP 7.0
            else
                print_message "Installation cancelled. Please upgrade Ubuntu first."
                echo -e ""
                echo -e "To upgrade Ubuntu:"
                echo -e "  ${BLUE}sudo do-release-upgrade${NC}"
                echo -e ""
                exit 0
            fi
        fi

        print_message "Installing PHP $TARGET_PHP_VERSION and extensions..."
        # Note: php-xml includes dom extension
        apt-get install -y \
            php${TARGET_PHP_VERSION} \
            php${TARGET_PHP_VERSION}-cli \
            php${TARGET_PHP_VERSION}-mbstring \
            php${TARGET_PHP_VERSION}-xml \
            php${TARGET_PHP_VERSION}-bcmath \
            php${TARGET_PHP_VERSION}-curl \
            php${TARGET_PHP_VERSION}-zip \
            php${TARGET_PHP_VERSION}-sqlite3 \
            php${TARGET_PHP_VERSION}-gd \
            php${TARGET_PHP_VERSION}-json \
            php${TARGET_PHP_VERSION}-tokenizer \
            php${TARGET_PHP_VERSION}-fileinfo 2>/dev/null || {
                print_warning "Some PHP extensions might already be included"
            }

        # Verify critical extensions after installation
        print_message "Verifying PHP extensions..."
        php -m | grep -q dom || print_warning "DOM extension may not be loaded - check PHP configuration"

        # Set the new PHP version as default if multiple versions exist
        if command -v update-alternatives &> /dev/null; then
            print_message "Setting PHP $TARGET_PHP_VERSION as default..."
            update-alternatives --set php /usr/bin/php${TARGET_PHP_VERSION} 2>/dev/null || true
        fi
    fi

    # Verify PHP installation and version
    if ! command -v php &> /dev/null; then
        print_error "PHP installation failed!"
        exit 1
    fi

    if [ "$SKIP_PHP_VERSION_CHECK" = false ]; then
        if ! check_php_version; then
            print_error "PHP version is still incompatible with Laravel 7"
            print_error "Current: $(php -r 'echo PHP_VERSION;')"
            print_error "Required: >= 7.2.5"
            exit 1
        fi
    else
        print_warning "Skipping PHP version check (user accepted incompatible version)"
    fi

    INSTALLED_VERSION=$(php -v | head -n 1)
    print_message "PHP installed: $INSTALLED_VERSION ✓"

    # Verify required PHP extensions
    print_message "Verifying required PHP extensions..."
    MISSING_EXTENSIONS=()
    REQUIRED_EXTENSIONS=("dom" "mbstring" "xml" "curl" "zip" "sqlite3" "json" "tokenizer" "fileinfo")

    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if ! php -m | grep -qi "^$ext$"; then
            MISSING_EXTENSIONS+=("$ext")
        fi
    done

    if [ ${#MISSING_EXTENSIONS[@]} -gt 0 ]; then
        print_warning "Missing PHP extensions: ${MISSING_EXTENSIONS[*]}"
        print_message "Attempting to fix missing extensions..."

        # Get current PHP version
        CURRENT_PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")

        # Try to install missing extensions
        for ext in "${MISSING_EXTENSIONS[@]}"; do
            apt-get install -y php${CURRENT_PHP_VER}-${ext} 2>/dev/null || {
                print_warning "Could not install php${CURRENT_PHP_VER}-${ext}"
            }
        done

        # Verify again
        STILL_MISSING=()
        for ext in "${MISSING_EXTENSIONS[@]}"; do
            if ! php -m | grep -qi "^$ext$"; then
                STILL_MISSING+=("$ext")
            fi
        done

        if [ ${#STILL_MISSING[@]} -gt 0 ]; then
            print_warning "Still missing: ${STILL_MISSING[*]}"
            print_message "These may be included in other packages or not required"
        else
            print_message "All extensions installed successfully ✓"
        fi
    else
        print_message "All required PHP extensions are installed ✓"
    fi

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

        # Configure for SQLite database
        print_message "Configuring database for SQLite..."
        sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=sqlite/' $APP_DIR/.env
        sed -i 's/DB_HOST=.*/# DB_HOST=127.0.0.1/' $APP_DIR/.env
        sed -i 's/DB_PORT=.*/# DB_PORT=3306/' $APP_DIR/.env
        sed -i 's/DB_DATABASE=.*/# DB_DATABASE=laravel/' $APP_DIR/.env
        sed -i 's/DB_USERNAME=.*/# DB_USERNAME=root/' $APP_DIR/.env
        sed -i 's/DB_PASSWORD=.*/# DB_PASSWORD=/' $APP_DIR/.env

        # Set app to production mode
        sed -i 's/APP_ENV=.*/APP_ENV=production/' $APP_DIR/.env
        sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/' $APP_DIR/.env

        # Generate application key
        print_message "Generating application key..."
        sudo -u $APP_USER php artisan key:generate
    else
        print_message ".env file already exists, skipping..."
    fi

    # Set up database
    print_message "Setting up database..."
    sudo -u $APP_USER mkdir -p $APP_DIR/database

    # Remove any existing database file from repository (it contains development data)
    # We need a fresh database for production installation
    if [ -f $APP_DIR/database/database.sqlite ]; then
        print_message "Removing development database from repository..."
        rm -f $APP_DIR/database/database.sqlite
    fi

    # Create fresh empty database file for production
    print_message "Creating fresh production database..."
    sudo -u $APP_USER touch $APP_DIR/database/database.sqlite

    # Ensure database file has correct permissions
    chmod 664 $APP_DIR/database/database.sqlite
    chown $APP_USER:$APP_USER $APP_DIR/database/database.sqlite

    # Run migrations on fresh database
    print_message "Running database migrations..."
    sudo -u $APP_USER php artisan migrate --force

    # Seed database with default production data
    print_message "Seeding database with default data..."
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

    # Route caching may fail if any routes use closures
    sudo -u $APP_USER php artisan route:cache 2>/dev/null || {
        print_warning "Route caching skipped (routes may contain closures)"
    }

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
