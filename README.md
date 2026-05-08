# CRM Krakatau

Aplikasi **Customer Relationship Management (CRM)** milik Krakatau IT, dibangun di atas Laravel 13 dan Vue 3 (Vite).

## Fitur Utama

- **Dashboard** — ringkasan metrik penjualan dan layanan
- **Customer 360** — profil pelanggan, interaksi, transaksi, dan preferensi
- **Sales Enablement** — Leads, Opportunities, Quotations, Sales Activities, Pipeline
- **Marketing** — Campaign, Audience Segment, Landing Page
- **Service Management** — Tickets, Case Resolution, SLA Policy, Omnichannel Inbox
- **Social Media Engagement**
- **Win/Lost Analysis**
- **Knowledge Base**

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 13.x (PHP 8.4) |
| Frontend | Vue 3 + Vite |
| Database | PostgreSQL |
| Testing | PHPUnit (198 tests, 705 assertions) |
| Autentikasi | Laravel session-based |

## Instalasi Lokal

```bash
# 1. Clone repo
git clone https://gitlab.krakatau-it.co.id/ibnuqosim/crm.git
cd crm

# 2. Install dependensi PHP
composer install

# 3. Install dependensi Node
npm install

# 4. Salin dan sesuaikan .env
cp .env.example .env
php artisan key:generate

# 5. Migrasi database
php artisan migrate --seed

# 6. Build asset frontend
npm run build

# 7. Link storage
php artisan storage:link

# 8. Jalankan server lokal
php artisan serve
```

## Deployment ke Production

Lihat panduan lengkap di [docs/deployment.md](docs/deployment.md).

## Menjalankan Tests

```bash
php artisan test
```

## Branch

| Branch | Keterangan |
|--------|------------|
| `main` | Branch stabil production |
| `develop-clean` | Branch pengembangan aktif |

## Lisensi

Hak cipta © 2025 PT Krakatau IT. Seluruh hak dilindungi.
