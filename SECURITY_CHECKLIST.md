# Security Regression Checklist

Gunakan checklist ini setiap selesai perubahan fitur sebelum deploy.

## 1) Authentication & Session
- Coba login salah berulang, pastikan kena limit percobaan.
- Pastikan session ID berubah setelah login (`session_regenerate_id`).
- Pastikan akun `public_school` tidak bisa login admin/user.
- Logout harus menghapus semua session (`sarpras_admin`, `sarpras_user`, `sarpras_public`, `sarpras_login`).

## 2) CSRF Protection
- Semua endpoint `POST` wajib menolak request tanpa `_csrf_token`.
- Coba kirim form dari tab lain dengan token lama, harus ditolak.
- Pastikan form admin aksi sensitif (`approve`, `reject`, `kembali`, `setujui`) pakai token.

## 3) Input Validation
- Uji input panjang berlebih pada form publik (`pinjam`, `minta`, `laporan`) -> harus ditolak.
- Uji karakter aneh/script tag di field teks -> harus disimpan aman/ditolak.
- Uji nomor WA non-digit atau panjang tidak valid -> harus ditolak.
- Uji nilai jumlah ekstrem (negatif/sangat besar) -> harus ditolak.

## 4) Rate Limiting
- Spam submit form publik dari browser yang sama -> kena limit sesi.
- Spam submit form publik dari IP yang sama -> kena limit IP.
- Cek `audit_logs` dan pastikan penolakan tercatat sebagai `rejected_request`.

## 5) SQL Injection
- Uji payload seperti `' OR 1=1 --` di filter admin (nama/kelas/kategori).
- Pastikan query tidak error dan hasil tidak bocor.
- Pastikan endpoint export CSV tetap berjalan normal setelah payload uji.

## 6) XSS & Output Encoding
- Isi data dengan payload HTML/JS, lalu lihat di halaman admin/user.
- Pastikan data tampil sebagai teks biasa (bukan dieksekusi).
- Periksa kolom log/detail yang berisi data user juga tetap di-escape.

## 7) Access Control
- Akses halaman `admin/*` tanpa login admin -> harus redirect ke login.
- Akses aksi admin lewat direct URL tanpa role admin -> harus ditolak.
- Pastikan user publik hanya bisa akses menu publik.

## 8) Error Handling & Information Disclosure
- Putuskan DB sementara / trigger error internal.
- Pastikan user hanya menerima pesan generik, bukan stack trace/query mentah.
- Detail teknis hanya masuk log internal, bukan UI publik.

## 9) File Export
- Download CSV peminjaman harus memuat data sesuai bulan aktif.
- Nama file harus sesuai format `peminjaman_sarpras_{bulan}.csv`.
- Buka CSV di Excel dan pastikan format kolom tetap benar.

## 10) Final Deploy Gate
- Jalankan `php -l` untuk file yang diubah.
- Cek `git diff` untuk memastikan tidak ada kredensial/secret ikut ter-commit.
- Pastikan `config/database.local.php` tidak ikut push ke repository publik.
