# Peatio Cryptocurrency Exchange - Laravel Edition

A modern, secure, and scalable cryptocurrency exchange platform built with Laravel 10+ and PHP 8.1+. This is a complete rewrite of the original Peatio exchange from Ruby on Rails to Laravel, preserving all core functionality while modernizing the technology stack.

## 🚀 Features

- **Multi-Currency Support**: Trade Bitcoin, Ethereum, Litecoin, and other cryptocurrencies
- **High-Performance Trading Engine**: Built-in order matching engine with support for limit and market orders
- **Secure Wallet Management**: Multi-signature wallets with cold storage support
- **Two-Factor Authentication**: Google Authenticator, SMS, and Email 2FA options
- **KYC/AML Compliance**: Built-in identity verification system
- **Admin Dashboard**: Comprehensive administration panel for managing users, deposits, withdrawals, and trades
- **RESTful API**: Complete API v2 implementation with OAuth2 authentication
- **WebSocket Support**: Real-time market data and order book updates
- **Proof of Solvency**: Cryptographic proof of exchange reserves
- **Multi-Language Support**: Internationalization ready
- **Mobile Responsive**: Works seamlessly on desktop and mobile devices

## 📋 Requirements

- PHP >= 8.1
- Composer
- MySQL >= 5.7 or MariaDB >= 10.3
- Redis >= 5.0
- Node.js >= 16.x (for frontend assets)
- RabbitMQ >= 3.8 (for background jobs)

### Recommended

- Nginx or Apache web server
- Ubuntu 20.04 LTS or higher
- 4GB RAM minimum, 8GB+ recommended
- SSD storage for database

## 🔧 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/Decipheredmedia/PeatioCryptoExchange.git
cd PeatioCryptoExchange
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Environment Configuration

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

Edit `.env` file with your configuration:

```bash
# Application
APP_NAME="Peatio Crypto Exchange"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=peatio_production
DB_USERNAME=peatio_user
DB_PASSWORD=your_secure_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# RabbitMQ
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest

# Cryptocurrency Nodes
BITCOIN_RPC_HOST=127.0.0.1
BITCOIN_RPC_PORT=8332
BITCOIN_RPC_USER=bitcoin
BITCOIN_RPC_PASSWORD=your_bitcoin_rpc_password

ETHEREUM_RPC_HOST=127.0.0.1
ETHEREUM_RPC_PORT=8545

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com

# Two-Factor Authentication
GOOGLE_2FA_ENABLED=true

# SMS (Twilio)
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_FROM=+1234567890

# Trading Configuration
TRADING_FEE_PERCENTAGE=0.2
MINIMUM_TRADE_AMOUNT=0.001
```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Database Setup

Create the database:

