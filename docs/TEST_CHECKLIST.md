# Checklist Pengujian Awal WhatsApp

Checklist ini disusun dari alur yang tersedia pada kode aplikasi. Jalankan pada lingkungan lokal yang sudah dikonfigurasi, lalu isi kotak centang dan kolom hasil setelah pengujian dilakukan.

## Informasi pengujian

| Informasi | Isi |
|---|---|
| Versi/commit aplikasi | |
| Tanggal pengujian | |
| Penguji | |
| Browser dan versi | |
| PHP dan MySQL/MariaDB | |
| URL lingkungan | |

**Status:** `Lulus` jika hasil aktual sesuai harapan, `Gagal` jika tidak sesuai, atau `N/A` jika kasus tidak dapat dijalankan. Catat bukti atau pesan kesalahan pada kolom catatan.

## 1. Instalasi dan pemuatan aplikasi

| ID | Checklist / langkah | Hasil yang diharapkan | Status | Catatan |
|---|---|---|---|---|
| INS-01 | Siapkan database MySQL/MariaDB dan konfigurasi koneksi di `config/database.php`, lalu buka aplikasi. | Aplikasi dapat terhubung ke database dan halaman masuk tampil. | ☐ | |
| INS-02 | Periksa database setelah aplikasi pertama kali dijalankan. | Tabel `users`, `chats`, dan `messages` tersedia. | ☐ | |
| INS-03 | Muat ulang halaman beberapa kali setelah skema tersedia. | Aplikasi tetap dapat dibuka dan tidak membuat tabel duplikat atau gagal karena skema sudah ada. | ☐ | |
| INS-04 | Buka aplikasi dengan database tidak tersedia atau konfigurasi koneksi salah. | Kegagalan koneksi tidak ditampilkan seolah-olah berhasil; catat pesan/log yang muncul untuk ditindaklanjuti. | ☐ | |

## 2. Pendaftaran, verifikasi, masuk, dan keluar

| ID | Checklist / langkah | Hasil yang diharapkan | Status | Catatan |
|---|---|---|---|---|
| AUTH-01 | Daftarkan akun dengan nama valid, email valid, kata sandi minimal 8 karakter, dan nomor telepon opsional. | Akun dibuat dan kode verifikasi demo enam digit ditampilkan. | ☐ | |
| AUTH-02 | Coba daftar dengan nama kurang dari 2 karakter, email tidak valid, atau kata sandi kurang dari 8 karakter. | Pendaftaran ditolak dan pesan validasi ditampilkan. | ☐ | |
| AUTH-03 | Daftarkan email yang sudah digunakan. | Pendaftaran ditolak dengan informasi bahwa email sudah terdaftar. | ☐ | |
| AUTH-04 | Masukkan kode verifikasi yang salah atau bukan enam digit. | Verifikasi gagal dan akun belum dapat digunakan. | ☐ | |
| AUTH-05 | Minta kode baru dari formulir verifikasi, lalu gunakan kode baru tersebut. | Kode baru ditampilkan dan menggantikan kode sebelumnya; kode yang masih berlaku dapat digunakan. | ☐ | |
| AUTH-06 | Verifikasi akun menggunakan kode yang benar sebelum kedaluwarsa. | Akun terverifikasi, sesi masuk dibuat, dan halaman percakapan terbuka. | ☐ | |
| AUTH-07 | Coba verifikasi menggunakan kode yang sudah lebih dari 15 menit. | Kode ditolak sebagai salah atau kedaluwarsa. | ☐ | |
| AUTH-08 | Masuk dengan email/kata sandi yang benar pada akun terverifikasi. | Pengguna masuk dan daftar percakapan tampil. | ☐ | |
| AUTH-09 | Masuk dengan kata sandi salah atau email yang tidak terdaftar. | Masuk ditolak dengan pesan kesalahan; halaman tidak berubah menjadi sesi masuk. | ☐ | |
| AUTH-10 | Masuk dengan akun yang belum terverifikasi. | Aplikasi meminta verifikasi dan menyediakan kode demo baru. | ☐ | |
| AUTH-11 | Keluar melalui menu akun, lalu coba muat ulang atau membuka endpoint percakapan. | Sesi berakhir, halaman masuk tampil, dan data/API yang memerlukan akun tidak dapat diakses tanpa masuk kembali. | ☐ | |
| AUTH-12 | Kirim permintaan perubahan data tanpa token CSRF yang valid (misalnya dari alat pengujian lokal). | Permintaan ditolak dengan status keamanan, bukan mengubah data. | ☐ | |

