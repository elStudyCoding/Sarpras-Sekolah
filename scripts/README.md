# Maintenance Scripts

## Reset Database (ID Urut + Seed Awal)

Script ini mencegah kesalahan reset manual (misalnya password admin tidak sesuai).

Jalankan dari root project:

```powershell
C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe scripts\reset_sarpras.php --admin-password="PASSWORD_ADMIN_BARU"
```

Contoh:

```powershell
C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe scripts\reset_sarpras.php --admin-password="adminsarprassrikandi1"
```

Yang di-reset:
- `peminjaman`
- `permintaan_barang`
- `laporan`
- `audit_logs`
- `class_penalties`
- `wa_notification_logs` (jika ada)
- `barang`
- `users`

Data awal setelah reset:
- User `admin` (role `admin`) dengan password dari argumen `--admin-password`
- User `public_school` (role `user`) untuk dashboard publik
- Barang:
  - LCD
  - Kabel HDMI
  - Speaker
