<?php
require_once 'config.php';
if($_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }

$tab = $_GET['tab'] ?? 'item_types';

// Item Types
if(isset($_POST['add_item_type'])) { $pdo->prepare("INSERT INTO m_item_types (name) VALUES (?)")->execute([$_POST['name']]); header("Location: master.php"); exit; }
if(isset($_GET['delete_item_type'])) { $pdo->prepare("DELETE FROM m_item_types WHERE id=?")->execute([$_GET['delete_item_type']]); header("Location: master.php"); exit; }

// Items
if(isset($_POST['add_item'])) { $pdo->prepare("INSERT INTO m_items (item_type_id, name, description) VALUES (?,?,?)")->execute([$_POST['item_type_id'], $_POST['name'], $_POST['description']]); header("Location: master.php"); exit; }
if(isset($_GET['delete_item'])) { $pdo->prepare("DELETE FROM m_items WHERE id=?")->execute([$_GET['delete_item']]); header("Location: master.php"); exit; }

// Buildings
if(isset($_POST['add_building'])) { $pdo->prepare("INSERT INTO m_buildings (name) VALUES (?)")->execute([$_POST['name']]); header("Location: master.php"); exit; }
if(isset($_GET['delete_building'])) { $pdo->prepare("DELETE FROM m_buildings WHERE id=?")->execute([$_GET['delete_building']]); header("Location: master.php"); exit; }

// Rooms
if(isset($_POST['add_room'])) { $pdo->prepare("INSERT INTO m_rooms (building_id, name) VALUES (?,?)")->execute([$_POST['building_id'], $_POST['name']]); header("Location: master.php"); exit; }
if(isset($_GET['delete_room'])) { $pdo->prepare("DELETE FROM m_rooms WHERE id=?")->execute([$_GET['delete_room']]); header("Location: master.php"); exit; }

