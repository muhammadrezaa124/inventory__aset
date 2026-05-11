<?php
require_once 'config.php';
if($_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }

$items = $pdo->query("SELECT id, name FROM m_items ORDER BY name")->fetchAll();

// Tambah stok baru
if(isset($_POST['add_stock'])) {
    $barcode = generateBarcode();
    $image = null;
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = uploadFile($_FILES['image'], 'uploads/barang/');
    }
    $stmt = $pdo->prepare("INSERT INTO t_inventory (item_id, barcode, total_quantity, price, expired_date, image, status) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['item_id'], $barcode, $_POST['quantity'], $_POST['price'],
        $_POST['expired_date'] ?: null, $image, 'good'
    ]);
    header("Location: inventory.php");
    exit;
}

// Hapus inventory
if(isset($_GET['delete'])) {
    $stmt = $pdo->prepare("SELECT image FROM t_inventory WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    $img = $stmt->fetchColumn();
    if($img && file_exists("uploads/barang/".$img)) unlink("uploads/barang/".$img);
    $pdo->prepare("DELETE FROM t_inventory WHERE id=?")->execute([$_GET['delete']]);
    header("Location: inventory.php");
    exit;
}

// Update inventory
if(isset($_POST['update_stock'])) {
    $data = [
        'total_quantity' => $_POST['quantity'],
        'price' => $_POST['price'],
        'expired_date' => $_POST['expired_date'] ?: null,
        'status' => $_POST['status'],
        'id' => $_POST['id']
    ];
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $old = $pdo->prepare("SELECT image FROM t_inventory WHERE id=?");
        $old->execute([$_POST['id']]);
        $oldImg = $old->fetchColumn();
        if($oldImg && file_exists("uploads/barang/".$oldImg)) unlink("uploads/barang/".$oldImg);
        $newImage = uploadFile($_FILES['image'], 'uploads/barang/');
        if($newImage) $data['image'] = $newImage;
    }
    $sql = "UPDATE t_inventory SET total_quantity=:total_quantity, price=:price, expired_date=:expired_date, status=:status";
    if(isset($data['image'])) $sql .= ", image=:image";
    $sql .= " WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    header("Location: inventory.php");
    exit;
}

$inventory = $pdo->query("
    SELECT i.*, m.name as item_name, t.name as type_name 
    FROM t_inventory i 
    JOIN m_items m ON i.item_id = m.id 
    JOIN m_item_types t ON m.item_type_id = t.id
    ORDER BY i.id DESC
")->fetchAll();

$editData = null;
if(isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM t_inventory WHERE id=?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; }
        .sidebar { /* sama seperti sebelumnya */ position: fixed; top: 0; left: 0; height: 100%; width: 280px; background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); color: white; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header i { font-size: 2.5rem; color: #00b4d8; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 25px; display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .sidebar .nav-link:hover { background: rgba(0,180,216,0.2); }
        .sidebar .nav-link.active { background: #00b4d8; }
        .main-content { margin-left: 280px; padding: 20px; }
        .table-container, .form-container { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 20px; }
        @media (max-width: 768px) { .sidebar { width: 70px; } .sidebar .nav-link span { display: none; } .sidebar-header h4 { display: none; } .main-content { margin-left: 70px; } }
        img.preview { max-width: 50px; max-height: 50px; border-radius: 5px; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-header"><i class="fas fa-boxes"></i><h4>Inventory Aset</h4></div>
    <nav>
        <a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a class="nav-link" href="master.php"><i class="fas fa-database"></i> <span>Master Data</span></a>
        <a class="nav-link active" href="inventory.php"><i class="fas fa-boxes"></i> <span>Kelola Inventory</span></a>
        <a class="nav-link" href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a>
        <a class="nav-link" href="distribusi.php"><i class="fas fa-location-dot"></i> <span>Distribusi Barang</span></a>
        <a class="nav-link" href="monitoring.php"><i class="fas fa-map-marker-alt"></i> <span>Monitoring Lokasi</span></a>
        <a class="nav-link" href="laporan.php"><i class="fas fa-chart-line"></i> <span>Laporan</span></a>
        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </nav>
</div>

<div class="main-content">
    <h2 class="mb-4"><i class="fas fa-boxes me-2 text-primary"></i> Kelola Inventory</h2>
    
    <div class="form-container">
        <h5>Tambah Stok Barang</h5>
        <form method="POST" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-5"><select name="item_id" class="form-control" required><option value="">Pilih Barang</option><?php foreach($items as $item) echo "<option value='{$item['id']}'>{$item['name']}</option>"; ?></select></div>
            <div class="col-md-2"><input type="number" name="quantity" class="form-control" placeholder="Jumlah" required></div>
            <div class="col-md-3"><input type="number" step="0.01" name="price" class="form-control" placeholder="Harga" required></div>
            <div class="col-md-2"><input type="date" name="expired_date" class="form-control" placeholder="Expired Date"></div>
            <div class="col-md-12"><input type="file" name="image" class="form-control" accept="image/*"></div>
            <div class="col-md-12"><button type="submit" name="add_stock" class="btn btn-primary">+ Tambah Stok</button></div>
        </form>
    </div>
    
    <?php if($editData): ?>
    <div class="form-container">
        <h5>Edit Stok Barang</h5>
        <form method="POST" enctype="multipart/form-data" class="row g-3">
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
            <div class="col-md-2"><input type="number" name="quantity" class="form-control" value="<?= $editData['total_quantity'] ?>" required></div>
            <div class="col-md-2"><input type="number" step="0.01" name="price" class="form-control" value="<?= $editData['price'] ?>" required></div>
            <div class="col-md-2"><input type="date" name="expired_date" class="form-control" value="<?= $editData['expired_date'] ?>"></div>
            <div class="col-md-2"><select name="status" class="form-control"><option value="good" <?= $editData['status']=='good'?'selected':'' ?>>Good</option><option value="damaged" <?= $editData['status']=='damaged'?'selected':'' ?>>Damaged</option><option value="expired" <?= $editData['status']=='expired'?'selected':'' ?>>Expired</option></select></div>
            <div class="col-md-12"><input type="file" name="image" class="form-control" accept="image/*"> <?php if($editData['image']) echo "<img src='uploads/barang/".$editData['image']."' class='preview mt-2'>"; ?></div>
            <div class="col-md-12"><button type="submit" name="update_stock" class="btn btn-warning">Update Stok</button></div>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="table-container">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr><th>Barcode</th><th>Nama Barang</th><th>Tipe</th><th>Stok</th><th>Harga</th><th>Expired</th><th>Status</th><th>Foto</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php foreach($inventory as $row): ?>
                <tr>
                    <td><code><?= $row['barcode'] ?></code></td>
                    <td><?= $row['item_name'] ?></td>
                    <td><?= $row['type_name'] ?></td>
                    <td><?= number_format($row['total_quantity']) ?></td>
                    <td><?= rupiah($row['price']) ?></td>
                    <td><?= $row['expired_date'] ? date('d/m/Y',strtotime($row['expired_date'])) : '-' ?></td>
                    <td><?= statusBadge($row['status']) ?></td>
                    <td><?php if($row['image']) echo "<img src='uploads/barang/".$row['image']."' class='preview'>"; else echo "-"; ?></td>
                    <td><a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a> <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin?')">Hapus</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>