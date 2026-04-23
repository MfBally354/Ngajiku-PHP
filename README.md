# NgajiKu — Platform E-Learning Ngaji
123
Platform manajemen pembelajaran ngaji berbasis web (PHP + MySQL + Bootstrap 5).
![WhatsApp Image 2026-04-02 at 12 24 23](https://github.com/user-attachments/assets/efcd5571-2bb2-4c77-82b3-d34e880fc4b6)
<img width="1920" height="896" alt="Screenshot (803)" src="https://github.com/user-attachments/assets/6fd10101-e6ba-482b-b359-97ab05d744b3" />

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
├── config/
│   └── database.php          # Koneksi DB & konstanta
├── includes/
│   ├── auth_check.php        # Middleware auth
│   ├── functions.php         # Helper functions
│   ├── header.php            # Layout header + sidebar
│   └── footer.php            # Layout footer
├── assets/
│   ├── css/style.css         # Stylesheet utama (tema hijau)
│   └── js/main.js            # JavaScript
├── uploads/
│   ├── materi/               # File materi (PDF, gambar, video)
│   └── tugas/                # File tugas santri
├── pages/
│   ├── admin/                # Halaman khusus admin
│   │   ├── dashboard.php
│   │   ├── users.php
│   │   ├── kelas.php
│   │   ├── pengumuman.php
│   │   └── laporan.php
│   ├── ustad/                # Halaman khusus ustad
│   │   ├── dashboard.php
│   │   ├── materi.php
│   │   ├── nilai.php
│   │   ├── tugas.php
│   │   ├── absensi.php
│   │   ├── hafalan.php
│   │   └── pengumuman.php
│   ├── parent/               # Halaman khusus orang tua
│   │   ├── dashboard.php
│   │   ├── nilai_anak.php
│   │   ├── materi.php
│   │   └── absensi.php
│   └── santri/               # Halaman khusus santri
│       ├── dashboard.php
│       ├── materi.php
│       ├── tugas.php
│       ├── nilai.php
│       └── absensi.php
├── database/
│   └── ngajiku.sql           # Skema & data awal
├── index.php                 # Redirect ke login/dashboard
├── login.php                 # Halaman login
├── logout.php                # Proses logout
└── router.php                # Redirect berdasarkan role
```
---


**Proses Bisnis**
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
