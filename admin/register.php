<?php

require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/../config/database.php";

if (!empty($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

// Periksa apakah akun Owner sudah dibuat
$ownerExists = (int) $pdo->query(
    "SELECT COUNT(*) FROM admins"
)->fetchColumn() > 0;

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
    $confirmPassword = $_POST["confirm_password"] ?? "";

    // VALIDASI NAMA
    if ($name === "" || mb_strlen($name) > 100) {

        $error = "Nama owner wajib diisi dan maksimal 100 karakter.";

    // VALIDASI EMAIL
    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 150
    ) {

        $error = "Format email tidak valid.";

    // VALIDASI PASSWORD
    } elseif (strlen($password) < 8) {

        $error = "Password minimal 8 karakter.";

    // VALIDASI KONFIRMASI PASSWORD
    } elseif ($password !== $confirmPassword) {

        $error = "Konfirmasi password tidak sama.";

    } else {

        try {

            $hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                INSERT INTO admins (
                    account_slot,
                    owner_name,
                    email,
                    password
                ) VALUES (1, ?, ?, ?)
            ");

            $stmt->execute([
                $name,
                $email,
                $hash
            ]);

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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrasi Owner | Daun Cincau ID</title>

    <link rel="stylesheet" href="assets/css/admin.css">

</head>

<body class="auth-page">

    <section class="auth-card">

        <a class="auth-brand" href="../index.html">
            🌿 DAUN CINCAU ID
        </a>

        <span class="eyebrow">
            OWNER REGISTRATION
        </span>

        <h1>Buat Akun Owner</h1>

        <p class="muted">
            Buat akun untuk mulai mengelola website
            Daun Cincau ID.
        </p>


        <?php if ($error): ?>

            <div class="alert error">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>

        <?php endif; ?>


        <form method="POST" autocomplete="on">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, "UTF-8") ?>"
            >


            <!-- NAMA OWNER -->

            <label for="owner_name">
                Nama Owner
            </label>

            <input
                type="text"
                id="owner_name"
                name="owner_name"
                maxlength="100"
                placeholder="Masukkan nama owner"
                required
                value="<?= htmlspecialchars($_POST["owner_name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
            >


            <!-- EMAIL -->

            <label for="email">
                Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                placeholder="Masukkan email"
                autocomplete="email"
                required
                value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
            >


            <!-- PASSWORD -->

            <label for="password">
                Password
            </label>

            <div class="password-wrapper">

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    placeholder="Minimal 8 karakter"
                    autocomplete="new-password"
                    required
                >

                <button
                    type="button"
                    class="toggle-password"
                    data-target="password"
                    aria-label="Tampilkan password"
                    aria-pressed="false"
                >

                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>

                </button>

            </div>

            <small>
                Password harus terdiri dari minimal 8 karakter.
            </small>


            <!-- KONFIRMASI PASSWORD -->

            <label for="confirm_password">
                Konfirmasi Password
            </label>

            <div class="password-wrapper">

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    placeholder="Masukkan ulang password"
                    autocomplete="new-password"
                    required
                >

                <button
                    type="button"
                    class="toggle-password"
                    data-target="confirm_password"
                    aria-label="Tampilkan konfirmasi password"
                    aria-pressed="false"
                >

                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>

                </button>

            </div>


            <!-- BUTTON -->

            <button
                type="submit"
                class="btn primary full"
            >
                Buat Akun Owner
            </button>

        </form>

    </section>

    <script src="assets/js/admin.js"></script>

</body>

</html>
