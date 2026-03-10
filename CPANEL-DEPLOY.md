# Deploy ke cPanel (Node.js)

Panduan ini untuk project `JeStore` agar stabil saat dijalankan di cPanel Node.js App.

## 1) Setup Node App di cPanel
- Buka `Setup Node.js App` di cPanel.
- Pilih versi Node `18+` (disarankan 20 jika tersedia).
- `Application mode`: `Production`.
- `Application root`: folder project ini.
- `Application URL`: domain/subdomain kamu.
- `Application startup file`: `app.js`.

## 2) Environment Variables (wajib)
Set variabel berikut di cPanel (menu Environment Variables):

- `NODE_ENV=production`
- `PORT` diisi otomatis oleh cPanel (jangan dipaksa jika sudah ada)
- `DB_HOST=localhost` (atau host MySQL dari provider kamu)
- `DB_PORT=3306`
- `DB_USER=...`
- `DB_PASS=...`
- `DB_NAME=...`
- `JWT_SECRET=isi-random-panjang`
- `JWT_EXPIRES_IN=7d`
- `ADMIN_COOKIE_NAME=admin_token`
- `COOKIE_SECURE=auto`
- `COOKIE_SAME_SITE=lax`
- `TRUST_PROXY=1`
- `UPLOAD_DIR=src/public/uploads/products`
- `MAX_UPLOAD_FILES=10`
- `MAX_UPLOAD_SIZE_MB=2`
- `TELEGRAM_DEFAULT=t.me/jefa14`

## 3) Install dependencies
Jalankan di terminal cPanel pada root app:

```bash
npm install
```

## 4) Inisialisasi database
Jalankan:

```bash
npm run setup:cpanel
```

Perintah di atas akan:
1. sync/migrate schema
2. seed/update admin default (`admin / admin12345`)

## 5) Restart aplikasi
- Klik `Restart` di halaman Node.js App cPanel.

## 6) Verifikasi
- Health check: `https://domainkamu.com/api/health`
- Public: `https://domainkamu.com/`
- Admin: `https://domainkamu.com/admin/login`

## 7) Troubleshooting cepat
- Jika login admin gagal di production:
  - pastikan `TRUST_PROXY=1`
  - pastikan site diakses via `https`
  - biarkan `COOKIE_SECURE=auto`
- Jika upload gagal:
  - cek permission folder `src/public/uploads/products`
- Jika DB gagal konek:
  - cek `DB_HOST/DB_USER/DB_PASS/DB_NAME`
  - cek user MySQL sudah punya akses ke DB