## 3. Daftar dan pengelolaan percakapan

| ID | Checklist / langkah | Hasil yang diharapkan | Status | Catatan |
|---|---|---|---|---|
| CHAT-01 | Masuk pertama kali dengan akun baru. | Beberapa percakapan contoh dan pesan awal dibuat untuk akun tersebut. | ☐ | |
| CHAT-02 | Muat ulang halaman dan periksa percakapan contoh. | Percakapan tidak dibuat berulang kali. | ☐ | |
| CHAT-03 | Buat percakapan baru dengan nama valid dan tanpa nomor telepon. | Percakapan dibuat, muncul pada daftar, dan langsung dibuka. | ☐ | |
| CHAT-04 | Buat percakapan dengan nama kurang dari 2 karakter atau lebih dari 120 karakter. | Data ditolak dengan validasi yang sesuai. | ☐ | |
| CHAT-05 | Cari percakapan dengan nama kontak, nomor telepon, dan cuplikan pesan. | Daftar menyaring hasil yang cocok; kata kunci tanpa hasil menampilkan keadaan kosong. | ☐ | |
| CHAT-06 | Uji filter Semua, Grup, Belum dibaca, dan Diarsipkan. | Setiap filter menampilkan daftar yang sesuai dengan data lokal yang tersedia. | ☐ | |
| CHAT-07 | Ganti nama kontak melalui tombol ubah nama. | Nama dan inisial avatar diperbarui pada tampilan dan tetap tersimpan setelah daftar dimuat ulang. | ☐ | |
| CHAT-08 | Ubah nama kontak menjadi kurang dari 2 karakter atau lebih dari 120 karakter. | Perubahan ditolak dan nama sebelumnya tetap tersimpan. | ☐ | |
| CHAT-09 | Arsipkan percakapan dan buka filter Diarsipkan. | Percakapan hilang dari daftar aktif dan muncul di daftar arsip. | ☐ | |
| CHAT-10 | Pulihkan percakapan dari daftar arsip. | Percakapan kembali ke daftar aktif. | ☐ | |
| CHAT-11 | Batalkan konfirmasi arsip atau hapus percakapan. | Tindakan dibatalkan dan data tidak berubah. | ☐ | |
| CHAT-12 | Hapus percakapan setelah menyetujui konfirmasi. | Percakapan dan pesan terkait hilang; percakapan tidak muncul kembali setelah muat ulang. | ☐ | |
| CHAT-13 | Dengan dua akun berbeda, coba membuka atau mengubah ID percakapan milik akun lain melalui permintaan lokal. | Akses ditolak; data percakapan akun lain tidak terbaca atau berubah. | ☐ | |

## 4. Pesan teks

| ID | Checklist / langkah | Hasil yang diharapkan | Status | Catatan |
|---|---|---|---|---|
| MSG-01 | Buka percakapan tanpa pesan. | Area percakapan menampilkan petunjuk untuk memulai percakapan. | ☐ | |
| MSG-02 | Kirim pesan teks yang tidak kosong menggunakan tombol kirim. | Pesan tampil pada percakapan dan cuplikan pesan di daftar diperbarui. | ☐ | |
| MSG-03 | Kirim pesan menggunakan Enter; lalu uji Shift+Enter untuk baris baru. | Enter mengirim pesan dan Shift+Enter membuat baris baru. | ☐ | |
| MSG-04 | Coba kirim pesan kosong tanpa lampiran. | Tidak ada pesan kosong yang dibuat. | ☐ | |
| MSG-05 | Kirim teks dengan spasi di awal/akhir, beberapa baris, serta karakter Unicode/emoji. | Spasi tepi dirapikan; isi lain tampil dan tersimpan dengan benar. | ☐ | |
| MSG-06 | Kirim pesan mendekati batas 5.000 karakter; coba juga masukan yang melebihi batas dari permintaan langsung. | Masukan dalam batas dapat dikirim; isi di luar batas tidak tersimpan sebagai pesan yang lebih panjang dari batas. | ☐ | |
| MSG-07 | Muat ulang halaman, buka percakapan lagi, dan periksa urutan pesan. | Pesan tersimpan dan ditampilkan dalam urutan waktu yang benar. | ☐ | |
| MSG-08 | Ubah pesan milik sendiri menjadi teks baru. | Isi diperbarui dan penanda “diedit” tampil. | ☐ | |
| MSG-09 | Coba ubah pesan menjadi kosong, lebih dari 5.000 karakter, atau pesan milik akun/kontak lain. | Perubahan ditolak; pesan yang tidak dimiliki pengguna tidak dapat diubah. | ☐ | |
| MSG-10 | Hapus pesan milik sendiri, batalkan konfirmasi, lalu ulangi dan setujui. | Pembatalan tidak menghapus pesan; persetujuan menghapus pesan dari tampilan dan penyimpanan. | ☐ | |
| MSG-11 | Coba menghapus pesan yang tidak ada atau bukan milik akun sendiri melalui permintaan lokal. | Pesan tidak dapat dihapus oleh akun yang tidak berhak. | ☐ | |
| MSG-12 | Buka percakapan lain, lalu kembali ke percakapan awal. | Pesan masing-masing percakapan tetap terpisah dan tampil dengan benar. | ☐ | |

