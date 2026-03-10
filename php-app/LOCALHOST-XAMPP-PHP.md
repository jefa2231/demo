# Run `php-app` di Windows (XAMPP)

## 1. Start XAMPP
- Jalankan `Apache` dan `MySQL` dari XAMPP Control Panel.

## 2. Siapkan `.env`
Di folder `php-app`, copy `.env.example` jadi `.env` lalu isi:

```env
APP_NAME=JeStore
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=lazastore
DB_USER=root
DB_PASS=
TELEGRAM_DEFAULT=t.me/jefa14
TIMEZONE=Asia/Jakarta
```

## 3. Letakkan project
Pilihan A (disarankan):
- simpan di `D:\github\demo\php-app`
- gunakan VirtualHost Apache ke `php-app/public`

Pilihan B (paling cepat):
- copy isi `php-app/public` ke `C:\xampp\htdocs\jestore`
- tetap simpan folder `app` dan `.env` di luar web root jika bisa.

## 4. Akses
- `http://localhost/jestore/` (jika pakai `htdocs/jestore`)
- atau sesuai VirtualHost yang kamu set.

## 5. Catatan
- App ini front-controller (`public/index.php`) + `.htaccess` rewrite.
- Jika route selain `/` 404, aktifkan `mod_rewrite` dan `AllowOverride All`.
