CMS ADMIN DAUN CINCAU ID - PAKET AWAL

ISI PAKET
- Registrasi satu akun Owner (one-time registration)
- Login dan logout
- Dashboard ringkasan pesanan
- Daftar pesanan dan filter status pembayaran
- Detail pesanan dan verifikasi pembayaran
- Perubahan status pesanan
- Riwayat transaksi
- Profil Owner dan ubah password
- Session, CSRF token, prepared statements, dan password_hash

PERSIAPAN DATABASE
1. Pastikan database dauncincau_db dan tabel orders, order_items, payments sudah ada.
2. Jalankan SQL berikut satu kali di phpMyAdmin:

CREATE TABLE admins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_slot TINYINT UNSIGNED NOT NULL DEFAULT 1,
    owner_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_owner_account (account_slot)
) ENGINE=InnoDB;

PENEMPATAN FILE
Salin folder admin dari ZIP ke folder utama project:
C:\xampp\htdocs\dauncincaustarter\admin

Pastikan config/database.php berada di:
C:\xampp\htdocs\dauncincaustarter\config\database.php
dan membuat variabel PDO bernama $pdo.

JALANKAN
1. Start Apache dan MySQL dari XAMPP.
2. Buka http://localhost/dauncincaustarter/admin/register.php
3. Buat akun Owner pertama.
4. Setelah berhasil, halaman registrasi otomatis ditutup dan diarahkan ke login.
5. Login melalui http://localhost/dauncincaustarter/admin/login.php

CATATAN PENTING
- Paket ini adalah starter CMS untuk pengembangan dan pengujian lokal, belum diaudit untuk produksi.
- Login rate limit pada paket awal berbasis session browser; sebelum produksi perlu rate limit terpusat di server/database.
- Session cookie path disetel untuk /dauncincaustarter/admin. Sesuaikan jika nama folder project berbeda.
- Verifikasi pembayaran hanya dilakukan setelah Owner mencocokkan transaksi QRIS di sumber transaksi resmi.
- Untuk produksi wajib HTTPS, konfigurasi session cookie secure, backup database, proteksi server, dan pengujian keamanan.
- Registrasi satu akun dikunci oleh unique account_slot pada database. Jangan hapus akun Owner tanpa prosedur pemulihan.
- Logout menggunakan POST + CSRF.
- Folder config/database.php tidak disertakan dan tidak ditimpa; gunakan file koneksi PDO project yang sudah ada.

PENTING UNTUK SKEMA
CMS mengasumsikan tabel:
orders(id, order_code, customer_name, customer_phone, customer_address, customer_note, total_amount, payment_method, payment_status, order_status, created_at, verified_at)
order_items(id, order_id, product_name, price, quantity, subtotal)
payments(id, order_id, payment_method, amount, status, verified_at, verification_note, created_at)
