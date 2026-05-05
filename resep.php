<?php
require_once 'config.php';
require_once 'encryption_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $no_resep = $_POST['no_resep'];
        $nama_pasien = $_POST['nama_pasien'];
        $nik_plain = $_POST['nik'];
        $nama_dokter = $_POST['nama_dokter'];
        $isi_resep_plain = $_POST['isi_resep'];
        $alergi_plain = $_POST['catatan_alergi'] ?: '-';
        $tanggal = $_POST['tanggal'];
        
        $nik_enc = vigenere_encrypt($nik_plain, $VIGENERE_KEY);
        $isi_resep_enc = vigenere_encrypt($isi_resep_plain, $VIGENERE_KEY);
        $alergi_enc = vigenere_encrypt($alergi_plain, $VIGENERE_KEY);
        
        $stmt = $conn->prepare("INSERT INTO resep (no_resep, nama_pasien, nik_enc, nama_dokter, isi_resep_enc, catatan_alergi_enc, tanggal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $no_resep, $nama_pasien, $nik_enc, $nama_dokter, $isi_resep_enc, $alergi_enc, $tanggal);
        
        if ($stmt->execute()) {
            $message = "Resep berhasil ditambahkan dan data pasien diamankan.";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
    
    if (isset($_POST['update_status'])) {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $conn->query("UPDATE resep SET status = '$status' WHERE id = $id");
        $message = "Status resep diperbarui menjadi $status.";
    }

    if (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $conn->query("DELETE FROM resep WHERE id = $id");
        $message = "Resep berhasil dihapus.";
    }
}

// Search & Filter
$search = $_GET['search'] ?? '';
$where = $search ? "WHERE nama_pasien LIKE '%$search%' OR no_resep LIKE '%$search%'" : "";
$result = $conn->query("SELECT * FROM resep $where ORDER BY tanggal DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Resep - SI Apotek</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .modal-overlay {
            display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px);
            align-items:center; justify-content:center; z-index:1000;
        }
        .status-badge.menunggu { background: #fee2e2; color: #991b1b; }
        .status-badge.verifikasi { background: #e0e7ff; color: #3730a3; }
        .status-badge.diambil { background: #dcfce7; color: #166534; }
        
        @media print {
            .no-print { display: none !important; }
            .print-label { display: block !important; border: 2px solid #000; padding: 20px; width: 300px; margin: 0 auto; }
            body { background: white !important; }
        }
        .print-label { display: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="no-print">
            <?php include 'sidebar.php'; ?>
        </div>
        
        <div class="main-content">
            <div class="no-print">
                <?php include 'navbar.php'; ?>

                <div class="card" style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; padding: 20px 32px;">
                    <div>
                        <h2 style="font-size: 1.5rem; color: var(--text-main);">Data Resep Digital</h2>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">Kelola riwayat resep, alergi, dan verifikasi pasien.</p>
                    </div>
                    <button onclick="document.getElementById('modal-add').style.display='flex'" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        Input Resep Baru
                    </button>
                </div>

                <?php if ($message): ?>
                    <div style="padding: 16px; background: #dcfce7; border: 1px solid #86efac; border-radius: 12px; margin-bottom: 32px; color: #166534; display: flex; align-items: center; gap: 12px; font-weight: 600;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <form method="GET" style="margin-bottom: 24px; display: flex; gap: 12px;">
                        <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Cari Nama Pasien atau No. Resep..." style="max-width: 400px;">
                        <button type="submit" class="btn btn-outline">Cari</button>
                    </form>

                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>No. Resep</th>
                                    <th>Pasien (NIK)</th>
                                    <th>Isi Resep & Alergi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 700; color: var(--primary);"><?php echo $row['no_resep']; ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('d M Y', strtotime($row['tanggal'])); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;"><?php echo $row['nama_pasien']; ?></div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span class="encrypted" style="font-size: 0.7rem;" data-hidden="true" data-encrypted="<?php echo $row['nik_enc']; ?>"><?php echo $row['nik_enc']; ?></span>
                                            <button class="btn btn-outline" style="padding: 2px 6px; font-size: 0.6rem;" onclick="toggleDecryption(this, '<?php echo vigenere_decrypt($row['nik_enc'], $VIGENERE_KEY); ?>')">Lihat</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="margin-bottom: 8px;">
                                            <label style="font-size: 0.7rem; text-transform: uppercase;">Isi Resep:</label>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span class="encrypted" style="font-size: 0.75rem; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" data-hidden="true" data-encrypted="<?php echo $row['isi_resep_enc']; ?>"><?php echo $row['isi_resep_enc']; ?></span>
                                                <button class="btn btn-outline" style="padding: 2px 6px; font-size: 0.6rem;" onclick="toggleDecryption(this, '<?php echo vigenere_decrypt($row['isi_resep_enc'], $VIGENERE_KEY); ?>')">Buka</button>
                                            </div>
                                        </div>
                                        <div>
                                            <label style="font-size: 0.7rem; text-transform: uppercase; color: var(--danger);">Catatan Alergi:</label>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span class="encrypted" style="font-size: 0.75rem; color: var(--danger);" data-hidden="true" data-encrypted="<?php echo $row['catatan_alergi_enc']; ?>"><?php echo $row['catatan_alergi_enc']; ?></span>
                                                <button class="btn btn-outline" style="padding: 2px 6px; font-size: 0.6rem;" onclick="toggleDecryption(this, '<?php echo vigenere_decrypt($row['catatan_alergi_enc'], $VIGENERE_KEY); ?>')">Cek</button>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($row['status']); ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" style="padding: 4px 8px; font-size: 0.75rem; width: auto; margin: 0;">
                                                    <option value="" disabled selected>Update</option>
                                                    <option value="Menunggu">Menunggu</option>
                                                    <option value="Diverifikasi">Verifikasi</option>
                                                    <option value="Diambil">Diambil</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                            <button class="btn btn-outline" style="padding: 8px;" onclick="printLabel('<?php echo $row['no_resep']; ?>', '<?php echo $row['nama_pasien']; ?>', '<?php echo vigenere_decrypt($row['isi_resep_enc'], $VIGENERE_KEY); ?>')">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/><polyline points="6 9 6 2 18 2 18 9"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Print Label Container -->
            <div id="print-area" class="print-label">
                <div style="text-align: center; border-bottom: 2px solid #000; margin-bottom: 10px; padding-bottom: 10px;">
                    <h2 style="margin: 0;">SI APOTEK MODERN</h2>
                    <p style="margin: 5px 0; font-size: 0.8rem;">Label Etiket Obat Digital</p>
                </div>
                <div style="margin-bottom: 10px;">
                    <strong>No. Resep:</strong> <span id="label-no"></span><br>
                    <strong>Pasien:</strong> <span id="label-nama"></span><br>
                    <strong>Tanggal:</strong> <?php echo date('d/m/Y'); ?>
                </div>
                <div style="border: 1px dashed #000; padding: 10px; background: #f9f9f9;">
                    <strong>ATURAN PAKAI:</strong><br>
                    <p id="label-isi" style="font-size: 1.1rem; font-weight: 700; margin: 10px 0;"></p>
                </div>
                <p style="font-size: 0.7rem; text-align: center; margin-top: 15px;">Simpan di tempat sejuk dan kering.<br>Jauhkan dari jangkauan anak-anak.</p>
            </div>
        </div>
    </div>

    <!-- Modal Add -->
    <div id="modal-add" class="modal-overlay no-print">
        <div class="card" style="width: 100%; max-width: 600px; padding: 40px; border-radius: 24px; box-shadow: var(--shadow-lg);">
            <h3 style="font-size: 1.5rem; color: var(--text-main); margin-bottom: 32px;">Input Data Resep Dokter</h3>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>No. Resep</label>
                        <input type="text" name="no_resep" value="RSP-<?php echo strtoupper(substr(uniqid(), -6)); ?>" readonly style="background: #f1f5f9;">
                    </div>
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Nama Pasien</label>
                        <input type="text" name="nama_pasien" required placeholder="Nama lengkap">
                    </div>
                    <div class="form-group">
                        <label>NIK Pasien (Enkripsi)</label>
                        <input type="text" name="nik" required placeholder="16 digit NIK">
                    </div>
                </div>
                <div class="form-group">
                    <label>Nama Dokter</label>
                    <input type="text" name="nama_dokter" required placeholder="dr. ...">
                </div>
                <div class="form-group">
                    <label>Isi Resep (Nama Obat, Dosis, Aturan)</label>
                    <textarea name="isi_resep" rows="3" required placeholder="Contoh: Paracetamol 500mg, 3x1 hari setelah makan"></textarea>
                </div>
                <div class="form-group">
                    <label>Catatan Alergi (Opsional - Enkripsi)</label>
                    <input type="text" name="catatan_alergi" placeholder="Contoh: Alergi Penisilin">
                </div>
                
                <div style="margin-top: 40px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="document.getElementById('modal-add').style.display='none'" class="btn btn-outline">Batal</button>
                    <button type="submit" name="add" class="btn btn-primary">Simpan Resep</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        function printLabel(no, nama, isi) {
            document.getElementById('label-no').innerText = no;
            document.getElementById('label-nama').innerText = nama;
            document.getElementById('label-isi').innerText = isi;
            window.print();
        }
    </script>
</body>
</html>
