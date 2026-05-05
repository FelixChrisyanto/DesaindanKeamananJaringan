<?php
require_once 'config.php';
require_once 'encryption_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_transaksi'])) {
    $no_transaksi = $_POST['no_transaksi'];
    $tanggal = $_POST['tanggal'];
    $nama_pasien = $_POST['nama_pasien'];
    $nik_plain = $_POST['nik_pasien'];
    $metode_bayar = $_POST['metode_bayar'];
    $nomor_pembayaran_plain = $_POST['nomor_pembayaran'] ?: '-';
    $is_bpjs = isset($_POST['is_bpjs']) ? 1 : 0;
    $nomor_asuransi_plain = $_POST['nomor_asuransi'] ?: '-';
    $cart_json = $_POST['cart_data'];
    $cart_items = json_decode($cart_json, true);
    
    if (empty($cart_items)) {
        $message = "Error: Keranjang belanja kosong!";
    } else {
        $nik_pasien_enc = vigenere_encrypt($nik_plain, $VIGENERE_KEY);
        $nomor_pembayaran_enc = vigenere_encrypt($nomor_pembayaran_plain, $VIGENERE_KEY);
        $nomor_asuransi_enc = vigenere_encrypt($nomor_asuransi_plain, $VIGENERE_KEY);
        
        // Calculate total from cart
        $total_transaksi = 0;
        foreach ($cart_items as $item) {
            $total_transaksi += $item['harga'] * $item['qty'];
        }

        $stmt = $conn->prepare("INSERT INTO transaksi (no_transaksi, tanggal, nama_pasien, nik_pasien_enc, metode_bayar, nomor_pembayaran_enc, is_bpjs, nomor_asuransi_enc, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssisd", $no_transaksi, $tanggal, $nama_pasien, $nik_pasien_enc, $metode_bayar, $nomor_pembayaran_enc, $is_bpjs, $nomor_asuransi_enc, $total_transaksi);
        
        if ($stmt->execute()) {
            $transaksi_id = $conn->insert_id;
            $stmt_detail = $conn->prepare("INSERT INTO detail_transaksi (transaksi_id, obat_id, jumlah, harga_satuan) VALUES (?, ?, ?, ?)");
            
            foreach ($cart_items as $item) {
                $o_id = $item['id'];
                $qty = $item['qty'];
                $price = $item['harga'];
                $stmt_detail->bind_param("iiid", $transaksi_id, $o_id, $qty, $price);
                $stmt_detail->execute();
                
                // Update stock
                $conn->query("UPDATE obat SET stok = stok - $qty WHERE id = $o_id");
            }
            
            $message = "Transaksi Kasir (POS) berhasil disimpan.";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}

// Fetch Data for Form
$obats = $conn->query("SELECT id, nama, harga_jual, stok FROM obat WHERE stok > 0");

// Search & Filter
$search = $_GET['search'] ?? '';
$where = $search ? "WHERE t.nama_pasien LIKE '%$search%' OR t.no_transaksi LIKE '%$search%'" : "";
$transaksi_sql = "SELECT t.*, GROUP_CONCAT(o.nama SEPARATOR ', ') as obat_nama 
                 FROM transaksi t 
                 LEFT JOIN detail_transaksi dt ON t.id = dt.transaksi_id 
                 LEFT JOIN obat o ON dt.obat_id = o.id 
                 $where
                 GROUP BY t.id
                 ORDER BY t.tanggal DESC LIMIT 10";
$transactions = $conn->query($transaksi_sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir (POS) - SI Apotek</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .pos-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
        @media print {
            .no-print { display: none !important; }
            .print-invoice { display: block !important; width: 300px; margin: 0 auto; font-family: 'Courier New', Courier, monospace; font-size: 10pt; }
            body { background: white !important; }
        }
        .print-invoice { display: none; }
        .cart-item-row:hover { background: #f8fafc; }
        .btn-remove { color: #ef4444; cursor: pointer; padding: 4px; border-radius: 4px; }
        .btn-remove:hover { background: #fef2f2; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="no-print"><?php include 'sidebar.php'; ?></div>
        
        <div class="main-content">
            <div class="no-print">
                <?php include 'navbar.php'; ?>
                
                <div class="card" style="margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; padding: 20px 32px;">
                    <div>
                        <h2 style="font-size: 1.5rem; color: var(--text-main);">Point of Sale (POS) Kasir</h2>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">Proses pembayaran multi-item, klaim BPJS, dan cetak struk.</p>
                    </div>
                    <div style="font-weight: 700; color: var(--primary);">Key Aktif: <?php echo $VIGENERE_KEY; ?></div>
                </div>

                <?php if ($message): ?>
                    <div style="padding: 16px; background: #dcfce7; border: 1px solid #86efac; border-radius: 12px; margin-bottom: 32px; color: #166534; display: flex; align-items: center; gap: 12px; font-weight: 600;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="pos-grid">
                    <!-- Form Input & Keranjang -->
                    <div style="display: flex; flex-direction: column; gap: 24px;">
                        <!-- Pemilihan Obat -->
                        <div class="card">
                            <h3 style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 20px;">Tambah Item</h3>
                            <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 12px; align-items: flex-end;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label>Pilih Obat</label>
                                    <select id="select-obat">
                                        <option value="">-- Pilih Obat --</option>
                                        <?php while ($o = $obats->fetch_assoc()): ?>
                                            <option value="<?php echo $o['id']; ?>" data-nama="<?php echo $o['nama']; ?>" data-harga="<?php echo $o['harga_jual']; ?>" data-stok="<?php echo $o['stok']; ?>">
                                                <?php echo $o['nama']; ?> (Stok: <?php echo $o['stok']; ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label>Qty</label>
                                    <input type="number" id="input-jumlah" min="1" value="1">
                                </div>
                                <button type="button" onclick="addToCart()" class="btn btn-primary" style="padding: 12px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Daftar Keranjang -->
                        <div class="card" style="flex: 1;">
                            <h3 style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 20px;">Keranjang Belanja</h3>
                            <div class="table-wrapper" style="max-height: 300px; overflow-y: auto;">
                                <table id="cart-table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Harga</th>
                                            <th>Qty</th>
                                            <th>Subtotal</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="cart-body">
                                        <!-- Items will be added here via JS -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="empty-cart-msg" style="text-align: center; padding: 40px 0; color: var(--text-muted);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.3; margin-bottom: 12px;"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                                <p>Keranjang kosong</p>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Transaksi & Pembayaran -->
                    <div class="card" style="height: fit-content;">
                        <h3 style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 24px;">Penyelesaian Transaksi</h3>
                        <form method="POST" onsubmit="return validateForm()">
                            <input type="hidden" name="cart_data" id="cart-data-input">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group">
                                    <label>No. Transaksi</label>
                                    <input type="text" name="no_transaksi" value="POS-<?php echo strtoupper(substr(uniqid(), -6)); ?>" readonly style="background: #f1f5f9;">
                                </div>
                                <div class="form-group">
                                    <label>Tanggal</label>
                                    <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group">
                                    <label>Nama Pasien</label>
                                    <input type="text" name="nama_pasien" required placeholder="Nama lengkap">
                                </div>
                                <div class="form-group">
                                    <label>NIK Pasien (Enkripsi)</label>
                                    <input type="text" name="nik_pasien" required placeholder="16 digit NIK">
                                </div>
                            </div>

                            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; margin-top: 10px;">
                                <div class="form-group">
                                    <label>Metode Pembayaran</label>
                                    <select name="metode_bayar" id="select-metode" required onchange="togglePaymentField()">
                                        <option value="Tunai">Tunai</option>
                                        <option value="Kartu">Kartu (Debit/Kredit)</option>
                                        <option value="E-Wallet">E-Wallet (QRIS/Dana/OVO)</option>
                                    </select>
                                </div>
                                <div id="digital-field" class="form-group" style="display: none;">
                                    <label id="digital-label">Nomor Kartu/ID (Enkripsi)</label>
                                    <input type="text" name="nomor_pembayaran" placeholder="xxxx-xxxx-xxxx">
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px; margin-top: 16px;">
                                    <input type="checkbox" name="is_bpjs" id="check-bpjs" style="width: auto; margin: 0;" onchange="toggleBPJS()">
                                    <label for="check-bpjs" style="margin: 0;">Klaim BPJS / Asuransi</label>
                                </div>
                                <div id="bpjs-field" class="form-group" style="display: none; margin-top: 12px;">
                                    <label>Nomor BPJS/Asuransi (Enkripsi)</label>
                                    <input type="text" name="nomor_asuransi" placeholder="0001xxxxxxxx">
                                </div>
                            </div>

                            <div style="background: var(--primary); color: white; padding: 20px; border-radius: 16px; margin-top: 24px; text-align: center;">
                                <div style="font-size: 0.8rem; opacity: 0.8;">Total Akhir</div>
                                <div id="total-display" style="font-size: 1.75rem; font-weight: 800; margin: 8px 0;">Rp 0</div>
                            </div>

                            <button type="submit" name="save_transaksi" class="btn btn-primary" style="width: 100%; margin-top: 24px; padding: 16px; justify-content: center; font-size: 1.1rem;">Simpan & Cetak Struk</button>
                        </form>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="card" style="margin-top: 32px;">
                    <h3 style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 20px;">10 Transaksi Terakhir</h3>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>No. TRX</th>
                                    <th>Pasien</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($t = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight: 700; color: var(--primary); font-size: 0.75rem;"><?php echo $t['no_transaksi']; ?></td>
                                    <td>
                                        <div style="font-weight: 600;"><?php echo $t['nama_pasien']; ?></div>
                                        <div style="font-size: 0.7rem; color: var(--text-muted);"><?php echo $t['metode_bayar']; ?></div>
                                    </td>
                                    <td style="font-size: 0.8rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo $t['obat_nama']; ?>
                                    </td>
                                    <td style="font-weight: 700; color: var(--success);">Rp <?php echo number_format($t['total'], 0, ',', '.'); ?></td>
                                    <td>
                                        <button class="btn btn-outline" style="padding: 8px;" onclick="alert('Gunakan Cetak dari menu Laporan untuk rincian lengkap.')">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/><polyline points="6 9 6 2 18 2 18 9"/></svg>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cart = [];

        function addToCart() {
            const select = document.getElementById('select-obat');
            const qtyInput = document.getElementById('input-jumlah');
            
            const id = select.value;
            const qty = parseInt(qtyInput.value);
            
            if (!id || qty < 1) {
                alert('Pilih obat dan jumlah yang valid!');
                return;
            }

            const opt = select.options[select.selectedIndex];
            const nama = opt.dataset.nama;
            const harga = parseInt(opt.dataset.harga);
            const stok = parseInt(opt.dataset.stok);

            if (qty > stok) {
                alert('Stok tidak mencukupi!');
                return;
            }

            // Check if already in cart
            const existing = cart.find(item => item.id === id);
            if (existing) {
                if (existing.qty + qty > stok) {
                    alert('Total jumlah melebihi stok!');
                    return;
                }
                existing.qty += qty;
            } else {
                cart.push({ id, nama, harga, qty });
            }

            updateCartUI();
            qtyInput.value = 1;
            select.value = "";
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartUI();
        }

        function updateCartUI() {
            const body = document.getElementById('cart-body');
            const emptyMsg = document.getElementById('empty-cart-msg');
            const totalDisplay = document.getElementById('total-display');
            const cartDataInput = document.getElementById('cart-data-input');
            
            body.innerHTML = '';
            let total = 0;

            cart.forEach((item, index) => {
                const subtotal = item.harga * item.qty;
                total += subtotal;
                
                body.innerHTML += `
                    <tr class="cart-item-row">
                        <td style="font-weight: 600;">${item.nama}</td>
                        <td>Rp ${item.harga.toLocaleString('id-ID')}</td>
                        <td>${item.qty}</td>
                        <td style="font-weight: 700;">Rp ${subtotal.toLocaleString('id-ID')}</td>
                        <td>
                            <span class="btn-remove" onclick="removeFromCart(${index})">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                            </span>
                        </td>
                    </tr>
                `;
            });

            emptyMsg.style.display = cart.length ? 'none' : 'block';
            totalDisplay.innerText = 'Rp ' + total.toLocaleString('id-ID');
            cartDataInput.value = JSON.stringify(cart);
        }

        function togglePaymentField() {
            const metode = document.getElementById('select-metode').value;
            const field = document.getElementById('digital-field');
            field.style.display = (metode === 'Tunai') ? 'none' : 'block';
            document.getElementById('digital-label').innerText = (metode === 'Kartu') ? 'Nomor Kartu (Enkripsi)' : 'ID E-Wallet (Enkripsi)';
        }

        function toggleBPJS() {
            const check = document.getElementById('check-bpjs').checked;
            document.getElementById('bpjs-field').style.display = check ? 'block' : 'none';
        }

        function validateForm() {
            if (cart.length === 0) {
                alert('Keranjang belanja masih kosong!');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>