```bash
mysql -u root -p
CREATE DATABASE peatio_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'peatio_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON peatio_production.* TO 'peatio_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Run migrations:

```bash
php artisan migrate
```

### 6. Seed Database (Optional)

Seed the database with sample data for development:

```bash
php artisan db:seed
```

### 7. Install Frontend Dependencies

```bash
npm install
npm run build
```

### 8. Storage Permissions

Set proper permissions for storage directories:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 9. Queue Workers

Start the queue worker for background jobs:

```bash
php artisan queue:work --daemon
```

For production, use Supervisor to manage queue workers:

```bash
sudo apt-get install supervisor
```

Create supervisor config at `/etc/supervisor/conf.d/peatio-worker.conf`:

```ini
[program:peatio-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/peatio/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/peatio/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start peatio-worker:*
```

### 10. WebSocket Server (Optional)

For real-time updates, start the WebSocket server:

```bash
php artisan websocket:serve
```

## 🐳 Docker Installation

We provide a Docker setup for easy deployment:

### 1. Install Docker and Docker Compose

```bash
sudo apt-get update
sudo apt-get install docker.io docker-compose
```

### 2. Build and Start Containers

```bash
docker-compose up -d
```

### 3. Run Migrations

```bash
docker-compose exec app php artisan migrate
```

## 📝 Configuration

### Cryptocurrency Nodes

You need to configure RPC access to cryptocurrency nodes:

#### Bitcoin

Install Bitcoin Core:

```bash
sudo add-apt-repository ppa:bitcoin/bitcoin
sudo apt-get update
sudo apt-get install bitcoind
```

Configure `~/.bitcoin/bitcoin.conf`:

```ini
server=1
rpcuser=bitcoin
rpcpassword=your_rpc_password
rpcallowip=127.0.0.1
```

#### Ethereum

Install Geth:

```bash
sudo add-apt-repository -y ppa:ethereum/ethereum
sudo apt-get update
sudo apt-get install ethereum
```

Start Geth with RPC enabled:

```bash
geth --http --http.addr 127.0.0.1 --http.port 8545 --syncmode "fast"
```

### Admin User

Create an admin user:

```bash
php artisan tinker
```

```php
$user = App\Models\User::create([
    'email' => 'admin@example.com',
    'password' => Hash::make('secure_password'),
    'sn' => 'SN' . strtoupper(uniqid()),
    'activated' => true,
]);
$user->assignRole('admin');
```

## 🔐 Security

### SSL/TLS Configuration

Always use HTTPS in production. Configure Nginx with Let's Encrypt:

```bash
sudo apt-get install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

### API Rate Limiting

API rate limiting is enabled by default (60 requests per minute). Adjust in `routes/api.php`.

### Database Backups

Set up automated database backups:

```bash
# Add to crontab
0 2 * * * /usr/bin/mysqldump -u peatio_user -p'password' peatio_production > /backups/peatio_$(date +\%Y\%m\%d).sql
```

## 📊 Database Seeding

### Seed Currencies

```bash
php artisan db:seed --class=CurrencySeeder
```

This will create:
- Bitcoin (BTC)
- Ethereum (ETH)
- Litecoin (LTC)
- USD (Fiat)

### Seed Markets

```bash
php artisan db:seed --class=MarketSeeder
```

This creates trading pairs:
- BTC/USD
- ETH/USD
- LTC/USD
- ETH/BTC

## 🌐 API Documentation

### Authentication

The API uses OAuth2 for authentication. Get your API keys from the user dashboard.

### Endpoints

#### Public Endpoints

- `GET /api/v2/markets` - List all markets
- `GET /api/v2/markets/{id}/tickers` - Get market ticker
- `GET /api/v2/markets/{id}/depth` - Get order book depth
- `GET /api/v2/markets/{id}/trades` - Get recent trades
- `GET /api/v2/currencies` - List all currencies
- `GET /api/v2/timestamp` - Get server timestamp

#### Private Endpoints (Require Authentication)

**Accounts**
- `GET /api/v2/accounts` - List user accounts
- `GET /api/v2/accounts/{currency}` - Get account balance

**Orders**
- `GET /api/v2/orders` - List user orders
- `POST /api/v2/orders` - Create new order
- `GET /api/v2/orders/{id}` - Get order details
- `DELETE /api/v2/orders/{id}` - Cancel order
- `DELETE /api/v2/orders` - Cancel all orders

**Trades**
- `GET /api/v2/trades` - List all trades
- `GET /api/v2/trades/my` - List user trades

**Deposits**
- `GET /api/v2/deposits` - List deposits
- `GET /api/v2/deposit_address/{currency}` - Get deposit address

**Withdrawals**
- `GET /api/v2/withdraws` - List withdrawals
- `POST /api/v2/withdraws` - Create withdrawal

### Example API Calls

#### Create Limit Order

```bash
curl -X POST https://yourdomain.com/api/v2/orders \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "market": "btcusd",
    "side": "buy",
    "volume": "0.1",
    "price": "45000",
    "ord_type": "limit"
  }'
```

#### Get Account Balances

```bash
curl -X GET https://yourdomain.com/api/v2/accounts \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

## 🔧 Maintenance

### Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

### Database Migrations

Always backup before running migrations:

```bash
php artisan migrate
```

Rollback last migration:

```bash
php artisan migrate:rollback
```

## 🧪 Testing

Run PHPUnit tests:

```bash
php artisan test
```

Run specific test:

```bash
php artisan test --filter=TradingEngineTest
```

## 📈 Monitoring

### Laravel Horizon (Queue Monitoring)

Install and configure Horizon:

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

Access dashboard at: `https://yourdomain.com/horizon`

### Application Logging

Logs are stored in `storage/logs/laravel.log`

Monitor in real-time:

```bash
tail -f storage/logs/laravel.log
```

## 🚀 Deployment

### Nginx Configuration

Create `/etc/nginx/sites-available/peatio`:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/peatio/public;

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
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/peatio /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## 🤝 Contributing

Contributions are welcome! Please read our contributing guidelines before submitting PRs.

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

- **Documentation**: https://docs.peatio.com
- **Issues**: https://github.com/Decipheredmedia/PeatioCryptoExchange/issues
- **Email**: support@peatio.com
- **Community**: https://community.peatio.com

## ⚠️ Disclaimer

Running a cryptocurrency exchange involves significant regulatory, security, and financial risks. This software is provided "as is" without warranty of any kind. Users are responsible for:

- Compliance with local regulations and laws
- Security of funds and user data
- Regular security audits
- Proper server configuration and maintenance
- Understanding cryptocurrency and exchange operations

**DO NOT** use this in production without:
- Professional security audit
- Legal consultation
- Proper insurance
- Understanding of your responsibilities as an exchange operator

## 🔄 Upgrade from Peatio v1/v2

If you're upgrading from the original Peatio Ruby on Rails version, please follow our migration guide at `docs/MIGRATION.md`.

## 📚 Additional Resources

- [API Documentation](docs/API.md)
- [Trading Engine Architecture](docs/TRADING_ENGINE.md)
- [Security Best Practices](docs/SECURITY.md)
- [Troubleshooting Guide](docs/TROUBLESHOOTING.md)

---

**Built with ❤️ by the Peatio Community**
