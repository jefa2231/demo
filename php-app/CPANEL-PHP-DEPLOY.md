# Deploy `php-app` ke cPanel (PHP + MySQL)

Panduan ini untuk menjalankan JeStore tanpa Node.js runtime.

## 1. Upload file
1. Upload folder `php-app` ke hosting (misal di `public_html/jestore`).
2. Pastikan document root domain/subdomain mengarah ke folder `php-app/public`.

Jika document root tidak bisa diubah:
- salin isi `php-app/public` ke `public_html`
- dan pindahkan folder `php-app/app` serta `.env` tetap di luar web root.

## 2. Set environment
Buat file `.env` di dalam folder `php-app` (satu level dengan `app/` dan `public/`).

Contoh:

```env
APP_NAME=JeStore
DB_HOST=localhost
DB_PORT=3306
DB_NAME=namadb
DB_USER=userdb
DB_PASS=passworddb
TELEGRAM_DEFAULT=t.me/jefa14
TIMEZONE=Asia/Jakarta
```

## 3. Database
Gunakan database yang sama dengan versi sebelumnya (`admins`, `categories`, `products`, `product_images`).

Minimal pastikan kolom ini tersedia di `products`:
- `badge_status`
- `publish_at`
- `view_count`
- `telegram_click_count`
- `og_image_path`

## 4. Permission folder upload
Pastikan folder ini writable:
- `php-app/public/uploads/products`
- `php-app/public/uploads/og`

Saran permission:
- folder: `755`
- file: `644`

Jika gagal upload di beberapa hosting, bisa coba `775` untuk folder upload.

## 5. Cek URL
- Public: `https://domainkamu.com/`
- Katalog: `https://domainkamu.com/katalog`
- Admin: `https://domainkamu.com/admin/login`

## 6. Login admin
Gunakan akun admin dari database lama (hash password tetap kompatibel karena pakai `password_verify`).

## 7. Troubleshooting
- Error 404 semua route:
  - pastikan `.htaccess` aktif (`mod_rewrite` aktif)
  - pastikan document root menunjuk ke `php-app/public`
- Error database:
  - cek kredensial `.env`
  - cek user DB punya akses penuh ke DB
- Upload gagal:
  - cek permission folder `public/uploads/*`
  - cek limit upload PHP di cPanel (`upload_max_filesize`, `post_max_size`)
