# Electra

Toko demo elektronik dengan PHP 8+, MySQL/MariaDB, login dan registrasi, katalog, stok, keranjang, checkout pesanan, serta dashboard admin.

## Menjalankan di Laragon

1. Nyalakan Apache dan MySQL dari Laragon.
2. Simpan folder proyek di `C:\laragon\www\latian3`.
3. Buka `http://localhost/latian3/`. Database `electra_store`, tabel, katalog awal, dan akun admin dibuat otomatis saat aplikasi pertama kali dibuka.
4. Jika konfigurasi MySQL berbeda, atur environment variable `DB_HOST`, `DB_NAME`, `DB_USER`, dan `DB_PASS` untuk Apache.

### Akun admin awal

- Email: `admin@electra.local`
- Kata sandi: `Admin123!`

Ganti kata sandi awal sebelum aplikasi digunakan di luar komputer lokal. Pendaftaran publik hanya membuat akun pelanggan dan tidak dapat mengubah role.

## Fitur

- Katalog kategori Laptop, Smartphone, PC & Komponen, Monitor, Audio, dan Aksesori.
- Halaman detail produk dengan deskripsi, harga, informasi stok, dan akses tambah ke keranjang.
- Harga, jumlah stok, pencarian produk, dan keranjang belanja (maksimal 10 unit per produk, menyesuaikan stok terkini).
- Checkout menggunakan transaksi database yang memeriksa serta mengurangi stok.
- Dashboard pelanggan, riwayat pesanan, dan dashboard admin untuk melihat hingga 20 pesanan terbaru, serta menambah produk atau mengubah harga dan stok.
- Halaman informasi pengiriman yang menjelaskan bahwa ongkir, estimasi, pembayaran, dan pelacakan belum terintegrasi.
- Gambar produk di `assets/products/` ditampilkan pada kartu katalog dan halaman detail untuk produk yang memiliki gambar.
- Formulir masuk menyediakan pilihan akun pelanggan atau admin dan memeriksa role akun; pendaftaran publik hanya membuat akun pelanggan.
- Logo Electra SVG digunakan pada header, footer, dan favicon situs.
- Kata sandi di-hash dan formulir perubahan data menggunakan CSRF token.
- Pencarian tetap aktif saat kategori katalog diganti, dan navigasi utama tetap tersedia pada layar ponsel.
- Tampilan menggunakan palet dark navy, tech blue, dan light gray.

Harga yang diisi otomatis adalah contoh indikatif tahun 2026, bukan sinkronisasi harga retail langsung. Perbarui data dari pemasok sebelum toko dipublikasikan. Pembayaran dan pengiriman belum terintegrasi.
