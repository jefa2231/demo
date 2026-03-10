# Run di Windows (XAMPP + Node.js)

## 1) Start service XAMPP
- Buka `XAMPP Control Panel`.
- Klik `Start` pada `MySQL`.
- Pastikan status `Running` dan port `3306` tidak bentrok.

## 2) Siapkan env project
- File `.env` sudah disiapkan.
- Default lokal:
  - `DB_HOST=127.0.0.1`
  - `DB_PORT=3306`
  - `DB_USER=root`
  - `DB_PASS=` (kosong)
  - `DB_NAME=lazastore`

Jika root MySQL kamu pakai password, isi `DB_PASS` di `.env`.

## 3) Inisialisasi database + tabel + admin
Jalankan di folder project:

```powershell
npm run setup:local
```

Script ini akan:
1. membuat database `lazastore` jika belum ada,
2. membuat/menyesuaikan tabel,
3. membuat admin default `admin / admin12345`.

## 4) Build CSS dan jalanin app
```powershell
npm run build
npm start
```

Akses:
- Public: `http://localhost:3000`
- Admin login: `http://localhost:3000/admin/login`

## 5) Jika gagal konek DB
- Cek MySQL XAMPP benar-benar `Running`.
- Cek apakah port 3306 dipakai service lain.
- Cek kredensial `.env`.
- Test cepat port:

```powershell
Test-NetConnection 127.0.0.1 -Port 3306
```