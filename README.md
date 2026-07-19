# Smart Mappia Backend API

Laravel 11.x backend API for Smart Mappia ride-hailing application - **2026 Edition**

## Tech Stack
- **Framework**: Laravel 11.x
- **Database**: PostgreSQL
- **Authentication**: Laravel Sanctum (API tokens)
- **Real-time**: Laravel Broadcasting with Pusher/Redis
- **Cache & Queue**: Redis
- **Server**: Ubuntu 20.04+ with Nginx + PHP-FPM

## Setup Instructions

### Prerequisites
- PHP >= 8.2
- Composer 2.x
- PostgreSQL 14+
- Redis 6+
- Nginx or Apache

### Local Development Installation

1. Install dependencies:
```bash
cd backend
composer install
```

2. Copy environment file:
```bash
cp .env.example .env
```

3. Configure PostgreSQL in `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=smart_mappia
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Run migrations:
```bash
php artisan migrate
```

6. Seed database with sample data (optional):
```bash
php artisan db:seed
```

7. Start development server:
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## Database Structure (2026)

### Core Tables
- **users** - Customer, driver, and admin accounts
- **drivers** - Driver-specific information (vehicle, license, location)
- **rides** - Ride bookings and trip history
- **payments** - Payment transactions and history
- **terminals** - Airport terminal locations
- **districts** - City districts/areas
- **personal_access_tokens** - API authentication tokens

## API Endpoints

### Authentication (`/api/auth`)
- `POST /register` - Register new user
- `POST /login` - Login user (returns API token)
- `POST /logout` - Logout user
- `POST /verify-phone` - Verify phone number with OTP
- `POST /forgot-password` - Request password reset
- `POST /reset-password` - Reset password
- `GET /user` - Get authenticated user profile
- `PUT /user/profile` - Update user profile

### Rides (`/api/rides`)
- `GET /rides` - List user's rides (history)
- `POST /rides` - Create new ride booking
- `GET /rides/{id}` - Get ride details
- `POST /rides/{id}/cancel` - Cancel ride
- `POST /rides/{id}/rate` - Rate driver after ride
- `GET /rides/{id}/track` - Real-time tracking

### Drivers (`/api/drivers`)
- `GET /drivers/nearby` - Get nearby available drivers
- `POST /drivers/register` - Register as driver
- `PUT /drivers/status` - Update driver availability (online/offline)
- `PUT /drivers/location` - Update driver GPS location
- `GET /drivers/earnings` - Get driver earnings summary
- `GET /drivers/trips` - Get driver trip history
- `POST /drivers/rides/{id}/accept` - Accept ride request
- `POST /drivers/rides/{id}/reject` - Reject ride request
- `POST /drivers/rides/{id}/arrive` - Mark as arrived at pickup
- `POST /drivers/rides/{id}/start` - Start the trip
- `POST /drivers/rides/{id}/complete` - Complete the trip

### Locations (`/api/locations`)
- `GET /locations/terminals` - Get airport terminals
- `GET /locations/districts` - Get city districts

### Payments (`/api/payments`)
- `POST /payments/calculate-fare` - Calculate ride fare estimate
- `POST /payments/process` - Process payment
- `GET /payments/history` - Get payment history
- `GET /payments/{id}` - Get payment details

## Deployment to VPS (2026)

### Quick Deployment

1. **Upload the deploy.sh script** to your VPS:
```bash
scp deploy.sh root@your-vps-ip:/root/
```

2. **SSH into your VPS**:
```bash
ssh root@your-vps-ip
```

3. **Run the deployment script**:
```bash
chmod +x /root/deploy.sh
./deploy.sh
```

4. **Upload your Laravel code**:
```bash
# On your local machine
rsync -avz --exclude 'node_modules' --exclude 'vendor' \
  ./backend/ root@your-vps-ip:/var/www/smart-mappia-backend/
```

5. **Complete the setup on VPS**:
```bash
cd /var/www/smart-mappia-backend
composer install --optimize-autoloader --no-dev
cp .env.example .env
nano .env  # Edit your production settings
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan optimize
```

6. **Setup SSL Certificate**:
```bash
certbot --nginx -d api.smartmappia.com
```

### Server Requirements
- **OS**: Ubuntu 20.04+ or CentOS 8+
- **Web Server**: Nginx (recommended) or Apache
- **PHP**: 8.2+
- **Database**: PostgreSQL 14+
- **Cache**: Redis 6+
- **SSL**: Let's Encrypt (free)
- **RAM**: Minimum 2GB (4GB+ recommended)
- **Storage**: 20GB+

### Environment Variables for Production

Important settings in `.env`:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.smartmappia.com

DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=smart_mappia
DB_USERNAME=smart_mappia_user
DB_PASSWORD=your_secure_password

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# Add your payment gateway keys
PAYMENT_GATEWAY=stripe
PAYMENT_API_KEY=your_payment_key

# Add your SMS gateway for OTP
SMS_GATEWAY=twilio
SMS_API_KEY=your_sms_key

# Google Maps for distance calculation
GOOGLE_MAPS_API_KEY=your_google_maps_key
```

## Testing the API

Use Postman or curl:

```bash
# Register a new user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Ibrahim Sab",
    "email": "ibrahim@example.com",
    "phone": "+966501234567",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "ibrahim@example.com",
    "password": "password123"
  }'

# Use the token from login response for authenticated requests
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Monitoring & Maintenance

### View Logs
```bash
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/error.log
```

### Queue Workers
```bash
# Check status
supervisorctl status smart-mappia-worker:*

# Restart workers
supervisorctl restart smart-mappia-worker:*
```

### Database Backup
```bash
# Automated backup script
pg_dump smart_mappia > backup_$(date +%Y%m%d).sql
```

## Security Checklist
- ✅ SSL certificate installed
- ✅ `APP_DEBUG=false` in production
- ✅ Strong database passwords
- ✅ API rate limiting enabled
- ✅ CORS configured properly
- ✅ File upload validation
- ✅ SQL injection protection (Eloquent ORM)
- ✅ XSS protection enabled
- ✅ Regular backups automated

## Support & Documentation
- **Laravel Docs**: https://laravel.com/docs/11.x
- **PostgreSQL Docs**: https://www.postgresql.org/docs/
- **Sanctum Auth**: https://laravel.com/docs/11.x/sanctum

## License
Proprietary - Smart Mappia © 2026
