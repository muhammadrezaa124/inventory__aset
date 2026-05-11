<?php
require_once 'config.php';
$role = $_SESSION['role'];

$inventory = $pdo->query("SELECT i.id, i.barcode, m.name as item_name, i.total_quantity FROM t_inventory i JOIN m_items m ON i.item_id = m.id WHERE i.total_quantity > 0")->fetchAll();
$rooms = $pdo->query("SELECT r.*, b.name as building_name FROM m_rooms r JOIN m_buildings b ON r.building_id = b.id")->fetchAll();
$distributions = $pdo->query("
    SELECT d.*, i.barcode, m.name as item_name, r.name as room_name, b.name as building_name 
    FROM t_inventory_room d 
    JOIN t_inventory i ON d.inventory_id = i.id 
    JOIN m_items m ON i.item_id = m.id 
    JOIN m_rooms r ON d.room_id = r.id 
    JOIN m_buildings b ON r.building_id = b.id 
    ORDER BY d.created_at DESC
")->fetchAll();

if($role == 'admin') {
    if(isset($_POST['assign'])) {
        $check = $pdo->prepare("SELECT * FROM t_inventory_room WHERE inventory_id = ? AND room_id = ?");
        $check->execute([$_POST['inventory_id'], $_POST['room_id']]);
        if($check->rowCount() > 0) {
            $pdo->prepare("UPDATE t_inventory_room SET quantity = quantity + ? WHERE inventory_id = ? AND room_id = ?")->execute([$_POST['quantity'], $_POST['inventory_id'], $_POST['room_id']]);
        } else {
            $pdo->prepare("INSERT INTO t_inventory_room (inventory_id, room_id, quantity) VALUES (?,?,?)")->execute([$_POST['inventory_id'], $_POST['room_id'], $_POST['quantity']]);
        }
        header("Location: distribusi.php");
        exit;
    }
    if(isset($_GET['delete'])) {
        $pdo->prepare("DELETE FROM t_inventory_room WHERE id=?")->execute([$_GET['delete']]);
        header("Location: distribusi.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Distribusi Barang</title>
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
        .table-container, .form-container {
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
        <a class="nav-link active" href="distribusi.php"><i class="fas fa-location-dot"></i> <span>Distribusi Barang</span></a>
        <a class="nav-link" href="monitoring.php"><i class="fas fa-map-marker-alt"></i> <span>Monitoring Lokasi</span></a>
        <a class="nav-link" href="laporan.php"><i class="fas fa-chart-line"></i> <span>Laporan</span></a>
        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </nav>
</div>

<div class="main-content">
    <h2 class="mb-4"><i class="fas fa-location-dot me-2 text-primary"></i> Distribusi Barang</h2>
    
    <?php if($role == 'admin'): ?>
    <div class="form-container">
        <form method="POST" class="row g-3">
            <div class="col-md-4"><select name="inventory_id" class="form-control" required><option value="">Pilih Barang</option><?php foreach($inventory as $i) echo "<option value='{$i['id']}'>{$i['barcode']} - {$i['item_name']} (Stok: {$i['total_quantity']})</option>"; ?></select></div>
            <div class="col-md-4"><select name="room_id" class="form-control" required><option value="">Pilih Ruangan</option><?php foreach($rooms as $r) echo "<option value='{$r['id']}'>{$r['building_name']} - {$r['name']} (Lantai {$r['floor']})</option>"; ?></select></div>
            <div class="col-md-2"><input type="number" name="quantity" class="form-control" placeholder="Jumlah" required></div>
            <div class="col-md-2"><button type="submit" name="assign" class="btn btn-primary w-100">Distribusikan</button></div>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="table-container">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr><th>Barcode</th><th>Nama Barang</th><th>Gedung</th><th>Ruangan</th><th>Jumlah</th><?php if($role=='admin') echo '<th>Aksi</th>'; ?></tr>
            </thead>
            <tbody>
                <?php foreach($distributions as $row): ?>
                <tr>
                    <td><?= $row['barcode'] ?></td>
                    <td><?= $row['item_name'] ?></td>
                    <td><?= $row['building_name'] ?></td>
                    <td><?= $row['room_name'] ?></td>
                    <td><?= number_format($row['quantity']) ?></td>
                    <?php if($role=='admin'): ?>
                    <td><a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus distribusi?')">Hapus</a></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>