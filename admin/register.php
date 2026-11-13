<?php
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/../config/database.php";

if (!empty($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

$ownerExists = (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn() > 0;
if ($ownerExists) {
    header("Location: login.php");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verifyCsrfToken($_POST["csrf_token"] ?? "");

    $name = trim($_POST["owner_name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm_password"] ?? "";

    if ($name === "" || mb_strlen($name) > 100) {
        $error = "Nama owner wajib diisi dan maksimal 100 karakter.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $error = "Format email tidak valid.";
    } elseif (strlen($password) < 12) {
        $error = "Password minimal 12 karakter.";
    } elseif ($password !== $confirm) {
        $error = "Konfirmasi password tidak sama.";
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO admins (account_slot, owner_name, email, password) VALUES (1, ?, ?, ?)"
            );
            $stmt->execute([$name, $email, $hash]);
            header("Location: login.php?registered=1");
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Akun owner gagal dibuat. Pastikan belum ada akun owner.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registrasi Owner | Daun Cincau ID</title>
<link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="auth-page">
<section class="auth-card">
    <a class="auth-brand" href="../index.html">🌿 DAUN CINCAU ID</a>
    <span class="eyebrow">OWNER REGISTRATION</span>
    <h1>Buat Akun Owner</h1>
    <p class="muted">Registrasi hanya tersedia untuk pembuatan akun pertama. Setelah akun dibuat, pendaftaran akan ditutup otomatis.</p>
    <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
    <form method="post" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, "UTF-8") ?>">
        <label>Nama Owner</label>
        <input name="owner_name" maxlength="100" required value="<?= htmlspecialchars($_POST["owner_name"] ?? "", ENT_QUOTES, "UTF-8") ?>">
        <label>Email</label>
        <input type="email" name="email" maxlength="150" required autocomplete="email" value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>">
        <label>Password</label>
        <input type="password" name="password" minlength="12" required autocomplete="new-password">
        <small>Gunakan minimal 12 karakter dan jangan gunakan password yang sama dengan akun lain.</small>
        <label>Konfirmasi Password</label>
        <input type="password" name="confirm_password" minlength="12" required autocomplete="new-password">
        <button class="btn primary full" type="submit">Buat Akun Owner</button>
    </form>
</section>
</body>
</html>
