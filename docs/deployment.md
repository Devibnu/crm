# Panduan Deployment — CRM Krakatau

Dokumen ini menjelaskan langkah-langkah deployment CRM Krakatau ke server production.

---

## Kebutuhan Server

| Komponen | Versi Minimum |
|----------|---------------|
| PHP | 8.2+ (disarankan 8.4) |
| Composer | 2.x |
| Node.js | 18.x+ |
| npm | 9.x+ |
| PostgreSQL | 14+ |
| Web Server | Nginx atau Apache |
| OS | Ubuntu 22.04 LTS (disarankan) |

**Ekstensi PHP wajib:**
`pdo`, `pdo_pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`, `fileinfo`, `zip`

---

## Langkah Deployment Pertama Kali

### 1. Clone Repo

```bash
git clone https://gitlab.krakatau-it.co.id/ibnuqosim/crm.git /var/www/crm
cd /var/www/crm
```

### 2. Install Dependensi PHP

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Install Dependensi Node & Build Asset

```bash
npm ci
npm run build
```

### 4. Setup File `.env`

```bash
cp .env.example .env
```

Edit `.env` dan sesuaikan konfigurasi:

```env
APP_NAME="CRM Krakatau"
APP_ENV=production
APP_KEY=           # akan diisi otomatis di langkah berikutnya
APP_DEBUG=false
APP_URL=https://crm.domain-anda.co.id

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=krakatau_crm
DB_USERNAME=crm_user
DB_PASSWORD=password_anda

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Migrasi Database

```bash
php artisan migrate --seed --force
```

> Gunakan flag `--force` di environment production.

### 7. Link Storage

```bash
php artisan storage:link
```

### 8. Optimasi Cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 9. Permission Folder

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## Konfigurasi Web Server

### Nginx

```nginx
server {
    listen 80;
    server_name crm.domain-anda.co.id;
    root /var/www/crm/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

> Aktifkan HTTPS menggunakan Certbot + Let's Encrypt.

### Apache

```apache
<VirtualHost *:80>
    ServerName crm.domain-anda.co.id
    DocumentRoot /var/www/crm/public

    <Directory /var/www/crm/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

> Pastikan modul `mod_rewrite` aktif: `a2enmod rewrite`

---

## SSL dengan Certbot

```bash
apt install certbot python3-certbot-nginx
certbot --nginx -d crm.domain-anda.co.id
```

---

## Laravel Scheduler (Cron)

Tambahkan cron job untuk scheduler Laravel:

```bash
crontab -e
```

Tambahkan baris:

```
* * * * * cd /var/www/crm && php artisan schedule:run >> /dev/null 2>&1
```

---

## Queue Worker (Opsional)

Jika menggunakan queue, jalankan worker:

```bash
php artisan queue:work --daemon
```

Disarankan menggunakan **Supervisor** untuk menjaga worker tetap berjalan:

```ini
[program:crm-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/crm/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/var/www/crm
autostart=true
autorestart=true
numprocs=1
user=www-data
stdout_logfile=/var/www/crm/storage/logs/worker.log
```

---

## Deploy Ulang (Update)

Lihat file [deploy-example.sh](../deploy-example.sh) di root project untuk contoh command update deployment.

---

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| `storage not writable` | Jalankan `chmod -R 775 storage` |
| `APP_KEY missing` | Jalankan `php artisan key:generate` |
| `500 Internal Server Error` | Cek `storage/logs/laravel.log` |
| `Class not found` | Jalankan `composer dump-autoload` |
| Route 404 | Pastikan `AllowOverride All` di Apache atau `try_files` di Nginx aktif |
