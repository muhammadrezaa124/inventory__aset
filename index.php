<?php
require_once 'config.php';
$role = $_SESSION['role'];

$totalBarang = $pdo->query("SELECT SUM(total_quantity) FROM t_inventory")->fetchColumn();
$totalItems = $pdo->query("SELECT COUNT(*) FROM m_items")->fetchColumn();
$totalLokasi = $pdo->query("SELECT COUNT(DISTINCT room_id) FROM t_inventory_room")->fetchColumn();
$rusak = $pdo->query("SELECT SUM(total_quantity) FROM t_inventory WHERE status = 'damaged'")->fetchColumn();

$topItems = $pdo->query("
    SELECT i.barcode, m.name as item_name, it.name as category, i.total_quantity, i.price 
    FROM t_inventory i 
    JOIN m_items m ON i.item_id = m.id 
    JOIN m_item_types it ON m.item_type_id = it.id 
    ORDER BY i.total_quantity DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Dashboard - Inventory Aset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100%; width: 280px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white; z-index: 1000; box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header i { font-size: 2.5rem; color: #00b4d8; }
        .sidebar-header h4 { margin-top: 10px; }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8); padding: 12px 25px;
            display: flex; align-items: center; gap: 12px; text-decoration: none;
            transition: 0.3s;
        }
        .sidebar .nav-link:hover { background: rgba(0,180,216,0.2); padding-left: 30px; }
        .sidebar .nav-link.active { background: #00b4d8; color: white; }
        .main-content { margin-left: 280px; padding: 20px; min-height: 100vh; }
        .stat-card {
            background: white; border-radius: 15px; padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08); border-left: 4px solid;
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card h3 { font-size: 1.8rem; font-weight: 700; margin: 10px 0 0; }
        .table-container {
            background: white; border-radius: 15px; padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-top: 20px;
        }
        .clock { width: 70px; height: 70px; background: white; border-radius: 50%; box-shadow: 0 0 10px rgba(0,0,0,0.2); margin-left: 15px; }
        .table-responsive-custom {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .nav-link span { display: none; }
            .sidebar-header h4 { display: none; }
            .main-content { margin-left: 70px; padding: 15px; }
            .stat-card h3 { font-size: 1.4rem; }
            .stat-card { padding: 15px; }
            .clock { width: 50px; height: 50px; }
        }
        @media (max-width: 576px) {
            .main-content { padding: 10px; }
            .stat-card h3 { font-size: 1.2rem; }
        }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-boxes"></i>
        <h4>Inventory Aset</h4>
    </div>
    <nav>
        <a class="nav-link active" href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <?php if($role == 'admin'): ?>
            <a class="nav-link" href="master.php"><i class="fas fa-database"></i> <span>Master Data</span></a>
            <a class="nav-link" href="inventory.php"><i class="fas fa-boxes"></i> <span>Kelola Inventory</span></a>
            <a class="nav-link" href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a>
        <?php endif; ?>
        <a class="nav-link" href="distribusi.php"><i class="fas fa-location-dot"></i> <span>Distribusi Barang</span></a>
        <a class="nav-link" href="monitoring.php"><i class="fas fa-map-marker-alt"></i> <span>Monitoring Lokasi</span></a>
        <a class="nav-link" href="laporan.php"><i class="fas fa-chart-line"></i> <span>Laporan</span></a>
        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h2><i class="fas fa-tachometer-alt me-2 text-primary"></i> Dashboard</h2>
        <div class="d-flex align-items-center mt-2 mt-sm-0">
            <canvas id="analogClock" class="clock" width="70" height="70"></canvas>
            <div class="dropdown ms-3">
                <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle"></i> <?= strtoupper($_SESSION['user']) ?> (<?= $role ?>)
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="edit_profile.php"><i class="fas fa-user-edit"></i> Edit Profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="row g-4 mb-4 row-cols-1 row-cols-md-2 row-cols-xl-4">
        <div class="col"><div class="stat-card" style="border-left-color: #4e73df;"><div class="d-flex justify-content-between"><div><small>Total Barang</small><h3><?= number_format($totalBarang) ?></h3></div><i class="fas fa-boxes fa-2x" style="color:#4e73df;"></i></div></div></div>
        <div class="col"><div class="stat-card" style="border-left-color: #1cc88a;"><div class="d-flex justify-content-between"><div><small>Total Item</small><h3><?= number_format($totalItems) ?></h3></div><i class="fas fa-tags fa-2x" style="color:#1cc88a;"></i></div></div></div>
        <div class="col"><div class="stat-card" style="border-left-color: #36b9cc;"><div class="d-flex justify-content-between"><div><small>Lokasi Terisi</small><h3><?= number_format($totalLokasi) ?></h3></div><i class="fas fa-building fa-2x" style="color:#36b9cc;"></i></div></div></div>
        <div class="col"><div class="stat-card" style="border-left-color: #e74a3b;"><div class="d-flex justify-content-between"><div><small>Barang Rusak</small><h3><?= number_format($rusak) ?></h3></div><i class="fas fa-exclamation-triangle fa-2x" style="color:#e74a3b;"></i></div></div></div>
    </div>
    
    <div class="table-container">
        <h5><i class="fas fa-trophy text-warning"></i> 5 Barang dengan Stok Terbanyak</h5>
        <div class="table-responsive-custom">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr><th>Barcode</th><th>Nama Barang</th><th>Kategori</th><th>Stok</th><th>Harga</th></tr>
                </thead>
                <tbody>
                    <?php foreach($topItems as $item): ?>
                    <tr>
                        <td><code><?= $item['barcode'] ?></code></td>
                        <td><?= $item['item_name'] ?></td>
                        <td><?= $item['category'] ?></td>
                        <td><span class="badge bg-primary"><?= number_format($item['total_quantity']) ?></span></td>
                        <td><?= rupiah($item['price']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function drawClock() {
    const canvas = document.getElementById('analogClock');
    if(!canvas) return;
    const ctx = canvas.getContext('2d');
    const radius = canvas.height/2;
    ctx.clearRect(0,0,canvas.width,canvas.height);
    const now = new Date();
    let hours = now.getHours() % 12;
    let minutes = now.getMinutes();
    let seconds = now.getSeconds();
    hours = (hours * Math.PI/6) + (minutes * Math.PI/(6*60)) + (seconds * Math.PI/(360*60));
    minutes = (minutes * Math.PI/30) + (seconds * Math.PI/(30*60));
    seconds = seconds * Math.PI/30;
    ctx.translate(radius, radius);
    ctx.beginPath(); ctx.arc(0,0,radius-5,0,2*Math.PI); ctx.fillStyle="white"; ctx.fill(); ctx.strokeStyle="#333"; ctx.lineWidth=2; ctx.stroke();
    ctx.font="10px Arial"; ctx.fillStyle="#333"; ctx.textAlign="center"; ctx.textBaseline="middle";
    for(let i=1;i<=12;i++){ let angle = i * Math.PI/6; let x = (radius-15)*Math.sin(angle); let y = -(radius-15)*Math.cos(angle); ctx.fillText(i.toString(),x,y); }
    ctx.beginPath(); ctx.moveTo(0,0); ctx.lineTo(Math.sin(hours)*(radius-20), -Math.cos(hours)*(radius-20)); ctx.lineWidth=4; ctx.strokeStyle="#333"; ctx.stroke();
    ctx.beginPath(); ctx.moveTo(0,0); ctx.lineTo(Math.sin(minutes)*(radius-12), -Math.cos(minutes)*(radius-12)); ctx.lineWidth=3; ctx.strokeStyle="#666"; ctx.stroke();
    ctx.beginPath(); ctx.moveTo(0,0); ctx.lineTo(Math.sin(seconds)*(radius-8), -Math.cos(seconds)*(radius-8)); ctx.lineWidth=1.5; ctx.strokeStyle="red"; ctx.stroke();
    ctx.beginPath(); ctx.arc(0,0,3,0,2*Math.PI); ctx.fillStyle="#00b4d8"; ctx.fill();
    ctx.setTransform(1,0,0,1,0,0);
}
setInterval(drawClock,1000);
drawClock();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>