$itemTypes = $pdo->query("SELECT * FROM m_item_types ORDER BY id DESC")->fetchAll();
$items = $pdo->query("SELECT i.*, t.name as type_name FROM m_items i JOIN m_item_types t ON i.item_type_id = t.id ORDER BY i.id DESC")->fetchAll();
$buildings = $pdo->query("SELECT * FROM m_buildings ORDER BY id DESC")->fetchAll();
$rooms = $pdo->query("SELECT r.*, b.name as building_name FROM m_rooms r JOIN m_buildings b ON r.building_id = b.id ORDER BY r.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Data - Inventory Aset</title>
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
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header"><i class="fas fa-boxes"></i><h4>Inventory Aset</h4></div>
    <nav>
        <a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a class="nav-link active" href="master.php"><i class="fas fa-database"></i> <span>Master Data</span></a>
        <a class="nav-link" href="inventory.php"><i class="fas fa-boxes"></i> <span>Kelola Inventory</span></a>
        <a class="nav-link" href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a>
        <a class="nav-link" href="distribusi.php"><i class="fas fa-location-dot"></i> <span>Distribusi Barang</span></a>
        <a class="nav-link" href="monitoring.php"><i class="fas fa-map-marker-alt"></i> <span>Monitoring Lokasi</span></a>
        <a class="nav-link" href="laporan.php"><i class="fas fa-chart-line"></i> <span>Laporan</span></a>
        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </nav>
</div>
<div class="main-content">
    <h2 class="mb-4"><i class="fas fa-database me-2 text-primary"></i> Master Data</h2>
    <ul class="nav nav-tabs">
        <li class="nav-item"><a class="nav-link <?= $tab=='item_types'?'active':'' ?>" href="?tab=item_types">Item Types</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab=='items'?'active':'' ?>" href="?tab=items">Items</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab=='buildings'?'active':'' ?>" href="?tab=buildings">Buildings</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab=='rooms'?'active':'' ?>" href="?tab=rooms">Rooms</a></li>
    </ul>
    <div class="table-container">
        <?php if($tab == 'item_types'): ?>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalItemType">+ Tambah Item Type</button>
            <table class="table table-bordered"><thead class="table-light"><tr><th>ID</th><th>Nama</th><th>Aksi</th></tr></thead>
            <tbody><?php foreach($itemTypes as $row): ?><tr><td><?= $row['id'] ?></td><td><?= $row['name'] ?></td><td><a href="?delete_item_type=<?= $row['id'] ?>&tab=item_types" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')">Hapus</a></td></tr><?php endforeach; ?></tbody></table>
        <?php elseif($tab == 'items'): ?>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalItem">+ Tambah Item</button>
            <table class="table table-bordered"><thead class="table-light"><tr><th>ID</th><th>Nama</th><th>Tipe</th><th>Deskripsi</th><th>Aksi</th></tr></thead>
            <tbody><?php foreach($items as $row): ?><tr><td><?= $row['id'] ?></td><td><?= $row['name'] ?></td><td><?= $row['type_name'] ?></td><td><?= $row['description'] ?></td><td><a href="?delete_item=<?= $row['id'] ?>&tab=items" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')">Hapus</a></td></table><?php endforeach; ?></tbody><td>
        <?php elseif($tab == 'buildings'): ?>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalBuilding">+ Tambah Gedung</button>
            <table class="table table-bordered"><thead class="table-light"><tr><th>ID</th><th>Nama Gedung</th><th>Aksi</th></tr></thead>
            <tbody><?php foreach($buildings as $row): ?><tr><td><?= $row['id'] ?></td><td><?= $row['name'] ?></td><td><a href="?delete_building=<?= $row['id'] ?>&tab=buildings" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')">Hapus</a></td></tr><?php endforeach; ?></tbody></table>
        <?php else: ?>
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalRoom">+ Tambah Ruangan</button>
            <table class="table table-bordered"><thead class="table-light"><tr><th>ID</th><th>Nama Ruangan</th><th>Gedung</th><th>Aksi</th></tr></thead>
            <tbody><?php foreach($rooms as $row): ?><tr><td><?= $row['id'] ?></td><td><?= $row['name'] ?></tr><td><?= $row['building_name'] ?></td><td><a href="?delete_room=<?= $row['id'] ?>&tab=rooms" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')">Hapus</a></td></tr><?php endforeach; ?></tbody></table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalItemType"><div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header"><h5>Tambah Item Type</h5></div><div class="modal-body"><input type="text" name="name" class="form-control" required></div><div class="modal-footer"><button type="submit" name="add_item_type" class="btn btn-primary">Simpan</button></div></form></div></div></div>
<div class="modal fade" id="modalItem"><div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header"><h5>Tambah Item</h5></div><div class="modal-body"><select name="item_type_id" class="form-control mb-2" required><option value="">Pilih Tipe</option><?php foreach($itemTypes as $t) echo "<option value='{$t['id']}'>{$t['name']}</option>"; ?></select><input type="text" name="name" class="form-control mb-2" placeholder="Nama Item" required><textarea name="description" class="form-control" placeholder="Deskripsi"></textarea></div><div class="modal-footer"><button type="submit" name="add_item" class="btn btn-primary">Simpan</button></div></form></div></div></div>
<div class="modal fade" id="modalBuilding"><div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header"><h5>Tambah Gedung</h5></div><div class="modal-body"><input type="text" name="name" class="form-control" required></div><div class="modal-footer"><button type="submit" name="add_building" class="btn btn-primary">Simpan</button></div></form></div></div></div>
<div class="modal fade" id="modalRoom"><div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header"><h5>Tambah Ruangan</h5></div><div class="modal-body"><select name="building_id" class="form-control mb-2" required><option value="">Pilih Gedung</option><?php foreach($buildings as $b) echo "<option value='{$b['id']}'>{$b['name']}</option>"; ?></select><input type="text" name="name" class="form-control" placeholder="Nama Ruangan" required></div><div class="modal-footer"><button type="submit" name="add_room" class="btn btn-primary">Simpan</button></div></form></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>