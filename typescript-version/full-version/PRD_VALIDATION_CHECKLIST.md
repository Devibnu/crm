# PRD Validation Checklist

Gunakan checklist ini setelah ada perubahan label modul, submenu, locale, atau struktur navigasi Vuexy CRM.

## 1. Validasi Label Modul

- Pastikan sidebar CRM menampilkan label berikut sesuai locale aktif:
  - CRM Service Management
  - CRM Sales Enablement
  - CRM Marketing Automation
  - CRM Omnichannel
- Pastikan sidebar backoffice menampilkan label berikut sesuai locale aktif:
  - Governance & Security
  - Integrasi & Transaksi pada locale `id`, `Integrations & Transactions` pada locale `en`
  - Pelaporan & Dashboard pada locale `id`, `Reporting & Dashboard` pada locale `en`
- Pastikan submenu utama backoffice tersedia:
  - Pengguna or Users
  - Roles & Permissions
  - Audit Trail
  - Invoice
  - Contract Management System
  - Executive Dashboard
  - Reporting
- Verifikasi sumber navigation menggunakan locale key, bukan hardcoded label, di file navigation aktif.

## 2. Validasi Submenu Kecil

- Pastikan pasangan bilingual berikut konsisten di sidebar, top search, shortcut terkait, dan halaman backoffice:
  - Daftar <-> List
  - Pratinjau <-> Preview
  - Ubah <-> Edit
  - Tambah <-> Add
- Pastikan source top search untuk invoice memakai key locale dari locale file, bukan string hardcoded English.
- Pastikan halaman invoice list, preview, add, dan edit memakai `t(...)` untuk label kecil yang tampil ke user.

## 3. Validasi Locale File

- Periksa [src/plugins/i18n/locales/id.json](src/plugins/i18n/locales/id.json) dan [src/plugins/i18n/locales/en.json](src/plugins/i18n/locales/en.json).
- Pastikan kedua file memiliki key yang konsisten untuk:
  - `crm.nav.serviceManagement`
  - `crm.nav.salesEnablement`
  - `crm.nav.marketingAutomation`
  - `crm.nav.omnichannel`
  - `backoffice.nav.governanceSecurity`
  - `backoffice.nav.integrationsTransactions`
  - `backoffice.nav.reportingDashboard`
  - `backoffice.search.invoiceList`
  - `backoffice.search.invoicePreview`
  - `backoffice.search.invoiceEdit`
  - `backoffice.search.invoiceAdd`
- Pastikan tidak ada istilah lama yang tersisa, terutama:
  - CRM Otomasi Marketing
  - CRM Layanan Pelanggan
  - Backoffice Admin

## 4. Validasi Build

- Jalankan diagnostics editor dan pastikan tidak ada error pada file yang diubah.
- Jalankan build frontend dari root app TypeScript:

```bash
pushd /Users/ibnuqosim/Documents/devlopmentibnu/kitcrm/typescript-version/full-version >/dev/null && pnpm build && popd >/dev/null
```

- Pastikan build selesai tanpa error.

## 5. Evidence Yang Disimpan

- Catat file yang berubah.
- Catat hasil scan istilah lama.
- Catat status diagnostics editor.
- Catat hasil akhir build.