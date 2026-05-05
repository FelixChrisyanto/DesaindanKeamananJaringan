CREATE DATABASE IF NOT EXISTS dkj_apotek_sederhana;
USE dkj_apotek_sederhana;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS obat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    kategori VARCHAR(50),
    stok INT DEFAULT 0,
    satuan VARCHAR(20),
    harga_beli DECIMAL(10, 2),
    harga_jual DECIMAL(10, 2)
);

CREATE TABLE IF NOT EXISTS supplier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(255),
    alamat TEXT,
    telepon TEXT,
    email_enc TEXT
);

CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_transaksi VARCHAR(50) NOT NULL UNIQUE,
    tanggal DATE NOT NULL,
    nama_pasien VARCHAR(100),
    nik_pasien_enc TEXT,
    metode_bayar VARCHAR(20),
    nomor_pembayaran_enc TEXT,
    is_bpjs BOOLEAN DEFAULT 0,
    nomor_asuransi_enc TEXT,
    supplier_id INT,
    total DECIMAL(10, 2),
    status VARCHAR(20) DEFAULT 'Selesai',
    FOREIGN KEY (supplier_id) REFERENCES supplier(id)
);

CREATE TABLE IF NOT EXISTS resep (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_resep VARCHAR(20) NOT NULL UNIQUE,
    nama_pasien VARCHAR(100) NOT NULL,
    nik_enc TEXT,
    nama_dokter VARCHAR(100) NOT NULL,
    isi_resep_enc TEXT NOT NULL,
    catatan_alergi_enc TEXT,
    tanggal DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'Menunggu'
);

CREATE TABLE IF NOT EXISTS detail_transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT,
    obat_id INT,
    jumlah INT,
    harga_satuan DECIMAL(10, 2),
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
    FOREIGN KEY (obat_id) REFERENCES obat(id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
