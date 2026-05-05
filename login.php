<?php
require_once 'config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            header("Location: index.php");
            exit;
        }
    }
    $error = "Username atau password salah!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SI Apotek</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.1">
</head>
<body class="login-page">
    <div class="login-card">
        <div style="width: 72px; height: 72px; background: linear-gradient(135deg, var(--primary), #8b5cf6); color: white; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px; box-shadow: 0 12px 24px rgba(79, 70, 229, 0.3); position: relative;">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <div style="position: absolute; top: -4px; right: -4px; width: 20px; height: 20px; background: var(--success); border: 3px solid #0b0e14; border-radius: 50%;"></div>
        </div>
        <h1>SI Apotek</h1>
        <p>Sistem Informasi Manajemen Apotek Modern</p>
        
        <?php if ($error): ?>
            <div style="padding: 14px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 14px; color: #fca5a5; margin-bottom: 24px; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group-premium">
                <label>Username</label>
                <div class="input-wrapper">
                    <input type="text" name="username" required placeholder="Masukkan username">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
            </div>
            <div class="input-group-premium" style="margin-bottom: 32px;">
                <label>Password</label>
                <div class="input-wrapper">
                    <input type="password" name="password" required placeholder="••••••••">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 16px; font-size: 1rem; border-radius: 14px; background: linear-gradient(135deg, var(--primary), #6366f1);">
                Masuk Sekarang
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </button>
        </form>
        
        <div style="margin-top: 40px; font-size: 0.8rem; color: #64748b; letter-spacing: 0.02em;">
            &copy; 2026 DKJ Apotek Sederhana. <br>All rights reserved.
        </div>
    </div>
</body>
</html>
