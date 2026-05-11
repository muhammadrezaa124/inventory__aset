<?php
require_once 'config.php';
$report = $_GET['report'] ?? 'stock';

$stock = $pdo->query("
    SELECT i.*, m.name as item_name, t.name as type_name 
    FROM t_inventory i 
    JOIN m_items m ON i.item_id = m.id 
    JOIN m_item_types t ON m.item_type_id = t.id 
    ORDER BY i.total_quantity DESC
")->fetchAll();

$transactions = $pdo->query("
    SELECT t.*, tt.name as type_name 
    FROM t_inventory_transactions t 
    JOIN m_transaction_type tt ON t.transaction_type_id = tt.id 
    ORDER BY t.transaction_date DESC
")->fetchAll();

// Perbaikan: kolom di tabel asli adalah 'budget' dan 'realization', bukan 'total_budget' dan 'budget_realization'
$budgetData = $pdo->query("
    SELECT tt.name as type_name, 
           SUM(t.budget) as total_budget, 
           SUM(t.realization) as total_realization 
    FROM t_inventory_transactions t 
    JOIN m_transaction_type tt ON t.transaction_type_id = tt.id 
    GROUP BY tt.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; }
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100%; width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
        }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header i { font-size: 2.5rem; color: #00b4d8; }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8); padding: 12px 25px;
            display: flex; align-items: center; gap: 12px; text-decoration: none;
        }
        .sidebar .nav-link:hover { background: rgba(0,180,216,0.2); }
        .sidebar .nav-link.active { background: #00b4d8; }
        .main-content { margin-left: 280px; padding: 20px; }
        .table-container {
            background: white; border-radius: 15px; padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-top: 20px;
        }
        .nav-tabs .nav-link { color: #333; }
        .nav-tabs .nav-link.active { background: #00b4d8; color: white; border: none; }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .nav-link span { display: none; }
            .sidebar-header h4 { display: none; }
            .main-content { margin-left: 70px; }
        }
        @media print {
            .sidebar, .nav-tabs, button { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header"><i class="fas fa-boxes"></i><h4>Inventory Aset</h4></div>
    <nav>
        <a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a class="nav-link" href="master.php"><i class="fas fa-database"></i> <span>Master Data</span></a>
        <a class="nav-link" href="inventory.php"><i class="fas fa-boxes"></i> <span>Kelola Inventory</span></a>
        <a class="nav-link" href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a>
        <a class="nav-link" href="distribusi.php"><i class="fas fa-location-dot"></i> <span>Distribusi Barang</span></a>
        <a class="nav-link" href="monitoring.php"><i class="fas fa-map-marker-alt"></i> <span>Monitoring Lokasi</span></a>
        <a class="nav-link active" href="laporan.php"><i class="fas fa-chart-line"></i> <span>Laporan</span></a>
        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </nav>
</div>

<div class="main-content">
    <h2 class="mb-4"><i class="fas fa-chart-line me-2 text-primary"></i> Laporan</h2>
    
    <ul class="nav nav-tabs">
        <li class="nav-item"><a class="nav-link <?= $report == 'stock' ? 'active' : '' ?>" href="?report=stock">Laporan Stok</a></li>
        <li class="nav-item"><a class="nav-link <?= $report == 'transaction' ? 'active' : '' ?>" href="?report=transaction">Laporan Transaksi</a></li>
        <li class="nav-item"><a class="nav-link <?= $report == 'budget' ? 'active' : '' ?>" href="?report=budget">Anggaran vs Realisasi</a></li>
    </ul>
    
    <div class="table-container">
        <?php if($report == 'stock'): ?>
            <button class="btn btn-success mb-3" onclick="window.print()"><i class="fas fa-print"></i> Cetak</button>
            <table class="table table-bordered">
                <thead class="table-light"><tr><th>Barcode</th><th>Nama Barang</th><th>Tipe</th><th>Stok</th><th>Harga</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach($stock as $row): ?>
                    <tr>
                        <td><?= $row['barcode'] ?></td>
                        <td><?= $row['item_name'] ?></td>
                        <td><?= $row['type_name'] ?></td>
                        <td><?= number_format($row['total_quantity']) ?></td>
                        <td><?= rupiah($row['price']) ?></td>
                        <td><?= ucfirst($row['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        
        <?php elseif($report == 'transaction'): ?>
            <table class="table table-bordered">
                <thead class="table-light"><tr><th>No. Transaksi</th><th>Tanggal</th><th>Jenis</th><th>Anggaran</th><th>Realisasi</th><th>Selisih</th></tr></thead>
                <tbody>
                    <?php foreach($transactions as $row): 
                        $selisih = $row['budget'] - $row['realization'];
                    ?>
                    <tr>
                        <td><?= $row['transaction_number'] ?></td>
                        <td><?= date('d/m/Y', strtotime($row['transaction_date'])) ?></td>
                        <td><?= $row['type_name'] ?></td>
                        <td><?= rupiah($row['budget']) ?></td>
                        <td><?= rupiah($row['realization']) ?></td>
                        <td class="<?= $selisih < 0 ? 'text-danger' : 'text-success' ?>"><?= rupiah(abs($selisih)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        
        <?php else: ?>
            <table class="table table-bordered">
                <thead class="table-light"><tr><th>Jenis Transaksi</th><th>Total Anggaran</th><th>Total Realisasi</th><th>Variance</th></tr></thead>
                <tbody>
                    <?php foreach($budgetData as $row): 
                        $variance = $row['total_budget'] - $row['total_realization'];
                    ?>
                    <tr>
                        <td><?= $row['type_name'] ?></td>
                        <td><?= rupiah($row['total_budget']) ?></td>
                        <td><?= rupiah($row['total_realization']) ?></td>
                        <td class="<?= $variance < 0 ? 'text-danger' : 'text-success' ?>"><?= rupiah(abs($variance)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>