# Pemetaan Kebutuhan DPPL WhatsApp

Dokumen ini memetakan kode kebutuhan yang diberikan ke implementasi pada repository saat ini. Status menunjukkan dukungan yang tersedia di kode, bukan hasil pengujian operasional.

| Kode Kebutuhan | Pemetaan Implementasi | Status |
|---|---|---|
| FR-01 (Registrasi dan Login) | Pengguna dapat mendaftar dengan nama, email, nomor telepon opsional, dan kata sandi; memverifikasi akun memakai kode demo enam digit; masuk dan keluar. Kata sandi dan kode verifikasi disimpan dalam bentuk hash. Kode demo ditampilkan di aplikasi, belum dikirim melalui layanan email. | Sebagian |
| FR-02 (Pengelolaan Profil) | Nama, email, dan nomor telepon disimpan pada akun dan nama/email ditampilkan pada menu akun. Belum tersedia alur untuk melihat atau mengubah profil, mengganti kata sandi, atau mengelola foto profil. | Sebagian |
| FR-03 (Pesan Pribadi) | Pengguna dapat membuat percakapan kontak, membaca pesan, mengirim pesan teks, serta mengubah dan menghapus pesan miliknya. Data percakapan dan pesan disimpan di database lokal. Implementasi saat ini belum mengirimkan pesan ke akun penerima lain; pesan kontak yang tersedia berasal dari data contoh. | Sebagian |
| FR-04 (Obrolan Grup) | Terdapat contoh percakapan berlabel grup dan filter grup yang mengenali label melalui teks nomor kontak. Belum ada model anggota/grup, pengelolaan anggota, atau pengiriman pesan grup ke beberapa pengguna. | Belum diimplementasikan |
| FR-05 (Pengiriman Media) | Pesan dapat menyertakan gambar, video, dan dokumen yang diizinkan. Berkas disimpan di server, diakses melalui endpoint yang memeriksa kepemilikan akun, dan dibatasi maksimal 12 MiB per berkas. Format yang didukung mencakup JPG, PNG, GIF, WEBP, MP4, WEBM, OGV, MOV, PDF, TXT, ZIP, DOCX, dan XLSX. | Sebagian |
| FR-06 (Panggilan Suara dan Video) | Belum tersedia fitur atau integrasi untuk memulai maupun menerima panggilan suara/video. | Belum diimplementasikan |
| G-01 (Pencadangan Chat) | Pengguna dapat mengunduh cadangan JSON berisi percakapan, pesan, status arsip, dan data lampiran dalam Base64. Total lampiran pada ekspor dibatasi 25 MiB. | Diimplementasikan |
| G-02 (Persiapan Pemulihan) | Pengguna memilih berkas JSON, aplikasi mem-parsing berkas dan meminta konfirmasi sebelum pemulihan. Server memvalidasi format cadangan serta batas jumlah percakapan, ukuran lampiran, dan tipe MIME. Belum tersedia pratinjau isi atau pemeriksaan cadangan terpisah tanpa menjalankan pemulihan. | Sebagian |
| G-03 (Pemulihan Chat) | Cadangan yang valid menggantikan seluruh percakapan milik akun yang sedang masuk. Perubahan data dilakukan dalam transaksi; lampiran dibatasi 12 MiB per berkas dan 25 MiB total. Pemulihan tidak mencakup data profil akun. | Diimplementasikan |

## Catatan implementasi dan batasan

- Aplikasi menggunakan PHP native, PDO, dan MySQL/MariaDB. Skema menyimpan akun pada `users`, percakapan pada `chats`, dan pesan/lampiran pada `messages`.
- Setiap percakapan dimiliki oleh satu akun. Skema saat ini tidak memuat relasi penerima akun lain atau keanggotaan grup, sehingga percakapan pribadi dan grup lintas pengguna belum tersedia.
- Kode verifikasi merupakan kode demo lokal, bukan verifikasi melalui email atau SMS.
- Media disimpan pada `storage/uploads`; akses unduhan melalui endpoint aplikasi memerlukan sesi dan pemeriksaan kepemilikan.
- Pemulihan mengganti data chat akun yang sedang aktif. Pengguna perlu membuat cadangan terlebih dahulu jika ingin menyimpan data lama.
- Status “Sebagian” berarti hanya sebagian alur pada kebutuhan tersebut yang telah didukung. Kebutuhan yang belum diimplementasikan memerlukan pengembangan sebelum dapat dinyatakan terpenuhi.
