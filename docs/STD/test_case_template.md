# Template Test Case — WhatsApp

Dokumen ini digunakan untuk mencatat pengujian aplikasi WhatsApp. Kode kebutuhan mengacu pada pemetaan di `docs/DPPL_MAPPING.md`. Kolom **Hasil Aktual** dan **Status** diisi setelah pengujian dijalankan.

## Petunjuk Pengisian

- **ID**: kode unik untuk setiap kasus, gunakan format `TC-XXX`.
- **Kebutuhan**: kode kebutuhan yang diuji, misalnya `FR-01`, `FR-05`, atau `G-03`.
- **Skenario**: satu kondisi atau tujuan pengujian dalam satu kasus.
- **Langkah**: urutan tindakan yang dapat diulang oleh penguji.
- **Hasil Diharapkan**: hasil yang benar sesuai implementasi dan pemetaan kebutuhan.
- **Hasil Aktual**: hasil yang diamati saat pengujian; kosongkan sebelum pengujian.
- **Status**: isi `Lulus`, `Gagal`, atau `Belum diuji`.
- Jalankan pengujian pemulihan hanya dengan data uji atau cadangan yang aman. Pemulihan mengganti seluruh percakapan akun yang sedang masuk.
- Untuk kebutuhan yang belum tersedia, jangan mencatatnya sebagai lulus hanya karena tidak ada kesalahan. Catat bahwa fitur belum tersedia pada hasil aktual dan laporkan sebagai kesenjangan implementasi.

## Informasi Pengujian

| Informasi | Isi |
|---|---|
| Versi/commit aplikasi | |
| Tanggal pengujian | |
| Nama penguji | |
| Browser dan versi | |
| PHP dan MySQL/MariaDB | |
| Lingkungan/URL | |

## Tabel Test Case

| ID | Kebutuhan | Skenario | Langkah | Hasil Diharapkan | Hasil Aktual | Status |
|---|---|---|---|---|---|---|
| TC-001 | FR-01 | Login dengan kata sandi yang salah | 1. Buka halaman aplikasi dalam keadaan keluar.<br>2. Masukkan email akun terdaftar dan kata sandi yang salah.<br>3. Kirim formulir masuk. | Login ditolak, pesan kesalahan ditampilkan, dan pengguna tetap tidak memiliki sesi masuk. | | Belum diuji |
| TC-002 | FR-01 | Pendaftaran dengan data tidak valid | 1. Buka formulir pendaftaran.<br>2. Masukkan nama kurang dari 2 karakter, email tidak valid, atau kata sandi kurang dari 8 karakter.<br>3. Kirim formulir. | Pendaftaran ditolak dan pesan validasi ditampilkan. Akun tidak dibuat. | | Belum diuji |
| TC-003 | FR-05 | Pengiriman lampiran dengan format yang tidak didukung | 1. Masuk dan buka percakapan.<br>2. Pilih berkas dengan format yang tidak didukung.<br>3. Coba kirim berkas. | Server menolak berkas dengan tipe MIME yang tidak diizinkan dan pesan kesalahan ditampilkan. Berkas tidak tersimpan sebagai pesan. | | Belum diuji |
| TC-004 | FR-05 | Pengiriman lampiran yang melebihi batas ukuran | 1. Masuk dan buka percakapan.<br>2. Pilih berkas berukuran lebih dari 12 MiB.<br>3. Coba kirim berkas. | Berkas ditolak dan pengguna mendapat informasi mengenai batas ukuran. | | Belum diuji |
| TC-005 | G-01 | Ekspor cadangan percakapan | 1. Masuk dengan akun yang memiliki percakapan dan pesan uji.<br>2. Buka menu cadangan.<br>3. Unduh cadangan dan periksa berkas JSON. | Berkas JSON terunduh dengan format `whatsapp-backup`, versi 1, dan memuat percakapan serta pesan akun tersebut. | | Belum diuji |
| TC-006 | G-02 | Pemilihan berkas cadangan dengan JSON tidak valid | 1. Masuk dan buka menu pemulihan.<br>2. Pilih berkas JSON rusak atau dengan format yang bukan cadangan WhatsApp.<br>3. Konfirmasi pemulihan jika diminta. | Pemulihan ditolak, pesan kesalahan ditampilkan, dan percakapan yang ada tidak berubah. | | Belum diuji |
| TC-007 | G-03 | Pemulihan cadangan yang valid | 1. Siapkan cadangan dan data percakapan uji yang aman.<br>2. Masuk dengan akun uji dan buat cadangan kondisi awal.<br>3. Pilih cadangan valid yang berisi data berbeda.<br>4. Setujui konfirmasi pemulihan.<br>5. Periksa daftar percakapan dan pesan. | Percakapan akun yang sedang masuk diganti dengan isi cadangan dan data yang dipulihkan tampil setelah halaman dimuat ulang. | | Belum diuji |
| TC-008 | G-03 | Membatalkan konfirmasi pemulihan | 1. Masuk dan buka menu pemulihan.<br>2. Pilih berkas cadangan.<br>3. Batalkan konfirmasi pemulihan.<br>4. Periksa daftar percakapan. | Pemulihan dibatalkan dan data percakapan tidak berubah. | | Belum diuji |
| TC-___ | | | 1. <br>2. <br>3. | | | Belum diuji |
| TC-___ | | | 1. <br>2. <br>3. | | | Belum diuji |

## Catatan Temuan

| ID Test Case | Bukti / hasil pengamatan | Prioritas | Tindak lanjut |
|---|---|---|---|
| | | | |
| | | | |
