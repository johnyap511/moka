# MOKA v2 — Complete Deployment Guide
> Ubuntu 22.04 LTS · Nginx · PHP 8.1 · MySQL 8 · Laravel

---

## WHAT'S IN THIS PACKAGE

```
moka-v2/
├── public/
│   └── moka-v2/
│       ├── css/
│       │   ├── design-system.css   ← Core design tokens & utilities
│       │   ├── header.css          ← Navigation styles
│       │   ├── home.css            ← Homepage + footer styles
│       │   ├── listings.css        ← Search, cards, property detail, booking widget
│       │   └── pages.css           ← Auth modal, about, service, dashboard, estimate
│       └── js/
│           └── moka.js             ← All interactivity (header, drawer, reveals, forms)
├── resources/views/
│   └── v2/
│       ├── partial/
│       │   ├── layout.blade.php    ← Master layout (replaces layout23.blade.php)
│       │   ├── header.blade.php    ← Header + mobile drawer
│       │   ├── footer.blade.php    ← Full footer
│       │   └── auth-modal.blade.php ← Login + Sign Up modal
│       └── pages/
│           ├── home.blade.php           ← Homepage
│           ├── about.blade.php          ← About page
│           ├── service.blade.php        ← Services page
│           ├── estimate.blade.php       ← Get estimate page
│           ├── property-search.blade.php ← Search results
│           └── property-detail.blade.php ← Property detail + booking
└── ROUTE_CONTROLLER_CHANGES.php    ← Exact code changes for routes & controller
```

---

## STEP 1 — Upload Files to Server

### 1a. From your local machine, upload the package:
```bash
scp -r moka-v2/ root@YOUR_SERVER_IP:/tmp/moka-v2-upload/
```

### 1b. SSH into your server:
```bash
ssh root@YOUR_SERVER_IP
```

---

## STEP 2 — Copy Files into Your Laravel Project

Assuming your Laravel project lives at `/var/www/moka`:

```bash
# Copy public assets (CSS + JS)
cp -r /tmp/moka-v2-upload/public/moka-v2  /var/www/moka/public/

# Copy blade views
cp -r /tmp/moka-v2-upload/resources/views/v2  /var/www/moka/resources/views/

# Verify structure
ls /var/www/moka/public/moka-v2/css/
ls /var/www/moka/resources/views/v2/
```

---

## STEP 3 — Update Routes (routes/web.php)

Open your routes file:
```bash
nano /var/www/moka/routes/web.php
```

Inside the `Route::group(['middleware' => 'lang'])` block, ensure these routes exist:
```php
Route::get('/homepage',     'Auth\WebController@newHomeNew');
Route::get('/about',        'Auth\WebController@HomeAbout');
Route::get('/service',      'Auth\WebController@HomeService');
Route::get('/get/estimate', 'Auth\WebController@getEstimate');
```
> The `/get/estimate` route may be new — add it if it doesn't exist.

---

## STEP 4 — Update WebController Views

Open your controller:
```bash
nano /var/www/moka/app/Http/Controllers/Auth/WebController.php
```

Update these methods to return v2 views:

```php
public function newHomeNew()
{
    return view('v2.pages.home');
}

public function HomeAbout()
{
    return view('v2.pages.about');
}

public function HomeService()
{
    return view('v2.pages.service');
}

// ADD this new method if it doesn't exist:
public function getEstimate()
{
    return view('v2.pages.estimate');
}
```

For search and detail, change only the view name, keep all existing logic:
```php
// In locationSearch() — change the last line to:
return view('v2.pages.property-search', compact(
    'listings', 'selectedZone', 'checkIn', 'checkOut', 'guestNo', 'children'
));

// In propertyDetail() — change the last line to:
return view('v2.pages.property-detail', compact('listing'));
```

---

## STEP 5 — Fix File Permissions

```bash
cd /var/www/moka

# Set ownership
chown -R www-data:www-data .

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Storage & cache must be writable
chmod -R 775 storage bootstrap/cache
```

---

## STEP 6 — Clear Laravel Caches

```bash
cd /var/www/moka

php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optional: re-cache for production performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## STEP 7 — Verify Nginx Config

```bash
cat /etc/nginx/sites-available/moka
```

Your server block should look like:
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/moka/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    # Gzip compression (performance)
    gzip on;
    gzip_types text/plain text/css application/javascript application/json image/svg+xml;
    gzip_min_length 1000;

    # Static asset caching (1 year)
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

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

If you make changes:
```bash
nginx -t                  # Test config
systemctl reload nginx    # Apply
```

---

## STEP 8 — Install SSL (HTTPS)

```bash
apt install certbot python3-certbot-nginx -y
certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

Certbot auto-renews. Verify:
```bash
certbot renew --dry-run
```

---

## STEP 9 — Test the Application

Visit these URLs and verify they load the new v2 design:

| URL | Expected View |
|-----|--------------|
| `https://yourdomain.com/homepage` | Homepage (hero + estimate card) |
| `https://yourdomain.com/about` | About page |
| `https://yourdomain.com/service` | Services page |
| `https://yourdomain.com/get/estimate` | Full-page estimate form |
| `https://yourdomain.com/location/search` | Property search results |
| `https://yourdomain.com/listing/{key}` | Property detail + booking widget |

---

## STEP 10 — .env Production Checklist

```bash
nano /var/www/moka/.env
```

Ensure these are set correctly:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=moka_db
DB_USERNAME=moka_user
DB_PASSWORD=your_secure_password

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-email-password
MAIL_FROM_ADDRESS=hello@homemoka.com
MAIL_FROM_NAME="MOKA"

SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

After editing `.env`:
```bash
php artisan config:clear
php artisan config:cache
```

---

## TROUBLESHOOTING

### CSS / JS not loading (404)
```bash
# Check files are in the right place
ls /var/www/moka/public/moka-v2/css/
# Should show: design-system.css  header.css  home.css  listings.css  pages.css
```

### Blade view not found error
```bash
# Check views are in place
ls /var/www/moka/resources/views/v2/partial/
ls /var/www/moka/resources/views/v2/pages/

# Clear view cache
php artisan view:clear
```

### 500 Server Error
```bash
# Check Laravel log
tail -100 /var/www/moka/storage/logs/laravel.log

# Check Nginx error log
tail -50 /var/log/nginx/error.log
```

### Permission denied
```bash
chown -R www-data:www-data /var/www/moka
chmod -R 775 /var/www/moka/storage
chmod -R 775 /var/www/moka/bootstrap/cache
```

---

## DESIGN SYSTEM REFERENCE

| Token | Value | Usage |
|-------|-------|-------|
| `--teal` | `#003d3c` | Primary brand (nav, footer, headings) |
| `--orange` | `#ff6b35` | Accent (CTAs, highlights) |
| `--cream` | `#faf8f4` | Section backgrounds |
| `--font-display` | Playfair Display | All headings |
| `--font-body` | DM Sans | All body text |

---

## QUICK COMMAND REFERENCE

```bash
# After any file change:
php artisan view:clear && php artisan cache:clear

# Check server status:
systemctl status nginx php8.1-fpm mysql

# Restart all services:
systemctl restart nginx php8.1-fpm

# Watch logs live:
tail -f /var/www/moka/storage/logs/laravel.log
```

---

*MOKA v2 — Built with Apple software engineer mindset.*
*Every pixel is intentional. Every interaction is smooth.*
