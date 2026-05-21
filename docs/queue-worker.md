# Laravel Queue Worker

WhatsApp Broadcast uses Laravel Queue with the database driver. Do not run queue workers from a web request or controller, because it can block the HTTP request and make the app hang.

## Environment

Set the queue connection in `.env`:

```env
QUEUE_CONNECTION=database
```

After changing queue configuration, clear cached config:

```bash
php artisan optimize:clear
```

## Local Development

Run a worker from your terminal:

```bash
php artisan queue:work
```

For one-job debugging:

```bash
php artisan queue:work --once -vvv
```

## Production Command

Recommended worker command:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120
```

Restart workers after deploys or config changes:

```bash
php artisan queue:restart
```

## Supervisor Example

```ini
[program:krakatau-crm-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/krakatau-crm/artisan queue:work --sleep=3 --tries=3 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/krakatau-crm/storage/logs/queue-worker.log
stopwaitsecs=130
```

After creating or updating the Supervisor config:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart krakatau-crm-queue:*
```
