<?php
require_once 'config.php';
$role = $_SESSION['role']; // ambil role dari session

$locations = $pdo->query("
    SELECT b.name as building, r.name as room, COALESCE(SUM(d.quantity),0) as total
    FROM m_buildings b
    LEFT JOIN m_rooms r ON b.id = r.building_id
    LEFT JOIN t_inventory_room d ON r.id = d.room_id
    GROUP BY b.id, r.id
    ORDER BY b.name, r.name
")->fetchAll();

$grouped = [];
foreach($locations as $loc) {
    $grouped[$loc['building']][] = $loc;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Lokasi</title>
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
            box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .nav-link span { display: none; }
            .sidebar-header h4 { display: none; }
            .main-content { margin-left: 70px; }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header"><i class="fas fa-boxes"></i><h4>Inventory Aset</h4></div>
    <nav>
        <a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <?php if($role == 'admin'): ?>
            <a class="nav-link" href="master.php"><i class="fas fa-database"></i> <span>Master Data</span></a>
            <a class="nav-link" href="inventory.php"><i class="fas fa-boxes"></i> <span>Kelola Inventory</span></a>
            <a class="nav-link" href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a>
        <?php endif; ?>
        <a class="nav-link" href="distribusi.php"><i class="fas fa-location-dot"></i> <span>Distribusi Barang</span></a>
        <a class="nav-link active" href="monitoring.php"><i class="fas fa-map-marker-alt"></i> <span>Monitoring Lokasi</span></a>
        <a class="nav-link" href="laporan.php"><i class="fas fa-chart-line"></i> <span>Laporan</span></a>
        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </nav>
</div>

<div class="main-content">
    <h2 class="mb-4"><i class="fas fa-map-marker-alt me-2 text-primary"></i> Monitoring Lokasi Barang</h2>
    <div class="table-container">
        <?php foreach($grouped as $building => $rooms): ?>
            <h4 class="mt-3"><i class="fas fa-building me-2"></i> <?= $building ?></h4>
            <table class="table table-bordered">
                <thead class="table-light"><tr><th>Ruangan</th><th>Total Barang</th></tr></thead>
                <tbody>
                    <?php foreach($rooms as $room): ?>
                    <tr>
                        <td><?= $room['room'] ?: '(Ruangan belum diberi nama)' ?></td>
                        <td><span class="badge bg-primary"><?= number_format($room['total']) ?> barang</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>