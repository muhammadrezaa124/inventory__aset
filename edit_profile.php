<?php
require_once 'config.php';

$user = $_SESSION['user'];
$role = $_SESSION['role'];
$errors = [];
$success = '';

// Ambil data user saat ini
$stmt = $pdo->prepare("SELECT name, email FROM m_users WHERE username = ?");
$stmt->execute([$user]);
$data = $stmt->fetch();
$name = $data['name'] ?? '';
$email = $data['email'] ?? '';

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $new_name = trim($_POST['name']);
        $new_email = trim($_POST['email']);

        if (empty($new_name)) $errors[] = "Nama tidak boleh kosong.";
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email tidak valid.";

        if (empty($errors)) {
            $update = $pdo->prepare("UPDATE m_users SET name = ?, email = ? WHERE username = ?");
            $update->execute([$new_name, $new_email, $user]);
            $success = "Profil berhasil diperbarui.";
            $name = $new_name;
            $email = $new_email;
        }
    }
    elseif (isset($_POST['change_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        // Cek password lama
        $check = $pdo->prepare("SELECT password FROM m_users WHERE username = ?");
        $check->execute([$user]);
        $hash = $check->fetchColumn();
        if (!password_verify($old_pass, $hash)) {
            $errors[] = "Password lama salah.";
        }
        elseif (strlen($new_pass) < 4) {
            $errors[] = "Password baru minimal 4 karakter.";
        }
        elseif ($new_pass !== $confirm_pass) {
            $errors[] = "Konfirmasi password tidak cocok.";
        }
        else {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE m_users SET password = ? WHERE username = ?");
            $update->execute([$new_hash, $user]);
            $success = "Password berhasil diubah.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Inventory Aset</title>
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
        .profile-card {
            background: white; border-radius: 15px; padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08); max-width: 700px; margin: auto;
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
        <a class="nav-link" href="monitoring.php"><i class="fas fa-map-marker-alt"></i> <span>Monitoring Lokasi</span></a>
        <a class="nav-link" href="laporan.php"><i class="fas fa-chart-line"></i> <span>Laporan</span></a>
        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h2><i class="fas fa-user-edit me-2 text-primary"></i> Edit Profil</h2>
        <div class="d-flex align-items-center mt-2 mt-sm-0">
            <canvas id="analogClock" class="clock" width="70" height="70" style="width:70px;height:70px;background:white;border-radius:50%;box-shadow:0 0 10px rgba(0,0,0,0.2);margin-right:15px;"></canvas>
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

    <div class="profile-card">
        <?php if($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        <?php if(!empty($errors)): ?>
            <div class="alert alert-danger"><?= implode('<br>', $errors) ?></div>
        <?php endif; ?>

        <h5 class="mb-3"><i class="fas fa-id-card"></i> Informasi Akun</h5>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['user']) ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <button type="submit" name="update_profile" class="btn btn-primary">Simpan Perubahan</button>
        </form>

        <hr class="my-4">

        <h5 class="mb-3"><i class="fas fa-key"></i> Ganti Password</h5>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Password Lama</label>
                <input type="password" name="old_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" name="change_password" class="btn btn-warning">Ganti Password</button>
        </form>
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