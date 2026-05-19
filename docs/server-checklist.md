# Server Checklist — CRM Krakatau

Gunakan checklist ini sebelum deployment ke server production.

---

## PHP

- [ ] PHP versi 8.2 atau lebih baru terinstall
  ```bash
  php -v
  ```
- [ ] Ekstensi `pdo` aktif
- [ ] Ekstensi `pdo_pgsql` aktif
- [ ] Ekstensi `mbstring` aktif
- [ ] Ekstensi `openssl` aktif
- [ ] Ekstensi `tokenizer` aktif
- [ ] Ekstensi `xml` aktif
- [ ] Ekstensi `ctype` aktif
- [ ] Ekstensi `json` aktif
- [ ] Ekstensi `bcmath` aktif
- [ ] Ekstensi `curl` aktif
- [ ] Ekstensi `fileinfo` aktif
- [ ] Ekstensi `zip` aktif
  ```bash
  php -m
  ```

---

## Composer

- [ ] Composer versi 2.x terinstall
  ```bash
  composer --version
  ```

---

## Node.js & npm

- [ ] Node.js versi 18.x atau lebih baru terinstall
  ```bash
  node -v
  ```
- [ ] npm versi 9.x atau lebih baru terinstall
  ```bash
  npm -v
  ```

---

## Database

- [ ] PostgreSQL versi 14+ terinstall dan berjalan
  ```bash
  psql --version
  systemctl status postgresql
  ```
- [ ] Database `krakatau_crm` sudah dibuat
- [ ] User database `crm_user` sudah dibuat dan punya akses ke database
- [ ] Koneksi database dari server web bisa terhubung

---

## Permission Folder

- [ ] Folder `storage/` dapat ditulis oleh web server
  ```bash
  ls -la storage/
  ```
- [ ] Folder `bootstrap/cache/` dapat ditulis oleh web server
  ```bash
  ls -la bootstrap/cache/
  ```
- [ ] Perintah permission sudah dijalankan:
  ```bash
  chmod -R 775 storage bootstrap/cache
  chown -R www-data:www-data storage bootstrap/cache
  ```

---

## Domain & DNS

- [ ] Domain sudah diarahkan ke IP server (A record di DNS)
- [ ] DNS propagation sudah selesai (cek dengan `dig crm.domain.co.id`)
- [ ] Virtual host web server sudah dikonfigurasi untuk domain tersebut
- [ ] Port 80 dan 443 terbuka di firewall
  ```bash
  ufw status
  ```

---

## SSL / HTTPS

- [ ] Sertifikat SSL sudah dipasang (Let's Encrypt / Certbot)
  ```bash
  certbot certificates
  ```
- [ ] Redirect HTTP → HTTPS sudah aktif
- [ ] Sertifikat belum expired
  ```bash
  openssl s_client -connect crm.domain.co.id:443 -servername crm.domain.co.id 2>/dev/null | openssl x509 -noout -dates
  ```

---

## Konfigurasi Aplikasi

- [ ] File `.env` sudah dibuat dari `.env.example`
- [ ] `APP_KEY` sudah di-generate (`php artisan key:generate`)
- [ ] `APP_DEBUG=false` di production
- [ ] `APP_ENV=production` sudah diset
- [ ] `APP_URL` sudah diset dengan URL yang benar (HTTPS)

---

## Cron Scheduler Laravel

- [ ] Cron job Laravel sudah ditambahkan
  ```bash
  crontab -l
  # Harus ada baris:
  # * * * * * cd /var/www/crm && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] Jalankan test scheduler:
  ```bash
  php artisan schedule:list
  ```

---

## Queue Worker (Jika Dipakai)

- [ ] Supervisor terinstall
  ```bash
  supervisorctl status
  ```
- [ ] Config worker Supervisor sudah dibuat di `/etc/supervisor/conf.d/crm-worker.conf`
- [ ] Worker sudah running:
  ```bash
  supervisorctl start crm-worker:*
  ```
- [ ] Cek status worker:
  ```bash
  supervisorctl status crm-worker:*
  ```

---

## Validasi Akhir

- [ ] Buka URL production di browser — halaman tampil normal
- [ ] Login ke admin panel berhasil
- [ ] Route `/admin` merespons
- [ ] Tidak ada error di `storage/logs/laravel.log`
  ```bash
  tail -n 50 storage/logs/laravel.log
  ```
