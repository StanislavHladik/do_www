#!/bin/bash

# Xdebug Setup Script for detection_api.php debugging
# Run with: sudo bash setup_xdebug.sh

echo "================================================"
echo "Xdebug Setup for detection_api.php Debugging"
echo "================================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}This script should be run as root (use sudo)${NC}" 
   echo "Some operations require root privileges"
   echo ""
fi

# Detect PHP version
echo "1. Detecting PHP version..."
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo -e "${GREEN}PHP Version: $PHP_VERSION${NC}"
echo ""

# Check if Xdebug is installed
echo "2. Checking Xdebug installation..."
if php -m | grep -q xdebug; then
    XDEBUG_VERSION=$(php -r "echo phpversion('xdebug');")
    echo -e "${GREEN}✓ Xdebug is installed (Version: $XDEBUG_VERSION)${NC}"
else
    echo -e "${YELLOW}✗ Xdebug is NOT installed${NC}"
    echo ""
    echo "Installing Xdebug..."
    apt-get update
    apt-get install -y php${PHP_VERSION}-xdebug
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Xdebug installed successfully${NC}"
    else
        echo -e "${RED}✗ Failed to install Xdebug${NC}"
        exit 1
    fi
fi
echo ""

# Detect web server
echo "3. Detecting web server..."
WEB_SERVER=""
if systemctl is-active --quiet apache2; then
    WEB_SERVER="apache2"
    INI_PATH="/etc/php/${PHP_VERSION}/apache2/conf.d/99-xdebug.ini"
    echo -e "${GREEN}Detected: Apache2${NC}"
elif systemctl is-active --quiet nginx; then
    WEB_SERVER="nginx"
    INI_PATH="/etc/php/${PHP_VERSION}/fpm/conf.d/99-xdebug.ini"
    echo -e "${GREEN}Detected: Nginx with PHP-FPM${NC}"
else
    echo -e "${YELLOW}Could not detect web server (Apache2/Nginx)${NC}"
    INI_PATH="/etc/php/${PHP_VERSION}/cli/conf.d/99-xdebug.ini"
    echo "Will configure for CLI only"
fi
echo ""

# Configure Xdebug
echo "4. Configuring Xdebug..."

# Determine Xdebug version
XDEBUG_MAJOR_VERSION=$(php -r "echo explode('.', phpversion('xdebug'))[0];")

if [ "$XDEBUG_MAJOR_VERSION" = "3" ]; then
    echo "Configuring for Xdebug 3.x"
    cat > "$INI_PATH" << 'EOF'
; Xdebug 3.x Configuration for VS Code
zend_extension=xdebug.so

; Enable debugging
xdebug.mode=debug,develop

; Start debugging on every request
xdebug.start_with_request=yes

; Client settings (VS Code)
xdebug.client_host=127.0.0.1
xdebug.client_port=9003

; Logging
xdebug.log=/tmp/xdebug.log
xdebug.log_level=7

; IDE Key
xdebug.idekey=VSCODE

; Other useful settings
xdebug.max_nesting_level=512
xdebug.var_display_max_depth=10
xdebug.var_display_max_children=256
xdebug.var_display_max_data=1024
EOF
else
    echo "Configuring for Xdebug 2.x"
    cat > "$INI_PATH" << 'EOF'
; Xdebug 2.x Configuration for VS Code
zend_extension=xdebug.so

; Enable remote debugging
xdebug.remote_enable=1
xdebug.remote_autostart=1

; Client settings (VS Code)
xdebug.remote_host=127.0.0.1
xdebug.remote_port=9000

; Logging
xdebug.remote_log=/tmp/xdebug.log

; IDE Key
xdebug.idekey=VSCODE

; Other useful settings
xdebug.max_nesting_level=512
EOF
fi

echo -e "${GREEN}✓ Xdebug configuration written to: $INI_PATH${NC}"
echo ""

# Restart web server
echo "5. Restarting web server..."
if [ "$WEB_SERVER" = "apache2" ]; then
    systemctl restart apache2
    echo -e "${GREEN}✓ Apache2 restarted${NC}"
elif [ "$WEB_SERVER" = "nginx" ]; then
    systemctl restart php${PHP_VERSION}-fpm
    systemctl restart nginx
    echo -e "${GREEN}✓ Nginx and PHP-FPM restarted${NC}"
fi
echo ""

# Verify installation
echo "6. Verifying Xdebug configuration..."
echo ""
php -i | grep -A 5 "xdebug support"
echo ""

# Check if port is available
echo "7. Checking debug port..."
if [ "$XDEBUG_MAJOR_VERSION" = "3" ]; then
    DEBUG_PORT=9003
else
    DEBUG_PORT=9000
fi

if netstat -tuln | grep -q ":$DEBUG_PORT "; then
    echo -e "${YELLOW}⚠ Port $DEBUG_PORT is already in use${NC}"
    echo "This might interfere with Xdebug. Check with: sudo netstat -tlnp | grep $DEBUG_PORT"
else
    echo -e "${GREEN}✓ Port $DEBUG_PORT is available${NC}"
fi
echo ""

# Create test log file with proper permissions
echo "8. Setting up log file..."
touch /tmp/xdebug.log
chmod 666 /tmp/xdebug.log
echo -e "${GREEN}✓ Xdebug log file created: /tmp/xdebug.log${NC}"
echo ""

# Summary
echo "================================================"
echo "Setup Complete!"
echo "================================================"
echo ""
echo "Next steps:"
echo "1. Open VS Code and go to: http://localhost/test_detection_api.html"
echo "2. In VS Code, press F5 to start listening for Xdebug"
echo "3. In the test page, click 'Enable Xdebug Session'"
echo "4. Set breakpoints in detection_api.php"
echo "5. Click any command button in the test page"
echo ""
echo "Useful commands:"
echo "  - View Xdebug log: tail -f /tmp/xdebug.log"
echo "  - Check PHP config: php -i | grep xdebug"
echo "  - Check status: http://localhost/check_xdebug.php"
echo "  - Test API: http://localhost/test_detection_api.html"
echo ""
echo -e "${GREEN}Happy debugging!${NC}"
