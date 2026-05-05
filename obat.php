<?php
require_once 'config.php';
require_once 'encryption_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $kode = $_POST['kode'];
        $nama = $_POST['nama'];
        $kategori = $_POST['kategori'];
        $stok = $_POST['stok'];
        $satuan = $_POST['satuan'];
        $harga_beli_plain = $_POST['harga_beli'];
        $harga_jual = $_POST['harga_jual'];
        
        $stmt = $conn->prepare("INSERT INTO obat (kode, nama, kategori, stok, satuan, harga_beli, harga_jual) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisdd", $kode, $nama, $kategori, $stok, $satuan, $harga_beli_plain, $harga_jual);
        $stmt->execute();
        $message = "Obat berhasil ditambahkan.";
    }
    
    if (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $conn->query("DELETE FROM obat WHERE id = $id");
        $message = "Obat berhasil dihapus.";
    }
}

// Search
$search = $_GET['q'] ?? '';
$sql = "SELECT * FROM obat WHERE nama LIKE '%$search%' OR kode LIKE '%$search%'";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modul Obat - SI Apotek</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .modal-overlay {
            display:none; 
            position:fixed; 
            top:0; left:0; 
            width:100%; height:100%; 
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            align-items:center; justify-content:center; 
            z-index:1000;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'navbar.php'; ?>

            <div class="key-info-bar" style="background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 12px 20px; display: flex; align-items: center; gap: 12px; margin-bottom: 32px; box-shadow: var(--shadow-sm);">
                <div style="width: 32px; height: 32px; background: var(--primary-light); color: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4.1a1 1 0 0 0-1.4 0l-2.1 2.1a1 1 0 0 0 0 1.4Zm-5 7 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L14.1 12a1 1 0 0 0-1.4 0l-2.1 2.1a1 1 0 0 0 0 1.4Z"/><path d="M7 21a5 5 0 0 1-5-5c0-1.49.65-2.82 1.69-3.74L13 2.5s2.92 2.92 5 5l-9.74 9.74C7.34 18.28 6 19 4.5 19a1.5 1.5 0 0 0 0 3h15a1.5 1.5 0 0 0 0-3H11"/><path d="m7 17-3 3"/></svg>
                </div>
                <span style="font-weight: 600; color: var(--text-main);">Key Aktif: <strong id="active-key-val" style="color: var(--primary);"><?php echo $VIGENERE_KEY; ?></strong></span>
                <span style="margin-left: auto; color: var(--text-muted); font-size: 0.75rem; font-weight: 500;">Berdasarkan Identitas Server</span>
            </div>

            <?php if ($message): ?>
                <div style="padding: 16px; background: #dcfce7; border: 1px solid #86efac; border-radius: 12px; margin-bottom: 32px; color: #166534; display: flex; align-items: center; gap: 12px; font-weight: 600;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="card" style="margin-bottom: 32px;">
                <form method="GET" style="display: flex; gap: 12px;">
                    <div style="flex: 1; position: relative;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        <input type="text" name="q" placeholder="Cari berdasarkan nama atau kode obat..." value="<?php echo $search; ?>" style="padding-left: 48px; margin-top: 0;">
                    </div>
                    <button type="submit" class="btn btn-primary">Cari Obat</button>
                    <a href="obat.php" class="btn btn-outline">Reset</a>
                </form>
            </div>

            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
                    <h3 style="font-size: 1.25rem; color: var(--text-main);">Daftar Inventaris Obat</h3>
                    <button onclick="document.getElementById('modal-add').style.display='flex'" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        Tambah Obat Baru
                    </button>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Obat</th>
                                <th>Kategori</th>
                                <th>Stok</th>
                                <th>Harga Beli</th>
                                <th>Harga Jual</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--primary);"><?php echo $row['kode']; ?></td>
                                <td style="font-weight: 600; color: var(--text-main);"><?php echo $row['nama']; ?></td>
                                <td><span style="padding: 4px 10px; background: var(--primary-light); color: var(--primary); border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;"><?php echo $row['kategori'] ?: 'Umum'; ?></span></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <strong style="font-size: 1.1rem;"><?php echo number_format($row['stok']); ?></strong> 
                                        <span style="color: var(--text-muted); font-size: 0.8rem; font-weight: 500;"><?php echo $row['satuan']; ?></span>
                                    </div>
                                </td>
                                <td style="font-weight: 500; color: var(--text-muted);">Rp <?php echo number_format($row['harga_beli'], 0, ',', '.'); ?></td>
                                <td style="font-weight: 700; color: var(--success);">Rp <?php echo number_format($row['harga_jual'], 0, ',', '.'); ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus obat ini?')">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-outline" style="padding: 8px; color: var(--danger); border-color: rgba(239, 68, 68, 0.2);">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($result->num_rows == 0): ?>
                                <tr><td colspan="7" style="text-align: center; padding: 60px; color: var(--text-muted);">Data obat tidak ditemukan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add -->
    <div id="modal-add" class="modal-overlay">
        <div class="card" style="width: 100%; max-width: 600px; padding: 40px; border-radius: 24px; box-shadow: var(--shadow-lg);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
                <h3 style="font-size: 1.5rem; color: var(--text-main);">Tambah Obat Baru</h3>
                <button onclick="document.getElementById('modal-add').style.display='none'" style="background: none; border: none; cursor: pointer; color: var(--text-muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                    <div class="form-group">
                        <label>Kode Obat</label>
                        <input type="text" name="kode" required placeholder="Contoh: OBT-001">
                    </div>
                    <div class="form-group">
                        <label>Nama Obat</label>
                        <input type="text" name="nama" required placeholder="Nama lengkap obat">
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <input type="text" name="kategori" placeholder="Contoh: Tablet, Cair">
                    </div>
                    <div class="form-group">
                        <label>Satuan</label>
                        <input type="text" name="satuan" placeholder="Contoh: Pcs, Box, Strip">
                    </div>
                    <div class="form-group">
                        <label>Stok Awal</label>
                        <input type="number" name="stok" value="0">
                    </div>
                    <div class="form-group">
                        <label>Harga Jual (Rp)</label>
                        <input type="number" name="harga_jual" required placeholder="Harga ke konsumen">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Harga Beli (Rp)</label>
                    <input type="number" name="harga_beli" required placeholder="Harga dari supplier">
                </div>

                <div style="margin-top: 40px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="document.getElementById('modal-add').style.display='none'" class="btn btn-outline" style="padding: 12px 24px;">Batal</button>
                    <button type="submit" name="add" class="btn btn-primary" style="padding: 12px 32px;">Simpan Obat</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
