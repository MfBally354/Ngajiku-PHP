CREATE TABLE log_keuangan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kategori ENUM('Masuk', 'Keluar', 'Infaq'),
    keterangan VARCHAR(255),
    jumlah INT,
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);