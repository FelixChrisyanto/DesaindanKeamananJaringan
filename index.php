<?php
require_once 'config.php';
require_once 'encryption_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Stats
$total_obat = $conn->query("SELECT COUNT(*) as count FROM obat")->fetch_assoc()['count'];
$total_supplier = $conn->query("SELECT COUNT(*) as count FROM supplier")->fetch_assoc()['count'];
$current_month = date('Y-m');
$total_transaksi = $conn->query("SELECT COUNT(*) as count FROM transaksi WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$current_month'")->fetch_assoc()['count'];

// Recent Transactions
$recent_sql = "SELECT t.*, s.nama as supplier_nama 
               FROM transaksi t 
               LEFT JOIN supplier s ON t.supplier_id = s.id 
               ORDER BY t.tanggal DESC LIMIT 5";
$recent_result = $conn->query($recent_sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SI Apotek</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'navbar.php'; ?>

            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"/><path d="M8 10h.01"/><path d="M16 10h.01"/><path d="M12 16h.01"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Obat</div>
                        <div class="stat-value"><?php echo number_format($total_obat); ?></div>
                    </div>
                </div>
                <div class="stat-card purple">
                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Total Supplier</div>
                        <div class="stat-value"><?php echo number_format($total_supplier); ?></div>
                    </div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Transaksi Bulan Ini</div>
                        <div class="stat-value"><?php echo number_format($total_transaksi); ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h3 style="font-size: 1.25rem; color: var(--text-main);">Transaksi Terbaru</h3>
                    <a href="transaksi.php" class="btn btn-outline" style="font-size: 0.8rem; padding: 8px 16px;">
                        <span>Lihat Semua</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                </div>
                
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>No. Transaksi</th>
                                <th>Tanggal</th>
                                <th>Supplier</th>
                                <th>Total Harga</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $recent_result->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight: 700; color: var(--primary);"><?php echo $row['no_transaksi']; ?></td>
                                <td><?php echo date('d M Y', strtotime($row['tanggal'])); ?></td>
                                <td>
                                    <?php echo $row['supplier_nama'] ?: '-'; ?>
                                </td>
                                <td>
                                    <span class="encrypted"><?php echo $row['total_enc']; ?></span>
                                    <span class="badge-encrypted">terenkripsi</span>
                                </td>
                                <td>
                                    <span class="status-badge success">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($recent_result->num_rows == 0): ?>
                                <tr><td colspan="5" style="text-align: center; padding: 60px; color: var(--text-muted);">Belum ada data transaksi terbaru.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
