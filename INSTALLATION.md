# Peatio Crypto Exchange - Quick Start Guide

## Prerequisites

Before you begin, ensure you have:
- **PHP 8.1+** installed
- **Composer** installed
- **MySQL 5.7+** or MariaDB 10.3+
- **Redis 5.0+** installed and running
- **Node.js 16+** and npm installed
- **Git** installed

## Quick Installation (Development)

### 1. Clone and Setup

```bash
# Clone the repository
git clone https://github.com/Decipheredmedia/PeatioCryptoExchange.git
cd PeatioCryptoExchange

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 2. Configure Environment

Edit `.env` file:

```bash
APP_NAME="Peatio Crypto Exchange"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=peatio_dev
DB_USERNAME=root
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### 3. Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE peatio_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate

# Seed database with sample data
php artisan db:seed
```

### 4. Create Admin User

```bash
php artisan admin:create admin@example.com SecurePassword123
```

### 5. Start Development Server

```bash
# Terminal 1: Laravel development server
php artisan serve

# Terminal 2: Queue worker
php artisan queue:work

# Terminal 3: Frontend assets (if using Vite)
npm run dev
```

Visit: http://localhost:8000

## Docker Installation

### Using Docker Compose

```bash
# Start all services
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Seed database
docker-compose exec app php artisan db:seed

# Create admin user
docker-compose exec app php artisan admin:create admin@example.com SecurePassword123
```

Access the application at http://localhost

## Production Deployment

### 1. Environment Configuration

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Use strong passwords in production!
DB_PASSWORD=your_strong_database_password
REDIS_PASSWORD=your_redis_password
```

### 2. Optimize Application

```bash
composer install --optimize-autoloader --no-dev
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Set Permissions

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 4. Setup Queue Workers with Supervisor

Create `/etc/supervisor/conf.d/peatio-worker.conf`:

```ini
[program:peatio-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/peatio/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/peatio/storage/logs/worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start peatio-worker:*
```

### 5. Configure Cron

Add to crontab:

```bash
* * * * * cd /var/www/peatio && php artisan schedule:run >> /dev/null 2>&1
```

### 6. Setup SSL with Let's Encrypt

```bash
sudo apt-get install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

## Common Commands

### Artisan Commands

```bash
# Process deposits
php artisan deposits:process

# Process withdrawals
php artisan withdrawals:process

# Create admin user
php artisan admin:create email@example.com password

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run queue worker
php artisan queue:work

# Check queue status
php artisan queue:failed
```

### Database Commands

```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Seed database
php artisan db:seed --class=CurrencySeeder
php artisan db:seed --class=MarketSeeder
```

## API Quick Start

### Authentication

```bash
# Register user
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# Get access token (configure OAuth2 first)
```

### API Examples

```bash
# Get all markets
curl http://localhost:8000/api/v2/markets

# Get market depth
curl http://localhost:8000/api/v2/markets/1/depth

# Create order (requires authentication)
curl -X POST http://localhost:8000/api/v2/orders \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "market": "btcusd",
    "side": "buy",
    "volume": "0.1",
    "price": "50000",
    "ord_type": "limit"
  }'
```

## Troubleshooting

### Laravel Not Finding Classes

```bash
composer dump-autoload
```

### Permission Errors

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Database Connection Errors

Check `.env` settings and ensure MySQL is running:

```bash
sudo systemctl status mysql
```

### Queue Not Processing

Check if queue worker is running:

```bash
ps aux | grep queue:work
```

Restart queue workers:

```bash
php artisan queue:restart
```

## Next Steps

1. **Configure Cryptocurrency Nodes**: Set up Bitcoin, Ethereum, and other cryptocurrency node connections in `.env`
2. **Enable 2FA**: Configure Google 2FA and Twilio for SMS
3. **Setup Email**: Configure SMTP settings for email notifications
4. **Add Currencies**: Use seeders or admin panel to add more cryptocurrencies
5. **Create Markets**: Configure trading pairs
6. **KYC Setup**: Configure KYC verification requirements
7. **Frontend**: Build custom frontend or use provided templates
8. **Testing**: Run automated tests before going live

## Support

- **Documentation**: Full documentation in `/docs` directory
- **Issues**: https://github.com/Decipheredmedia/PeatioCryptoExchange/issues
- **Email**: support@peatio.com

## Security Checklist

Before going to production:

- [ ] Change all default passwords
- [ ] Enable SSL/HTTPS
- [ ] Configure firewall rules
- [ ] Set up database backups
- [ ] Enable 2FA for admin accounts
- [ ] Review API rate limits
- [ ] Set proper file permissions
- [ ] Configure logging and monitoring
- [ ] Get professional security audit
- [ ] Review compliance requirements

---

**Happy Trading! 🚀**
