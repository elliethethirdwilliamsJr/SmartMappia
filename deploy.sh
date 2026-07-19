#!/bin/bash

###############################################################################
# Smart Mappia Laravel Backend Deployment Script
# For Ubuntu 20.04+ / CentOS 8+ VPS
# Year: 2026
###############################################################################

echo "🚀 Smart Mappia Backend Deployment Started"
echo "============================================"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/smart-mappia-backend"
NGINX_CONF="/etc/nginx/sites-available/smart-mappia"
PHP_VERSION="8.2"

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}➤ $1${NC}"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    print_error "Please run as root or with sudo"
    exit 1
fi

# Step 1: Update system packages
print_info "Updating system packages..."
apt-get update && apt-get upgrade -y
print_success "System packages updated"

# Step 2: Install PHP 8.2 and extensions
print_info "Installing PHP $PHP_VERSION and extensions..."
add-apt-repository ppa:ondrej/php -y
apt-get update
apt-get install -y \
    php${PHP_VERSION} \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-pgsql \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-redis
print_success "PHP installed"

# Step 3: Install PostgreSQL
print_info "Installing PostgreSQL..."
apt-get install -y postgresql postgresql-contrib
systemctl start postgresql
systemctl enable postgresql
print_success "PostgreSQL installed"

# Step 4: Install Nginx
print_info "Installing Nginx..."
apt-get install -y nginx
systemctl start nginx
systemctl enable nginx
print_success "Nginx installed"

# Step 5: Install Composer
print_info "Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
print_success "Composer installed"

# Step 6: Install Redis (optional but recommended)
print_info "Installing Redis..."
apt-get install -y redis-server
systemctl start redis-server
systemctl enable redis-server
print_success "Redis installed"

# Step 7: Create database and user
print_info "Setting up PostgreSQL database..."
sudo -u postgres psql <<EOF
CREATE DATABASE smart_mappia;
CREATE USER smart_mappia_user WITH ENCRYPTED PASSWORD 'your_secure_password_here';
GRANT ALL PRIVILEGES ON DATABASE smart_mappia TO smart_mappia_user;
ALTER DATABASE smart_mappia OWNER TO smart_mappia_user;
\q
EOF
print_success "Database created"

# Step 8: Create application directory
print_info "Creating application directory..."
mkdir -p $APP_DIR
print_success "Application directory created"

# Step 9: Deploy Laravel application
print_info "Please upload your Laravel application to $APP_DIR"
print_info "Then run: cd $APP_DIR && composer install --optimize-autoloader --no-dev"

# Step 10: Configure Nginx
print_info "Configuring Nginx..."
cat > $NGINX_CONF <<'NGINX_CONFIG'
server {
    listen 80;
    listen [::]:80;
    server_name your_domain.com www.your_domain.com;
    root /var/www/smart-mappia-backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Rate limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;
    location /api/ {
        limit_req zone=api burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
}
NGINX_CONFIG

ln -s $NGINX_CONF /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
print_success "Nginx configured"

# Step 11: Set permissions
print_info "Setting permissions..."
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/storage
chmod -R 775 $APP_DIR/bootstrap/cache
print_success "Permissions set"

# Step 12: Install SSL Certificate (Let's Encrypt)
print_info "Installing Certbot for SSL..."
apt-get install -y certbot python3-certbot-nginx
print_info "Run: certbot --nginx -d your_domain.com -d www.your_domain.com"

# Step 13: Setup supervisor for queue workers
print_info "Installing Supervisor for queue workers..."
apt-get install -y supervisor

cat > /etc/supervisor/conf.d/smart-mappia-worker.conf <<'SUPERVISOR_CONFIG'
[program:smart-mappia-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/smart-mappia-backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/smart-mappia-backend/storage/logs/worker.log
stopwaitsecs=3600
SUPERVISOR_CONFIG

supervisorctl reread
supervisorctl update
supervisorctl start smart-mappia-worker:*
print_success "Supervisor configured"

# Final instructions
echo ""
echo "============================================"
echo "✅ Base server setup complete!"
echo "============================================"
echo ""
print_info "Next steps:"
echo "1. Upload your Laravel code to $APP_DIR"
echo "2. cd $APP_DIR"
echo "3. Copy .env.example to .env and configure:"
echo "   - Database credentials"
echo "   - APP_KEY (run: php artisan key:generate)"
echo "   - APP_ENV=production"
echo "   - APP_DEBUG=false"
echo "4. Run: composer install --optimize-autoloader --no-dev"
echo "5. Run: php artisan migrate --force"
echo "6. Run: php artisan db:seed"
echo "7. Run: php artisan config:cache"
echo "8. Run: php artisan route:cache"
echo "9. Run: php artisan view:cache"
echo "10. Setup SSL: certbot --nginx -d your_domain.com"
echo ""
print_success "Deployment script completed! 🎉"