## 5. Lampiran

| ID | Checklist / langkah | Hasil yang diharapkan | Status | Catatan |
|---|---|---|---|---|
| FILE-01 | Kirim gambar JPG, PNG, GIF, dan WEBP, masing-masing sendiri dan bersama teks. | Lampiran tersimpan dan pratinjau gambar dapat dibuka. | ☐ | |
| FILE-02 | Kirim video MP4, WEBM, OGV, atau MOV yang valid. | Video tampil dengan kontrol pemutaran browser dan dapat diputar jika codec didukung browser. | ☐ | |
| FILE-03 | Kirim PDF, TXT, ZIP, DOCX, dan XLSX yang valid. | Pesan menampilkan kartu lampiran; berkas dapat dibuka atau diunduh. | ☐ | |
| FILE-04 | Kirim lampiran tanpa teks. | Pesan lampiran tetap terkirim. | ☐ | |
| FILE-05 | Pilih lampiran lalu batalkan melalui tombol pada pratinjau. | Lampiran dibatalkan dan tidak ikut terkirim. | ☐ | |
| FILE-06 | Coba mengirim format yang tidak didukung atau berkas dengan tipe isi yang tidak sesuai ekstensi. | Server menolak tipe MIME yang tidak diizinkan. | ☐ | |
| FILE-07 | Uji berkas di bawah, tepat pada, dan di atas batas 12 MiB; perhatikan batas unggah PHP/server. | Berkas sampai 12 MiB dapat diterima bila konfigurasi server mengizinkan; berkas lebih besar ditolak dengan pesan batas ukuran. | ☐ | |
| FILE-08 | Buka URL lampiran saat masuk, lalu coba URL lampiran milik akun lain atau tanpa sesi. | Pemilik dapat mengakses lampiran; pengguna tanpa hak tidak dapat mengambilnya. | ☐ | |
| FILE-09 | Hapus pesan berlampiran dan hapus percakapan yang berisi lampiran. | Pesan/percakapan terhapus dan berkas terkait tidak lagi dapat diambil. | ☐ | |

## 6. Cadangan dan pemulihan

