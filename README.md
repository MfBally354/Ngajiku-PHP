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
