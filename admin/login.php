<?php
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/../config/database.php";

if (!empty($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
if (!isset($_SESSION["login_attempts"])) $_SESSION["login_attempts"] = 0;
if (!isset($_SESSION["login_window"])) $_SESSION["login_window"] = time();

if (time() - $_SESSION["login_window"] > 900) {
    $_SESSION["login_attempts"] = 0;
    $_SESSION["login_window"] = time();
}

$locked = $_SESSION["login_attempts"] >= 5;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verifyCsrfToken($_POST["csrf_token"] ?? "");

    if ($locked) {
        $error = "Terlalu banyak percobaan login. Tunggu 15 menit.";
    } else {
        $email = strtolower(trim($_POST["email"] ?? ""));
        $password = $_POST["password"] ?? "";

        $stmt = $pdo->prepare("SELECT id, owner_name, email, password FROM admins WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $owner = $stmt->fetch();

        if ($owner && password_verify($password, $owner["password"])) {
            session_regenerate_id(true);
            $_SESSION["admin_id"] = (int)$owner["id"];
            $_SESSION["owner_name"] = $owner["owner_name"];
            $_SESSION["login_attempts"] = 0;
            unset($_SESSION["csrf_token"]);
            header("Location: dashboard.php");
            exit;
        }

        $_SESSION["login_attempts"]++;
        $error = "Email atau password tidak sesuai.";
        $locked = $_SESSION["login_attempts"] >= 5;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login Owner | Daun Cincau ID</title>
<link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="auth-page">
<section class="auth-card">
    <a class="auth-brand" href="../index.html">🌿 DAUN CINCAU ID</a>
    <span class="eyebrow">OWNER CMS</span>
    <h1>Selamat Datang</h1>
    <p class="muted">Masuk untuk mengelola pesanan dan pembayaran.</p>
    <?php if (isset($_GET["registered"])): ?><div class="alert success">Akun owner berhasil dibuat. Silakan login.</div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, "UTF-8") ?>">
        <label>Email</label>
        <input type="email" name="email" required autocomplete="username">
        <label>Password</label>
        <input type="password" name="password" required autocomplete="current-password">
        <button class="btn primary full" type="submit" <?= $locked ? "disabled" : "" ?>>Login</button>
    </form>
    <p class="auth-foot">Belum ada akun owner? <a href="register.php">Daftar akun pertama</a></p>
</section>
</body>
</html>
