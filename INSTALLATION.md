# 🚀 Quick Start Installation Guide - Smart Mappia Backend (2026)

## For Windows Development

Since you're on Windows, here's how to set up the Laravel backend:

### Step 1: Install Prerequisites

1. **Install PHP 8.2+**
   - Download from: https://windows.php.net/download/
   - Or use XAMPP/WAMP/Laragon

2. **Install Composer**
   - Download from: https://getcomposer.org/download/
   - Run the Windows installer

3. **Install PostgreSQL**
   - Download from: https://www.postgresql.org/download/windows/
   - Remember your postgres password!

4. **Install Redis (Optional but recommended)**
   - Download from: https://github.com/microsoftarchive/redis/releases
   - Or use WSL2 with Ubuntu

### Step 2: Setup Laravel

```bash
# Navigate to backend folder
cd "C:\Users\6\Desktop\Project\Smart Mapia\backend"

# Install dependencies
composer install

# Copy environment file
copy .env.example .env

# Generate application key
php artisan key:generate
```

### Step 3: Configure Database

1. Open PostgreSQL and create database:
```sql
CREATE DATABASE smart_mappia;
```

2. Edit `.env` file:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=smart_mappia
DB_USERNAME=postgres
DB_PASSWORD=your_postgres_password
```

### Step 4: Run Migrations

```bash
php artisan migrate
```

### Step 5: Start Development Server

```bash
php artisan serve
```

Your API will be running at: http://localhost:8000

## For VPS Deployment (Production)

### Step 1: Prepare Your VPS

1. Get a VPS (DigitalOcean, Linode, AWS, Vultr, etc.)
2. Choose Ubuntu 20.04 or 22.04
3. Minimum 2GB RAM recommended

### Step 2: Upload and Run Deploy Script

```bash
# On your local machine (upload the deploy script)
scp deploy.sh root@YOUR_VPS_IP:/root/

# SSH into VPS
ssh root@YOUR_VPS_IP

# Make script executable and run
chmod +x /root/deploy.sh
./deploy.sh
```

### Step 3: Upload Laravel Code

```bash
# From your Windows machine using WinSCP or rsync
# Install Git Bash or WSL first, then:
rsync -avz --exclude 'node_modules' --exclude 'vendor' \
  "C:/Users/6/Desktop/Project/Smart Mapia/backend/" \
  root@YOUR_VPS_IP:/var/www/smart-mappia-backend/
```

### Step 4: Complete Setup on VPS

```bash
cd /var/www/smart-mappia-backend
composer install --optimize-autoloader --no-dev
cp .env.example .env
nano .env  # Configure your production settings
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

### Step 5: Setup Domain & SSL

1. Point your domain to VPS IP:
   - A Record: api.smartmappia.com → YOUR_VPS_IP

2. Install SSL certificate:
```bash
certbot --nginx -d api.smartmappia.com
```

## Testing the API

### Test Registration

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d "{\"name\":\"Test User\",\"email\":\"test@example.com\",\"phone\":\"+966501234567\",\"password\":\"password123\",\"password_confirmation\":\"password123\"}"
```

### Test Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"test@example.com\",\"password\":\"password123\"}"
```

## Next Steps

1. **Connect Flutter App** to your API
   - Update API base URL in Flutter app
   - Test authentication flow
   - Test ride booking flow

2. **Add Real Payment Gateway**
   - Stripe, PayPal, or local Saudi payment provider
   - Configure in `.env`

3. **Add SMS Gateway**
   - Twilio, Nexmo, or local SMS provider
   - For OTP verification

4. **Add Google Maps API**
   - For distance calculation
   - For route optimization

5. **Setup Push Notifications**
   - Firebase Cloud Messaging
   - For real-time ride updates

## Common Issues

### Issue: Composer not found
**Solution**: Add Composer to PATH or use full path

### Issue: PostgreSQL connection failed
**Solution**: Check if PostgreSQL service is running
```bash
# Windows
net start postgresql-x64-14
```

### Issue: Port 8000 already in use
**Solution**: Use different port
```bash
php artisan serve --port=8001
```

## Need Help?

- Laravel Documentation: https://laravel.com/docs/11.x
- PostgreSQL Docs: https://www.postgresql.org/docs/
- Stack Overflow: https://stackoverflow.com/questions/tagged/laravel

---

**Smart Mappia Backend API © 2026**
