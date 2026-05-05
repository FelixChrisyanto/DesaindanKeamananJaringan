<?php
require_once 'config.php';
require_once 'encryption_helper.php';

echo "<h2>Generating Dummy Data...</h2>";

// 1. Clear existing data (Optional, but good for clean dummy data)
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("TRUNCATE TABLE detail_transaksi");
$conn->query("TRUNCATE TABLE transaksi");
$conn->query("TRUNCATE TABLE obat");
$conn->query("TRUNCATE TABLE supplier");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// 2. Insert Suppliers
$suppliers = [
    ['S001', 'Kimia Farma Distribusi', 'Jl. Budi Utomo No. 1, Jakarta', '021-1234567', 'contact@kimiafarma.id'],
    ['S002', 'Bio Farma PT', 'Jl. Pasteur No. 28, Bandung', '022-7654321', 'info@biofarma.co.id'],
    ['S003', 'Sanbe Farma', 'Jl. Tamansari No. 10, Bandung', '022-1112223', 'sales@sanbe.com'],
    ['S004', 'Kalbe Farma', 'Jl. Letjen Suprapto Kav. 4, Jakarta', '021-42873888', 'info@kalbe.co.id'],
    ['S005', 'Dexa Medica', 'Jl. Jend. Bambang Utoyo No. 138, Palembang', '0711-311390', 'customer.care@dexa-medica.com']
];

foreach ($suppliers as $s) {
    $email_enc = vigenere_encrypt($s[4], $VIGENERE_KEY);
    $stmt = $conn->prepare("INSERT INTO supplier (kode, nama, alamat, telepon, email_enc) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $s[0], $s[1], $s[2], $s[3], $email_enc);
    $stmt->execute();
}
echo "5 Suppliers inserted.<br>";

// 3. Insert Obat
$obats = [
    ['B001', 'Paracetamol 500mg', 'Tablet', 100, 'Strip', '5000', 7500],
    ['B002', 'Amoxicillin 500mg', 'Antibiotik', 50, 'Strip', '12000', 15000],
    ['B003', 'Cetirizine', 'Alergi', 30, 'Box', '25000', 35000],
    ['B004', 'Vitamin C 1000mg', 'Suplemen', 200, 'Botol', '45000', 55000],
    ['B005', 'Diapet NR', 'Pencernaan', 80, 'Strip', '8000', 12000],
    ['B006', 'Omeprazole 20mg', 'Lambung', 40, 'Capsule', '15000', 22000],
    ['B007', 'Sangobion', 'Suplemen', 60, 'Strip', '18000', 25000],
    ['B008', 'Panadol Extra', 'Pereda Nyeri', 120, 'Strip', '9000', 14000],
    ['B009', 'Loperamide', 'Pencernaan', 50, 'Strip', '4000', 6500],
    ['B010', 'Antangin JRG', 'Herbal', 100, 'Sachet', '2500', 4000]
];

foreach ($obats as $o) {
    $stmt = $conn->prepare("INSERT INTO obat (kode, nama, kategori, stok, satuan, harga_beli, harga_jual) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisdd", $o[0], $o[1], $o[2], $o[3], $o[4], $o[5], $o[6]);
    $stmt->execute();
}
echo "10 Medicines inserted.<br>";

// 4. Insert Transactions
$transaksi_data = [
    ['POS-A1B2C3', date('Y-m-d'), 'Ahmad Faisal', [[1, 2], [5, 1], [10, 3]]], 
    ['POS-D4E5F6', date('Y-m-d'), 'Siti Aminah', [[2, 1], [8, 1]]],
    ['POS-G7H8I9', date('Y-m-d', strtotime('-1 day')), 'Budi Santoso', [[3, 1], [4, 1], [1, 5]]],
    ['POS-J0K1L2', date('Y-m-d', strtotime('-1 day')), 'Dewi Sartika', [[6, 2], [7, 1]]],
    ['POS-M3N4O5', date('Y-m-d', strtotime('-2 days')), 'Eko Prasetyo', [[9, 4], [10, 10]]],
    ['POS-P6Q7R8', date('Y-m-d', strtotime('-2 days')), 'Aveline Ong', [[5, 2], [8, 2], [1, 1]]],
    ['POS-S9T0U1', date('Y-m-d', strtotime('-3 days')), 'Hendra Wijaya', [[2, 2], [3, 2]]],
    ['POS-V2W3X4', date('Y-m-d', strtotime('-4 days')), 'Ani Maryani', [[4, 2], [7, 2], [10, 5]]]
];

foreach ($transaksi_data as $t) {
    $no_trx = $t[0];
    $tgl = $t[1];
    $pasien = $t[2];
    $items = $t[3];
    
    $total_transaksi = 0;
    foreach ($items as $item) {
        $res = $conn->query("SELECT harga_jual FROM obat WHERE id = " . $item[0]);
        $o_data = $res->fetch_assoc();
        $total_transaksi += $o_data['harga_jual'] * $item[1];
    }

    $nik_enc = vigenere_encrypt('1234567890123456', $VIGENERE_KEY);
    $metode = (rand(0, 2) == 0) ? 'Tunai' : ((rand(0, 1) == 0) ? 'Kartu' : 'E-Wallet');

    $stmt = $conn->prepare("INSERT INTO transaksi (no_transaksi, tanggal, nama_pasien, nik_pasien_enc, metode_bayar, total) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssd", $no_trx, $tgl, $pasien, $nik_enc, $metode, $total_transaksi);
    $stmt->execute();
    $t_id = $conn->insert_id;

    foreach ($items as $item) {
        $res = $conn->query("SELECT harga_jual FROM obat WHERE id = " . $item[0]);
        $o_data = $res->fetch_assoc();
        
        $stmt_d = $conn->prepare("INSERT INTO detail_transaksi (transaksi_id, obat_id, jumlah, harga_satuan) VALUES (?, ?, ?, ?)");
        $stmt_d->bind_param("iiid", $t_id, $item[0], $item[1], $o_data['harga_jual']);
        $stmt_d->execute();
        
        $conn->query("UPDATE obat SET stok = stok - " . $item[1] . " WHERE id = " . $item[0]);
    }
}
echo count($transaksi_data) . " Transactions inserted.<br>";

echo "<h3>Dummy Data Generation Complete!</h3>";
echo "<a href='laporan.php' class='btn btn-primary' style='text-decoration:none; display:inline-block; padding:10px 20px; background:#4f46e5; color:white; border-radius:8px;'>Lihat Laporan Baru</a>";
?>


