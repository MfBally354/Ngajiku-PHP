# NgajiKu — Platform E-Learning Ngaji
Platform manajemen pembelajaran ngaji berbasis web (PHP + MySQL + Bootstrap 5).
---
DFD level 0
![WhatsApp Image 2026-04-02 at 12 24 23](https://github.com/user-attachments/assets/efcd5571-2bb2-4c77-82b3-d34e880fc4b6)
---
DFD level 1
<img width="1002" height="481" alt="image" src="https://github.com/user-attachments/assets/7340d924-cfb2-4676-b880-92dcf7ba6c96" />
---
<img width="1920" height="896" alt="Screenshot (803)" src="https://github.com/user-attachments/assets/6fd10101-e6ba-482b-b359-97ab05d744b3" />
---
<img width="2816" height="1536" alt="Gemini_Generated_Image_qcbnf9qcbnf9qcbn" src="https://github.com/user-attachments/assets/6cf9253f-3355-4148-b350-c705a5c95b00" />
---

## Fitur Utama

### 👤 Admin
- Kelola semua pengguna (tambah, edit, hapus, ubah status)
- Buat dan kelola kelas
- Kirim pengumuman ke semua role
- Lihat laporan & statistik platform

### 🧕 Ustad
- Dashboard dengan ringkasan kelas & tugas
- Upload materi (PDF, video, gambar, link, teks)
- Kategori materi: Al-Quran, Fiqih, Aqidah, Akhlak, Hadits, Doa, Bahasa Arab, Sirah
- Input nilai (harian, ulangan, ujian, hafalan, praktik)
- Nilai tugas yang dikumpulkan santri
- Catat absensi harian
- Rekap hafalan santri
- Kirim pengumuman

### 👨‍👩‍👧 Orang Tua
- Pantau nilai & progress anak ngaji
- Lihat status absensi anak
- Lihat tugas & status pengumpulan anak
- Terima pengumuman dari ustad/admin

### 🧒 Santri
- Akses materi pembelajaran
- Kumpulkan tugas (upload file atau teks)
- Lihat nilai dan riwayat penilaian
- Pantau absensi sendiri
- Lihat pengumuman

---

## Cara Install

### Prasyarat
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10+
- Apache / Nginx dengan mod_rewrite aktif

### Langkah Install

1. **Clone/copy folder** `ngajiku/` ke root server Anda (cth: `htdocs/ngajiku/`)

2. **Buat database:**
```sql
CREATE DATABASE ngajiku;
```

3. **Import SQL:**
```bash
mysql -u root -p ngajiku < database/ngajiku.sql
```

4. **Edit konfigurasi** di `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ngajiku');
define('DB_USER', 'root');      // sesuaikan
define('DB_PASS', '');          // sesuaikan
define('SITE_URL', 'http://localhost/ngajiku');
```

5. **Akses** di browser: `http://localhost/ngajiku`

---

## Akun Default

| Role      | Email                  | Password   |
|-----------|------------------------|------------|
| Admin     | admin@ngajiku.id       | password   |
| Ustad     | ahmad@ngajiku.id       | password   |
| Orang Tua | budi@ngajiku.id        | password   |
| Santri    | rafi@ngajiku.id        | password   |

> ⚠️ Ganti password semua akun setelah login pertama!

---

## Struktur Folder

```
ngajiku/
├── assets
│   ├── css
│   │   └── style.css
│   ├── images
│   │   └── articles
│   └── js
│       └── main.js
├── config
│   └── database.php
├── database
│   └── ngajiku.sql
├── database.sql
├── docker-compose.yml
├── Dockerfile
├── git.py
├── includes
│   ├── auth_check.php
│   ├── footer.php
│   ├── functions.php
│   └── header.php
├── index.php
├── info.php
├── login.php
├── logout.php
├── mysql-custom.cnf
├── pages
│   ├── admin
│   │   ├── dashboard.php
│   │   ├── kelas.php
│   │   ├── keuangan.php
│   │   ├── laporan.php
│   │   ├── materi.php
│   │   ├── pengumuman.php
│   │   ├── profil.php
│   │   └── users.php
│   ├── keuangan
│   │   ├── dashboard.php
│   │   ├── FileSQL.sql
│   │   ├── gaji.php
│   │   ├── laporan.php
│   │   ├── pengawasan.php
│   │   └── transaksi.php
│   ├── parent
│   │   ├── absensi.php
│   │   ├── anak.php
│   │   ├── dashboard.php
│   │   ├── materi.php
│   │   ├── nilai_anak.php
│   │   ├── pengumuman.php
│   │   └── profil.php
│   ├── santri
│   │   ├── absensi.php
│   │   ├── dashboard.php
│   │   ├── hafalan.php
│   │   ├── materi.php
│   │   ├── nilai.php
│   │   ├── pengumuman.php
│   │   ├── profil.php
│   │   └── tugas.php
│   └── ustad
│       ├── absensi.php
│       ├── dashboard.php
│       ├── hafalan.php
│       ├── kelas.php
│       ├── materi.php
│       ├── nilai.php
│       ├── pengumuman.php
│       ├── profil.php
│       └── tugas.php
├── README.md
├── router.php
├── sql
│   └── database.sql
├── test.php
└── uploads
    └── materi
        ├── 69bf8bbce63da_1774160828.pdf
        └── 69bf905ebfffe_1774162014.pdf
```
---


**Proses Bisnis**
<img width="548" height="586" alt="image" src="https://github.com/user-attachments/assets/9843da1f-cc43-4738-83f8-6ef9a3cda0f2" />
<img width="809" height="723" alt="image" src="https://github.com/user-attachments/assets/8339c017-ba2f-4370-85bc-8c2c129e4073" />
```
🔄 Alur Utama Sistem
Admin → Buat Kelas → Assign Ustad → Daftarkan Santri → Hubungkan ke Orang Tua
                                          ↓
                              Proses Pembelajaran Berjalan
                                          ↓
                    ┌─────────────────────┼─────────────────────┐
                    ↓                     ↓                     ↓
             Ustad Input            Ustad Catat           Ustad Buat
             Nilai Santri           Absensi Harian         Tugas
                    ↓                     ↓                     ↓
                    └─────────────────────┼─────────────────────┘
                                          ↓
                              Santri & Orang Tua
                              Bisa Memantau Progress

👤 Proses Per Role
1. Admin
Login sebagai Admin
    │
    ├── Kelola Pengguna
    │       ├── Tambah akun Ustad, Santri, Orang Tua
    │       ├── Edit / nonaktifkan akun
    │       └── Hubungkan akun Orang Tua ke Santri
    │
    ├── Kelola Kelas
    │       ├── Buat kelas baru
    │       ├── Assign Ustad pengampu
    │       └── Daftarkan Santri ke kelas
    │
    ├── Kelola Keuangan
    │       ├── Catat pemasukan (Infaq, SPP, Donasi)
    │       ├── Catat pengeluaran (Operasional, dll)
    │       ├── Atur & bayar gaji Ustad per bulan
    │       └── Lihat laporan & grafik keuangan
    │
    ├── Kirim Pengumuman
    │       └── Target: semua role / kelas tertentu
    │
    └── Lihat Laporan
            ├── Statistik platform
            ├── Rekap nilai per kelas
            └── Rekap absensi bulanan
2. Ustad
Login sebagai Ustad
    │
    ├── Lihat Dashboard
    │       ├── Ringkasan kelas yang diampu
    │       └── Notifikasi tugas belum dinilai
    │
    ├── Kelola Materi
    │       ├── Upload materi (PDF, video, gambar, link, teks)
    │       ├── Kategorikan (Al-Quran, Fiqih, Aqidah, dll)
    │       └── Atur visibilitas (publik / draft)
    │
    ├── Kelola Tugas
    │       ├── Buat tugas dengan deadline
    │       ├── Upload file soal (opsional)
    │       └── Nilai jawaban yang dikumpulkan santri
    │
    ├── Input Nilai
    │       ├── Nilai harian, ulangan, ujian, hafalan, praktik
    │       └── Lihat rekap nilai per santri
    │
    ├── Catat Absensi
    │       ├── Input hadir / izin / sakit / alpha per hari
    │       └── Lihat rekap absensi bulanan
    │
    ├── Rekap Hafalan
    │       ├── Catat hafalan santri (surah, ayat, doa, hadits)
    │       └── Beri nilai A/B/C/D
    │
    └── Kirim Pengumuman
            └── Target: santri & orang tua di kelasnya
3. Santri
Login sebagai Santri
    │
    ├── Lihat Dashboard
    │       ├── Ringkasan kelas aktif
    │       ├── Tugas yang belum dikumpulkan
    │       └── Nilai terbaru
    │
    ├── Akses Materi
    │       ├── Baca / tonton materi dari Ustad
    │       └── Filter berdasarkan kategori
    │
    ├── Kumpulkan Tugas
    │       ├── Upload file jawaban atau tulis teks
    │       └── Lihat status & nilai tugas
    │
    ├── Pantau Nilai
    │       ├── Nilai harian, ulangan, ujian
    │       └── Nilai tugas yang sudah dinilai
    │
    ├── Lihat Absensi
    │       └── Rekap kehadiran per bulan
    │
    ├── Lihat Hafalan
    │       └── Riwayat hafalan & nilai dari Ustad
    │
    └── Lihat Pengumuman
            └── Dari Ustad dan Admin
4. Orang Tua
Login sebagai Orang Tua
    │
    ├── Lihat Dashboard Anak
    │       ├── Rata-rata nilai anak
    │       ├── Kehadiran bulan ini
    │       └── Status tugas anak
    │
    ├── Pantau Nilai Anak
    │       ├── Nilai harian, ulangan, ujian
    │       └── Nilai tugas
    │
    ├── Pantau Absensi Anak
    │       └── Rekap hadir / izin / sakit / alpha
    │
    ├── Lihat Materi
    │       └── Materi yang diajarkan ke anak
    │
    └── Lihat Pengumuman
            └── Dari Ustad dan Admin

💰 Proses Bisnis Keuangan
Admin Buka Modul Keuangan
    │
    ├── Pemasukan
    │       ├── Infaq / Sedekah dari santri atau donatur
    │       ├── SPP / Iuran bulanan per santri
    │       └── Donasi lainnya
    │
    ├── Pengeluaran
    │       ├── Gaji Ustad (dibayar per bulan)
    │       ├── Operasional (listrik, air, ATK, dll)
    │       └── Pengeluaran lain-lain
    │
    ├── Manajemen Gaji Ustad
    │       ├── Admin atur gaji pokok per ustad
    │       ├── Setiap bulan admin klik "Bayar"
    │       └── Otomatis tercatat sebagai pengeluaran
    │
    └── Laporan
            ├── Grafik pemasukan vs pengeluaran
            ├── Rekap per kategori
            ├── Laporan bulanan & tahunan
            └── Log pengawasan semua transaksi

🔐 Alur Keamanan
Setiap Request Masuk
    │
    ├── Cek Session → Belum login? → Redirect ke /login.php
    │
    ├── Cek Role → Role tidak sesuai? → Redirect ke /login.php
    │
    ├── Sanitasi Input → htmlspecialchars() untuk semua input
    │
    ├── Query Database → PDO Prepared Statements (anti SQL Injection)
    │
    └── Upload File → Validasi tipe & ukuran file

📊 Relasi Antar Entitas
Admin
  └── Membuat ──→ Kelas ←── Diampu oleh ── Ustad
                   │
                   └── Diikuti oleh ──→ Santri ←── Dipantau oleh ── Orang Tua
                                          │
                              ┌───────────┼───────────┐
                              ↓           ↓           ↓
                           Nilai      Absensi      Tugas
                                                     │
                                              Dikumpulkan
                                              Dinilai Ustad
```
## Proses Bisnis (Business Process)
Proses bisnis menjelaskan bagaimana alur kerja antar aktor dalam sistem untuk mencapai tujuan lembaga pengajian. Berdasarkan diagram konteks yang Anda unggah, berikut adalah alur utamanya:

- Manajemen Pengguna & Sistem (Admin): Admin melakukan registrasi data user (santri, ustadz, orang tua) dan melakukan konfigurasi sistem serta memantau log aktivitas untuk memastikan sistem berjalan lancar.

- Proses Belajar Mengajar (Ustadz & Santri): Ustadz mengunggah materi pembelajaran dan memberikan tugas kepada santri. Santri mengakses materi tersebut, mengerjakan, dan mengirimkan jawaban kembali ke sistem untuk dinilai oleh ustadz.

- Evaluasi & Monitoring (Ustadz & Orang Tua): Ustadz menginput nilai (harian, hafalan, ujian) dan absensi harian. Data ini diolah sistem menjadi laporan perkembangan (rapor) yang dapat diakses oleh orang tua sebagai sarana monitoring hasil evaluasi belajar anak.

- Pengelolaan Keuangan (Pengelola Keuangan): Sistem menyediakan data rekap kehadiran santri sebagai dasar penagihan. Pengelola keuangan mengelola data tagihan dan pembayaran santri, mencatat infaq, serta mengajukan permintaan pembayaran gaji guru berdasarkan data kehadiran yang terekam di sistem.

## Cara Kerja Kode (System Logic)
Aplikasi ini dibangun menggunakan arsitektur Three-Tier (Presentation, Logic, dan Data Layer) dengan alur kerja sebagai berikut:

**A. Alur Inisialisasi & Keamanan**
- Koneksi Database: File config/database.php dipanggil di setiap halaman untuk membuka koneksi ke MySQL menggunakan PDO atau MySQLi agar data dapat ditarik.

- Autentikasi (Auth Check): Sebelum halaman dimuat, file includes/auth_check.php memeriksa apakah pengguna sudah login. Jika belum, pengguna diarahkan kembali ke login.php.

- Verifikasi Role (Routing): File router.php memeriksa tingkatan akses (role) pengguna (Admin, Ustadz, dll.) untuk memastikan mereka hanya dapat mengakses folder halaman (pages/) yang sesuai dengan hak aksesnya.

**B. Pengolahan Data (CRUD & Logic)**
- Input Data: Saat pengguna mengisi form (misalnya input infaq di dashboard.php), data dikirim melalui metode POST ke server.

- Sanitasi & Keamanan: Kode menggunakan htmlspecialchars() untuk membersihkan input dari script berbahaya dan Prepared Statements untuk mencegah serangan SQL Injection sebelum data disimpan ke tabel database.

- Penyajian Visual: Di halaman seperti laporan.php, kode PHP melakukan query agregasi (seperti SUM atau COUNT) untuk mengambil total keuangan atau jumlah santri, lalu data tersebut dioper ke pustaka JavaScript (seperti Chart.js) untuk ditampilkan dalam bentuk diagram.

**C. Manajemen File**
- Sistem menyimpan referensi nama file di database, sementara file fisik (seperti PDF materi atau gambar tugas) disimpan di folder uploads/materi/ atau uploads/tugas/. Saat santri ingin belajar, sistem akan memanggil path file tersebut untuk ditampilkan di browser.

## Menghubungkan akun parent ke santri
- Cara menghubungkan akun parent ke santri dilakukan langsung di database
- ```sudo mysql ngajiku```
- ```SELECT id, nama, role FROM users;```
- Setelah tahu ID-nya, misalnya parent ID=3 dan santri ID=4:
-  ```INSERT INTO parent_santri (parent_id, santri_id) VALUES (3, 4);```

---
---

## Keamanan
- Password di-hash dengan bcrypt (password_hash)
- Query database menggunakan PDO Prepared Statements (cegah SQL Injection)
- Input di-sanitize dengan htmlspecialchars
- Role-based access control di setiap halaman
- Session regenerate setelah login
- Upload file dibatasi tipe & ukuran

---

## Teknologi
- **Backend**: PHP 8.x + PDO MySQL
- **Frontend**: Bootstrap 5.3, Font Awesome 6, Plus Jakarta Sans, Amiri (Arab)
- **Database**: MySQL / MariaDB
- **Server**: Apache/Nginx + PHP

---

*Dibuat dengan ❤️ untuk kemudahan belajar Al-Quran*
