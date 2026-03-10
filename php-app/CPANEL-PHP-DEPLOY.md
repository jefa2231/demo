# Deploy `php-app` ke cPanel (PHP + MySQL)

Panduan ini untuk menjalankan JeStore tanpa Node.js runtime.

## 1. Upload file
1. Upload folder `php-app` ke hosting.
2. Pastikan document root domain/subdomain mengarah ke folder `php-app/public_html`.

Struktur di server:
```
/home/username/jestore/        ← root project (php-app)
├── app/                       ← logic (di luar web root)
├── .env                       ← config (di luar web root)
└── public_html/               ← document root domain
    ├── index.php
    ├── .htaccess
    ├── css/
    ├── js/
    └── uploads/
```

## 2. Set environment
Buat file `.env` di dalam folder `php-app` (satu level dengan `app/` dan `public_html/`).

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
Tabel yang dibutuhkan: `admins`, `categories`, `products`, `product_images`.

Kolom wajib di `products`:
- `title`, `slug`, `description`, `price`
- `category_id`, `whatsapp_number`, `is_active`
- `badge_status`, `publish_at`, `og_image_path`
- `view_count`, `telegram_click_count`
- `createdAt`, `updatedAt`

## 4. Seed admin
Buka `https://domainkamu.com/seed-admin.php` untuk membuat akun admin default.
**Hapus file `seed-admin.php` setelah selesai!**

Login: `admin` / `admin12345`

## 5. Permission folder upload
Pastikan folder ini writable:
- `php-app/public_html/uploads/products`
- `php-app/public_html/uploads/og`

Saran permission:
- folder: `755`
- file: `644`

Jika gagal upload di beberapa hosting, bisa coba `775` untuk folder upload.

## 6. Cek URL
- Public: `https://domainkamu.com/`
- Katalog: `https://domainkamu.com/katalog`
- Admin: `https://domainkamu.com/admin/login`

## 7. Troubleshooting
- Error 404 semua route:
  - pastikan `.htaccess` aktif (`mod_rewrite` aktif)
  - pastikan document root menunjuk ke `php-app/public_html`
- Error database:
  - cek kredensial `.env`
  - cek user DB punya akses penuh ke DB
- Upload gagal:
  - cek permission folder `public_html/uploads/*`
  - cek limit upload PHP di cPanel (`upload_max_filesize`, `post_max_size`)
