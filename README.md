# WhatsApp

Tugas besar mata kuliah Perencanaan Pengembangan Perangkat Lunak

## Daftar Anggota

| No. | Nama | NIM |
|---|---|---|
| 1 | Fabyo Nathanael Suoth | 10241027 |
| 2 | Hamzah Wiranata | 10241035 |
| 3 | Linggar Pramudya | 10241039 |
| 4 | Muhammad Farras Kanz | 10241047 |
| 5 | Toro Ababil Tamami Darojat | 10241071 |

## WhatsApp

Aplikasi percakapan berbasis PHP dengan autentikasi email, kode verifikasi demo, pengelolaan kontak dan pesan, berbagi lampiran, serta pencadangan dan pemulihan percakapan.

### Kebutuhan

- PHP 8.1 atau lebih baru dengan ekstensi `pdo_mysql`, `fileinfo`, `mbstring`, dan `session`.
- MySQL atau MariaDB.
- Apache/XAMPP atau server pengembangan PHP.

### Menjalankan aplikasi

1. Buat database MySQL bernama `whatsapp` melalui phpMyAdmin atau konsol MySQL.
2. Salin `config/database.example.php` sebagai `config/database.php`, lalu sesuaikan host, nama database, username, dan password.
3. Import `database/schema.sql` ke database `whatsapp`. Tabel juga dibuat otomatis saat aplikasi pertama kali dijalankan.
4. Jalankan Apache dari XAMPP, atau dari folder proyek gunakan `php -S localhost:8000`.
5. Buka `http://localhost/WhatsApp/` untuk Apache atau `http://localhost:8000` untuk server pengembangan PHP.

Folder `storage/uploads` dibuat otomatis. File di dalamnya hanya dapat diambil melalui endpoint yang memeriksa sesi akun. Lampiran maksimal 12 MB per file dan format yang didukung mencakup gambar, PDF, teks, ZIP, DOCX, dan XLSX.

### Akun dan verifikasi

Daftar dengan email dan kata sandi minimal 8 karakter. Karena integrasi email tidak disediakan, kode verifikasi ditampilkan langsung di layar sebagai kode demo lokal. Pengguna yang belum terverifikasi dapat meminta kode baru dari formulir verifikasi.

### Fitur data

- Kontak/chat: buat, lihat, ubah nama, arsipkan, dan hapus.
- Pesan: kirim teks, upload foto, atau lampirkan file dan dokumen; ubah dan hapus pesan sendiri.
- Cadangan: unduh JSON yang berisi chat, pesan, dan lampiran (maksimal total 25 MB); pemulihan mengganti data chat akun yang sedang masuk.

Setiap akun mendapatkan beberapa percakapan contoh saat pertama kali masuk. Data tersebut berada di database lokal dan dapat diubah atau dihapus dari antarmuka.
