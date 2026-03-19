-- =============================================
-- NgajiKu - Database Schema
-- =============================================

CREATE DATABASE IF NOT EXISTS ngajiku CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ngajiku;

-- Tabel Users (semua role)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','ustad','parent','santri') NOT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    telepon VARCHAR(20) DEFAULT NULL,
    alamat TEXT DEFAULT NULL,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Kelas
CREATE TABLE kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    ustad_id INT NOT NULL,
    jadwal VARCHAR(100),
    lokasi VARCHAR(100),
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ustad_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Relasi santri ke kelas
CREATE TABLE santri_kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    kelas_id INT NOT NULL,
    tanggal_masuk DATE DEFAULT (CURRENT_DATE),
    UNIQUE KEY unique_santri_kelas (santri_id, kelas_id),
    FOREIGN KEY (santri_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE
);

-- Relasi orang tua ke santri
CREATE TABLE parent_santri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NOT NULL,
    santri_id INT NOT NULL,
    UNIQUE KEY unique_parent_santri (parent_id, santri_id),
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (santri_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Kategori Materi
CREATE TABLE kategori_materi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    ikon VARCHAR(50) DEFAULT 'fa-book',
    warna VARCHAR(20) DEFAULT '#2E7D32'
);

-- Isi kategori default
INSERT INTO kategori_materi (nama, ikon, warna) VALUES
('Al-Quran & Tajwid', 'fa-book-quran', '#2E7D32'),
('Fiqih', 'fa-mosque', '#1565C0'),
('Aqidah', 'fa-star-and-crescent', '#6A1B9A'),
('Akhlak', 'fa-heart', '#C62828'),
('Hadits', 'fa-scroll', '#E65100'),
('Doa Harian', 'fa-hands', '#00695C'),
('Bahasa Arab', 'fa-language', '#F57F17'),
('Sirah Nabawi', 'fa-history', '#4E342E');

-- Tabel Materi
CREATE TABLE materi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    konten LONGTEXT,
    kategori_id INT,
    kelas_id INT,
    ustad_id INT NOT NULL,
    file_path VARCHAR(255),
    tipe_file ENUM('pdf','video','gambar','link','teks') DEFAULT 'teks',
    link_eksternal VARCHAR(500),
    urutan INT DEFAULT 0,
    status ENUM('publik','draft') DEFAULT 'publik',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori_materi(id),
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE SET NULL,
    FOREIGN KEY (ustad_id) REFERENCES users(id)
);

-- Tabel Tugas
CREATE TABLE tugas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    deskripsi TEXT,
    kelas_id INT NOT NULL,
    ustad_id INT NOT NULL,
    deadline DATETIME,
    file_soal VARCHAR(255),
    max_nilai INT DEFAULT 100,
    status ENUM('aktif','selesai','draft') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
    FOREIGN KEY (ustad_id) REFERENCES users(id)
);

-- Pengumpulan Tugas
CREATE TABLE pengumpulan_tugas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tugas_id INT NOT NULL,
    santri_id INT NOT NULL,
    file_jawaban VARCHAR(255),
    teks_jawaban TEXT,
    waktu_kumpul TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    nilai INT DEFAULT NULL,
    catatan_ustad TEXT,
    status ENUM('dikumpulkan','dinilai','terlambat') DEFAULT 'dikumpulkan',
    UNIQUE KEY unique_kumpul (tugas_id, santri_id),
    FOREIGN KEY (tugas_id) REFERENCES tugas(id) ON DELETE CASCADE,
    FOREIGN KEY (santri_id) REFERENCES users(id)
);

-- Tabel Nilai
CREATE TABLE nilai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    kelas_id INT NOT NULL,
    ustad_id INT NOT NULL,
    jenis ENUM('harian','ulangan','ujian','hafalan','praktik') NOT NULL,
    mata_pelajaran VARCHAR(100),
    nilai_angka DECIMAL(5,2) NOT NULL,
    keterangan TEXT,
    tanggal DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (santri_id) REFERENCES users(id),
    FOREIGN KEY (kelas_id) REFERENCES kelas(id),
    FOREIGN KEY (ustad_id) REFERENCES users(id)
);

-- Tabel Absensi
CREATE TABLE absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    kelas_id INT NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('hadir','izin','sakit','alpha') NOT NULL,
    keterangan VARCHAR(255),
    dicatat_oleh INT,
    UNIQUE KEY unique_absensi (santri_id, kelas_id, tanggal),
    FOREIGN KEY (santri_id) REFERENCES users(id),
    FOREIGN KEY (kelas_id) REFERENCES kelas(id),
    FOREIGN KEY (dicatat_oleh) REFERENCES users(id)
);

-- Tabel Pengumuman
CREATE TABLE pengumuman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    isi TEXT NOT NULL,
    penulis_id INT NOT NULL,
    target_role SET('admin','ustad','parent','santri') DEFAULT 'admin,ustad,parent,santri',
    kelas_id INT DEFAULT NULL,
    tanggal_mulai DATE,
    tanggal_selesai DATE,
    prioritas ENUM('normal','penting','darurat') DEFAULT 'normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (penulis_id) REFERENCES users(id),
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE SET NULL
);

-- Tabel Hafalan (fitur tambahan)
CREATE TABLE hafalan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    kelas_id INT NOT NULL,
    ustad_id INT NOT NULL,
    jenis ENUM('surah','ayat','doa','hadits') DEFAULT 'surah',
    nama_hafalan VARCHAR(200) NOT NULL,
    nilai ENUM('A','B','C','D') DEFAULT 'B',
    tanggal DATE NOT NULL,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (santri_id) REFERENCES users(id),
    FOREIGN KEY (kelas_id) REFERENCES kelas(id),
    FOREIGN KEY (ustad_id) REFERENCES users(id)
);

-- =============================================
-- Data Default
-- =============================================

-- Admin default (password: admin123)
INSERT INTO users (nama, email, password, role) VALUES
('Administrator', 'admin@ngajiku.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uRpFERe6i', 'admin'),
('Ustadz Ahmad', 'ahmad@ngajiku.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uRpFERe6i', 'ustad'),
('Bapak Budi', 'budi@ngajiku.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uRpFERe6i', 'parent'),
('Muhammad Rafi', 'rafi@ngajiku.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uRpFERe6i', 'santri');
-- Password semua akun default: password
