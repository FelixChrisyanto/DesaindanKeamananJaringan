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
        $kode = $_POST['kode'];
        $nama = $_POST['nama'];
        $alamat_plain = $_POST['alamat'];
        $telepon_plain = $_POST['telepon'];
        $email_plain = $_POST['email'];
        
        $email_enc = vigenere_encrypt($email_plain, $VIGENERE_KEY);
        
        $stmt = $conn->prepare("INSERT INTO supplier (kode, nama, alamat, telepon, email_enc) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $kode, $nama, $alamat_plain, $telepon_plain, $email_enc);
        $stmt->execute();
        $message = "Supplier berhasil ditambahkan.";
    }
    
    if (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $conn->query("DELETE FROM supplier WHERE id = $id");
        $message = "Supplier berhasil dihapus.";
    }
}

$result = $conn->query("SELECT * FROM supplier");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modul Supplier - SI Apotek</title>
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
            </div>

            <?php if ($message): ?>
                <div style="padding: 16px; background: #dcfce7; border: 1px solid #86efac; border-radius: 12px; margin-bottom: 32px; color: #166534; display: flex; align-items: center; gap: 12px; font-weight: 600;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
                    <h3 style="font-size: 1.25rem; color: var(--text-main);">Manajemen Supplier</h3>
                    <button onclick="document.getElementById('modal-add').style.display='flex'" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        Tambah Supplier
                    </button>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Supplier</th>
                                <th>Alamat</th>
                                <th>Telepon</th>
                                <th>Email</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--primary);"><?php echo $row['kode']; ?></td>
                                <td style="font-weight: 600; color: var(--text-main);"><?php echo $row['nama']; ?></td>
                                <td style="font-size: 0.85rem; color: var(--text-muted);"><?php echo $row['alamat']; ?></td>
                                <td style="font-weight: 500;"><?php echo $row['telepon']; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <span class="encrypted" data-hidden="true" data-encrypted="<?php echo $row['email_enc']; ?>"><?php echo $row['email_enc']; ?></span>
                                        <button class="btn btn-outline" style="padding: 6px 12px; font-size: 0.75rem; font-weight: 700;" onclick="toggleDecryption(this, '<?php echo vigenere_decrypt($row['email_enc'], $VIGENERE_KEY); ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            Buka
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus supplier ini?')">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-outline" style="padding: 8px; color: var(--danger); border-color: rgba(239, 68, 68, 0.2);">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($result->num_rows == 0): ?>
                                <tr><td colspan="6" style="text-align: center; padding: 60px; color: var(--text-muted);">Data supplier tidak ditemukan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add -->
    <div id="modal-add" class="modal-overlay">
        <div class="card" style="width: 100%; max-width: 500px; padding: 40px; border-radius: 24px; box-shadow: var(--shadow-lg);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
                <h3 style="font-size: 1.5rem; color: var(--text-main);">Tambah Supplier</h3>
                <button onclick="document.getElementById('modal-add').style.display='none'" style="background: none; border: none; cursor: pointer; color: var(--text-muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <form method="POST">
                <div style="display: grid; gap: 20px;">
                    <div class="form-group">
                        <label>Kode Supplier</label>
                        <input type="text" name="kode" required placeholder="Contoh: SUP-001">
                    </div>
                    <div class="form-group">
                        <label>Nama Supplier</label>
                        <input type="text" name="nama" required placeholder="Nama lengkap perusahaan">
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" rows="3" placeholder="Alamat lengkap supplier" style="width: 100%; padding: 12px 16px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); margin-top: 8px; outline: none; font-size: 0.95rem;"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Telepon</label>
                        <input type="text" name="telepon" required placeholder="Contoh: 0812-xxxx-xxxx">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="Contoh: supplier@mail.com">
                    </div>
                </div>
                <div style="margin-top: 40px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="document.getElementById('modal-add').style.display='none'" class="btn btn-outline" style="padding: 12px 24px;">Batal</button>
                    <button type="submit" name="add" class="btn btn-primary" style="padding: 12px 32px;">Simpan Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
