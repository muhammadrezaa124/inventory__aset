<?php
session_start();

$host = 'localhost';
$dbname = 'inventori_db';   // ← gunakan database Anda
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

function rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

function statusBadge($status) {
    $badges = [
        'good' => 'success', 'damaged' => 'danger', 'expired' => 'warning',
        'pending' => 'secondary', 'approved' => 'primary', 'rejected' => 'dark'
    ];
    $class = $badges[$status] ?? 'secondary';
    return "<span class='badge bg-$class'>" . ucfirst($status) . "</span>";
}

function generateBarcode() {
    return 'BRC-' . time() . rand(100, 999);
}

function uploadFile($file, $targetDir) {
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = time() . '_' . uniqid() . '.' . $ext;
    $targetPath = $targetDir . $fileName;
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $fileName;
    }
    return null;
}

if (basename($_SERVER['PHP_SELF']) != 'login.php' && !isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
?>