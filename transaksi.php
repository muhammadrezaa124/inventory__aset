<?php
require_once 'config.php';
if($_SESSION['role'] != 'admin') { header("Location: index.php"); exit; }

$transTypes = $pdo->query("SELECT * FROM m_transaction_type")->fetchAll();
$inventory = $pdo->query("SELECT i.id, i.barcode, m.name as item_name, i.total_quantity FROM t_inventory i JOIN m_items m ON i.item_id = m.id WHERE i.total_quantity > 0")->fetchAll();

// Tambah transaksi baru
if(isset($_POST['add_transaction'])) {
    $trans_number = 'TRX-' . date('Ymd') . rand(100, 999);
    $evidence = null;
    if(isset($_FILES['evidence']) && $_FILES['evidence']['error'] == 0) {
        $evidence = uploadFile($_FILES['evidence'], 'uploads/bukti/');
    }
    $stmt = $pdo->prepare("INSERT INTO t_inventory_transactions (transaction_number, transaction_date, transaction_type_id, total_budget, budget_realization, source_of_funds, evidence_file, notes, status) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$trans_number, $_POST['date'], $_POST['type_id'], $_POST['total_budget'], $_POST['realization'], $_POST['source_of_funds'], $evidence, $_POST['notes'], 'approved']);
    $trans_id = $pdo->lastInsertId();
    
    $item_ids = $_POST['item_id'];
    $quantities = $_POST['quantity'];
    $prices = $_POST['price'];
    
    for($i = 0; $i < count($item_ids); $i++) {
        $pdo->prepare("INSERT INTO t_inventory_transaction_details (transaction_id, inventory_id, quantity, price) VALUES (?,?,?,?)")->execute([$trans_id, $item_ids[$i], $quantities[$i], $prices[$i]]);
        // Update stok: jenis 1=Pembelian, 2=Hibah -> tambah; lainnya -> kurang
        if($_POST['type_id'] == 1 || $_POST['type_id'] == 2) {
            $pdo->prepare("UPDATE t_inventory SET total_quantity = total_quantity + ? WHERE id = ?")->execute([$quantities[$i], $item_ids[$i]]);
        } else {
            // validasi stok cukup
            $check = $pdo->prepare("SELECT total_quantity FROM t_inventory WHERE id=?");
            $check->execute([$item_ids[$i]]);
            $stokSekarang = $check->fetchColumn();
            if($stokSekarang < $quantities[$i]) {
                // rollback transaksi
                $pdo->prepare("DELETE FROM t_inventory_transactions WHERE id=?")->execute([$trans_id]);
                die("<script>alert('Stok tidak mencukupi untuk barang tertentu!'); window.location.href='transaksi.php';</script>");
            }
            $pdo->prepare("UPDATE t_inventory SET total_quantity = total_quantity - ? WHERE id = ?")->execute([$quantities[$i], $item_ids[$i]]);
        }
    }
    header("Location: transaksi.php");
    exit;
}

// Hapus transaksi (rollback stok)
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // ambil detail
    $details = $pdo->prepare("SELECT inventory_id, quantity FROM t_inventory_transaction_details WHERE transaction_id=?");
    $details->execute([$id]);
    $trans = $pdo->prepare("SELECT transaction_type_id FROM t_inventory_transactions WHERE id=?");
    $trans->execute([$id]);
    $type_id = $trans->fetchColumn();
    foreach($details as $det) {
        if($type_id == 1 || $type_id == 2) {
            // pembelian/hibah -> kurangi stok
            $pdo->prepare("UPDATE t_inventory SET total_quantity = total_quantity - ? WHERE id = ?")->execute([$det['quantity'], $det['inventory_id']]);
        } else {
            // penghapusan/mutasi -> tambah stok
            $pdo->prepare("UPDATE t_inventory SET total_quantity = total_quantity + ? WHERE id = ?")->execute([$det['quantity'], $det['inventory_id']]);
        }
    }
    $pdo->prepare("DELETE FROM t_inventory_transactions WHERE id=?")->execute([$id]);
    header("Location: transaksi.php");
    exit;
}

