<?php
require_once __DIR__ . "/includes/auth.php";
requireOwner();
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/../config/database.php";

$pageTitle = "Profil Owner";
$stmt = $pdo->prepare("SELECT owner_name, email, created_at FROM admins WHERE id=?");
$stmt->execute([$_SESSION["admin_id"]]);
$owner = $stmt->fetch();
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verifyCsrfToken($_POST["csrf_token"] ?? "");
    $name = trim($_POST["owner_name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $current = $_POST["current_password"] ?? "";
    $new = $_POST["new_password"] ?? "";

    $check = $pdo->prepare("SELECT password FROM admins WHERE id=?");
    $check->execute([$_SESSION["admin_id"]]);
    $hash = $check->fetchColumn();

    if (!$name || mb_strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Nama atau email tidak valid.";
    } elseif (!password_verify($current, $hash)) {
        $error = "Password saat ini tidak sesuai.";
    } elseif ($new !== "" && strlen($new) < 8) {
        $error = "Password baru minimal 8 karakter.";
    } else {
        try {
            if ($new !== "") {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $up = $pdo->prepare("UPDATE admins SET owner_name=?, email=?, password=? WHERE id=?");
                $up->execute([$name, $email, $newHash, $_SESSION["admin_id"]]);
            } else {
                $up = $pdo->prepare("UPDATE admins SET owner_name=?, email=? WHERE id=?");
                $up->execute([$name, $email, $_SESSION["admin_id"]]);
            }
            $_SESSION["owner_name"] = $name;
            $success = "Profil berhasil diperbarui.";
        } catch (PDOException $e) {
            $error = "Profil gagal diperbarui. Pastikan email belum digunakan.";
        }
    }
    $stmt->execute([$_SESSION["admin_id"]]);
    $owner = $stmt->fetch();
}

require __DIR__ . "/includes/header.php";
?>
<section class="page-heading"><div><span class="eyebrow">PENGATURAN AKUN</span><h1>Profil Owner</h1><p class="muted">Perbarui informasi akun pengelola website.</p></div></section>
<section class="panel form-panel">
<?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
<label>Nama Owner</label><input name="owner_name" required maxlength="100" value="<?= htmlspecialchars($owner["owner_name"]) ?>">
<label>Email</label><input type="email" name="email" required value="<?= htmlspecialchars($owner["email"]) ?>">
<hr>
<p class="muted">Masukkan password saat ini untuk menyimpan perubahan. Isi password baru hanya jika ingin menggantinya.</p>
<label>Password Saat Ini</label><input type="password" name="current_password" required autocomplete="current-password">
<label>Password Baru (opsional)</label><input type="password" name="new_password" minlength="8" autocomplete="new-password">
<button class="btn primary" type="submit">Simpan Perubahan</button>
</form>
</section>
<?php require __DIR__ . "/includes/footer.php"; ?>
