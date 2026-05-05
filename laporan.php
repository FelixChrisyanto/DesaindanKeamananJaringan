<?php
require_once 'config.php';
require_once 'encryption_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Report Stats
$sql_stats = "SELECT 
                COUNT(DISTINCT t.id) as total_transaksi,
                SUM(dt.jumlah) as total_item
              FROM transaksi t
              LEFT JOIN detail_transaksi dt ON t.id = dt.transaksi_id
              WHERE t.tanggal BETWEEN '$start_date' AND '$end_date'";
$stats_res = $conn->query($sql_stats);
$stats = $stats_res ? $stats_res->fetch_assoc() : ['total_transaksi' => 0, 'total_item' => 0];

// Detailed List
$sql_list = "SELECT t.*, GROUP_CONCAT(CONCAT(o.nama, ' (', dt.jumlah, ')') SEPARATOR ', ') as items_list
             FROM transaksi t
             LEFT JOIN detail_transaksi dt ON t.id = dt.transaksi_id
             LEFT JOIN obat o ON dt.obat_id = o.id
             WHERE t.tanggal BETWEEN '$start_date' AND '$end_date'
             GROUP BY t.id
             ORDER BY t.tanggal ASC";
$list = $conn->query($sql_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan Harian - SI Apotek</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        @media print {
            .sidebar, .navbar, .btn, .filter-box, .key-info-bar, .sidebar-footer { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; background: white !important; }
            .card { border: none !important; box-shadow: none !important; padding: 0 !important; }
            .badge-encrypted { display: none !important; }
            .encrypted { border: none !important; background: none !important; padding: 0 !important; }
            body { background: white !important; }
            .table-wrapper { border: 1px solid #eee !important; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'navbar.php'; ?>

            <div class="card filter-box" style="margin-bottom: 32px; background: white; border-radius: 16px;">
                <h3 style="margin-bottom: 24px; font-size: 1.1rem; color: var(--text-main);">Laporan Penjualan Harian</h3>
                <form method="GET" style="display: flex; gap: 20px; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label>Dari Tanggal</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div style="flex: 1;">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Filter</button>
                        <button type="button" onclick="window.print()" class="btn btn-outline" style="padding: 12px 24px;">Cetak</button>
                    </div>
                </form>
            </div>

            <div class="stats-grid" style="margin-bottom: 32px;">
                <div class="stat-card blue">
                    <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                    <div class="stat-info">
                        <div class="stat-label">Volume Penjualan</div>
                        <div class="stat-value"><?php echo number_format($stats['total_transaksi'] ?: 0); ?> <small>Trx</small></div>
                    </div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg></div>
                    <div class="stat-info">
                        <div class="stat-label">Item Terjual</div>
                        <div class="stat-value"><?php echo number_format($stats['total_item'] ?: 0); ?> <small>Unit</small></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 style="font-size: 1.25rem; color: var(--text-main); margin-bottom: 32px;">Rincian Transaksi Harian</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>No. POS</th>
                                <th>Pasien</th>
                                <th>Item</th>
                                <th>Metode</th>
                                <th>BPJS</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($list && $list->num_rows > 0): ?>
                                <?php while ($row = $list->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight: 700; color: var(--primary);"><?php echo $row['no_transaksi']; ?></td>
                                    <td style="font-weight: 600;"><?php echo $row['nama_pasien'] ?: '-'; ?></td>
                                    <td style="font-size: 0.9rem; max-width: 300px; line-height: 1.4;"><?php echo $row['items_list']; ?></td>
                                    <td style="font-size: 0.85rem;"><?php echo $row['metode_bayar']; ?></td>
                                    <td><span class="status-badge" style="background: <?php echo $row['is_bpjs'] ? '#dcfce7' : '#f1f5f9'; ?>"><?php echo $row['is_bpjs'] ? 'YA' : 'TIDAK'; ?></span></td>
                                    <td><span style="font-weight: 700; color: var(--success);">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">Tidak ada transaksi pada periode ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