$transactions = $pdo->query("SELECT t.*, tt.name as type_name FROM t_inventory_transactions t JOIN m_transaction_type tt ON t.transaction_type_id = tt.id ORDER BY t.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        <a class="nav-link" href="master.php"><i class="fas fa-database"></i> <span>Master Data</span></a>
        <a class="nav-link" href="inventory.php"><i class="fas fa-boxes"></i> <span>Kelola Inventory</span></a>
        <a class="nav-link active" href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a>
        <a class="nav-link" href="distribusi.php"><i class="fas fa-location-dot"></i> <span>Distribusi Barang</span></a>
        <a class="nav-link" href="monitoring.php"><i class="fas fa-map-marker-alt"></i> <span>Monitoring Lokasi</span></a>
        <a class="nav-link" href="laporan.php"><i class="fas fa-chart-line"></i> <span>Laporan</span></a>
        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </nav>
</div>

<div class="main-content">
    <h2 class="mb-4"><i class="fas fa-exchange-alt me-2 text-primary"></i> Transaksi</h2>
    
    <div class="form-container">
        <h5>Input Transaksi Baru</h5>
        <form method="POST" enctype="multipart/form-data" id="transForm">
            <div class="row g-3 mb-3">
                <div class="col-md-3"><input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                <div class="col-md-3"><select name="type_id" class="form-control" required><option value="">Jenis</option><?php foreach($transTypes as $t) echo "<option value='{$t['id']}'>{$t['name']}</option>"; ?></select></div>
                <div class="col-md-3"><input type="text" name="source_of_funds" class="form-control" placeholder="Sumber Dana"></div>
                <div class="col-md-3"><input type="number" name="total_budget" class="form-control" placeholder="Total Anggaran"></div>
                <div class="col-md-3"><input type="number" name="realization" class="form-control" placeholder="Realisasi"></div>
                <div class="col-md-12"><textarea name="notes" class="form-control" rows="2" placeholder="Catatan"></textarea></div>
                <div class="col-md-12"><input type="file" name="evidence" class="form-control" accept="image/*,application/pdf"></div>
            </div>
            <div id="itemsContainer">
                <div class="row item-row g-2 mb-2">
                    <div class="col-md-5"><select name="item_id[]" class="form-control" required><option value="">Pilih Barang</option><?php foreach($inventory as $i) echo "<option value='{$i['id']}'>{$i['barcode']} - {$i['item_name']} (Stok: {$i['total_quantity']})</option>"; ?></select></div>
                    <div class="col-md-3"><input type="number" name="quantity[]" class="form-control" placeholder="Jumlah" required></div>
                    <div class="col-md-3"><input type="number" step="0.01" name="price[]" class="form-control" placeholder="Harga" required></div>
                    <div class="col-md-1"><button type="button" class="btn btn-danger remove-item">Hapus</button></div>
                </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm mb-3" id="addItemBtn">+ Tambah Barang</button>
            <button type="submit" name="add_transaction" class="btn btn-primary">Simpan Transaksi</button>
        </form>
    </div>
    
    <div class="table-container">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr><th>No. Transaksi</th><th>Tanggal</th><th>Jenis</th><th>Sumber Dana</th><th>Anggaran</th><th>Realisasi</th><th>Bukti</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php foreach($transactions as $row): ?>
                <tr>
                    <td><?= $row['transaction_number'] ?></td>
                    <td><?= date('d/m/Y', strtotime($row['transaction_date'])) ?></td>
                    <td><?= $row['type_name'] ?></td>
                    <td><?= $row['source_of_funds'] ?></td>
                    <td><?= rupiah($row['total_budget']) ?></td>
                    <td><?= rupiah($row['budget_realization']) ?></td>
                    <td><?php if($row['evidence_file']) echo "<a href='uploads/bukti/".$row['evidence_file']."' target='_blank'>Lihat</a>"; else echo "-"; ?></td>
                    <td><a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus transaksi? Stok akan dikembalikan.')">Hapus</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$('#addItemBtn').click(function() {
    var newRow = $('.item-row:first').clone();
    newRow.find('input').val('');
    newRow.find('select').val('');
    $('#itemsContainer').append(newRow);
});
$(document).on('click', '.remove-item', function() {
    if($('.item-row').length > 1) $(this).closest('.item-row').remove();
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>