| ID | Checklist / langkah | Hasil yang diharapkan | Status | Catatan |
|---|---|---|---|---|
| BACK-01 | Unduh cadangan saat masuk. | Berkas JSON terunduh dengan format `whatsapp-backup`, versi 1, waktu ekspor, daftar percakapan, pesan, dan data lampiran bila tersedia. | ☐ | |
| BACK-02 | Ekspor lalu pulihkan cadangan yang sama. | Percakapan, status arsip, pesan, waktu, dan lampiran dipulihkan dengan benar. | ☐ | |
| BACK-03 | Pastikan data tujuan berisi percakapan berbeda, lalu pulihkan cadangan. | Data percakapan akun yang sedang masuk diganti dengan isi cadangan; peringatan konfirmasi ditampilkan. | ☐ | |
| BACK-04 | Batalkan konfirmasi pemulihan. | Tidak ada data yang berubah. | ☐ | |
| BACK-05 | Pulihkan JSON yang rusak, format yang salah, atau jumlah percakapan lebih dari 500. | Pemulihan ditolak, pesan kesalahan ditampilkan, dan data lama tetap utuh. | ☐ | |
| BACK-06 | Pulihkan cadangan dengan lampiran rusak, MIME yang tidak didukung, lampiran lebih dari 12 MiB, atau total lampiran lebih dari 25 MiB. | Pemulihan ditolak dan perubahan transaksi serta berkas sementara dibersihkan; data lama tetap utuh. | ☐ | |
| BACK-07 | Uji pemulihan dengan banyak pesan (termasuk lebih dari 5.000 pesan pada satu percakapan). | Tidak lebih dari 5.000 pesan per percakapan yang dipulihkan; aplikasi tetap memberikan hasil yang konsisten. | ☐ | |
| BACK-08 | Pulihkan berkas JSON yang bukan cadangan valid dari pemilih berkas. | Kesalahan ditampilkan tanpa mengganti percakapan saat ini. | ☐ | |

## 7. Tampilan, navigasi, dan aksesibilitas dasar

| ID | Checklist / langkah | Hasil yang diharapkan | Status | Catatan |
|---|---|---|---|---|
| UI-01 | Buka menu pengaturan dan pilih tema terang, lalu tema gelap. Muat ulang halaman. | Tema berubah dan pilihan terakhir tetap tersimpan pada browser/perangkat tersebut. | ☐ | |
| UI-02 | Uji menu akun, tombol keluar, menu lampiran, serta tombol tutup modal. | Kontrol membuka/menutup bagian yang sesuai; klik di luar modal atau Escape menutup modal. | ☐ | |
| UI-03 | Uji pintasan Ctrl+K (Windows/Linux) atau Cmd+K (macOS). | Fokus berpindah ke kolom pencarian percakapan. | ☐ | |
| UI-04 | Navigasikan daftar percakapan memakai Tab, Enter, dan Space. | Percakapan dapat dipilih menggunakan keyboard dan fokus terlihat. | ☐ | |
| UI-05 | Uji pada lebar desktop, tablet, dan ponsel (termasuk lebar 320 px); buka percakapan dan kembali ke daftar. | Konten dapat digunakan tanpa kontrol utama tertutup; navigasi kembali berfungsi pada tampilan ponsel. | ☐ | |
| UI-06 | Uji pesan panjang, nama kontak panjang, daftar kosong, serta toast kesalahan/berhasil. | Konten tidak merusak tata letak dan umpan balik dapat dibaca. | ☐ | |

## 8. Fitur yang perlu dikonfirmasi sebelum dinyatakan lulus

Bagian ini mencatat kontrol atau pemanggilan yang terlihat pada antarmuka, tetapi belum memiliki dukungan yang cocok di API/skema saat checklist dibuat. Jalankan pengujian untuk mengonfirmasi status aktual; tandai gagal atau belum tersedia jika perilaku yang diharapkan tidak tercapai.

| ID | Fitur yang diperiksa | Kriteria untuk dinyatakan lulus | Status | Catatan |
|---|---|---|---|---|
| GAP-01 | Tombol **Balas** pada pesan dan pengiriman pesan balasan. | Hubungan ke pesan asal tersimpan dan ditampilkan kembali setelah percakapan dimuat ulang. | ☐ | |
| GAP-02 | Pembaruan pesan baru otomatis tanpa memuat ulang halaman. | Endpoint polling tersedia dan pesan baru muncul otomatis; konsol browser tidak menunjukkan kegagalan endpoint. | ☐ | |
| GAP-03 | Indikator “sedang mengetik”. | Status mengetik dapat dikirim dan ditampilkan dengan benar tanpa permintaan API gagal. | ☐ | |
| GAP-04 | Status belum dibaca dan tanda baca pesan. | Nilainya berasal dari status pesan yang konsisten, bukan hanya penanda lokal browser atau properti respons yang tidak tersedia. | ☐ | |

## Catatan hasil dan tindak lanjut

| ID kasus | Temuan / bukti (misalnya pesan kesalahan, tangkapan layar, atau langkah reproduksi) | Prioritas | Tindak lanjut |
|---|---|---|---|
| | | | |
| | | | |
| | | | |
