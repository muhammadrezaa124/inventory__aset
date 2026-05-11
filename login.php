<?php
require_once 'config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $stmt = $pdo->prepare("SELECT * FROM m_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventory Aset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeInUp 0.6s ease-out;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 35px 30px;
            text-align: center;
        }
        .login-header i { font-size: 3.5rem; color: #00b4d8; margin-bottom: 15px; }
        .login-header h3 { font-size: 1.8rem; font-weight: 600; }
        .login-body { background: white; padding: 35px 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { font-weight: 500; margin-bottom: 8px; display: block; }
        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            width: 100%;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            outline: none;
        }
        .password-toggle {
            position: absolute;
            right: 15px;
            cursor: pointer;
            color: #888;
            background: transparent;
            border: none;
            font-size: 1.1rem;
            z-index: 10;
        }
        .password-toggle:hover {
            color: #333;
        }
        .btn-login {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 10px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-login:hover { background: #00b4d8; transform: translateY(-2px); }
        .demo-info {
            margin-top: 20px;
            text-align: center;
            font-size: 0.85rem;
            background: #f0f2f5;
            padding: 10px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-boxes"></i>
            <h3>Inventory Aset</h3>
            <p>Sistem Informasi Inventory Terpadu</p>
            <small>By: Muhammad Reza</small>
        </div>
        <div class="login-body">
            <?php if($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control" required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-login">Login <i class="fas fa-sign-in-alt"></i></button>
            </form>
            <div class="demo-info">
                <i class="fas fa-info-circle"></i> Demo: <strong>admin / password</strong> (full akses) &nbsp;|&nbsp; <strong>staff / password</strong> (hanya lihat)
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePassword.addEventListener('click', function () {
            // Toggle type attribute
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            // Toggle icon
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>