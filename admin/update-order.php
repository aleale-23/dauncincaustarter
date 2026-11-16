
<?php

/* =====================================
   UPDATE STATUS PESANAN
   DAUN CINCAU ID
===================================== */

require_once __DIR__ . "/includes/auth.php";
requireOwner();

require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/../config/database.php";

// =====================================
// HANYA MENERIMA REQUEST POST
// =====================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    exit("Metode request tidak diizinkan.");

}

// =====================================
// VALIDASI CSRF
// =====================================

verifyCsrfToken(
    $_POST["csrf_token"] ?? ""
);

// =====================================
// AMBIL DATA REQUEST
// =====================================

$orderId = filter_var(
    $_POST["order_id"] ?? null,
    FILTER_VALIDATE_INT
);

$newStatus = $_POST["order_status"] ?? "";

if (!$orderId || $orderId < 1) {

    http_response_code(422);

    exit("ID pesanan tidak valid.");

}

// =====================================
// DAFTAR TRANSISI YANG DIIZINKAN
// =====================================

$allowedTransitions = [

    "PROCESSING" => "READY",

    "READY" => "COMPLETED"

];

if (!in_array(
    $newStatus,
    array_values($allowedTransitions),
    true
)) {

    http_response_code(422);

    exit("Status pesanan tidak valid.");

}

// =====================================
// PROSES UPDATE DATABASE
// =====================================

try {

    $pdo->beginTransaction();

    // Ambil dan kunci data pesanan
    $stmt = $pdo->prepare("
        SELECT
            id,
            payment_status,
            order_status
        FROM orders
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->execute([$orderId]);

    $order = $stmt->fetch();

    if (!$order) {

        throw new RuntimeException(
            "Pesanan tidak ditemukan."
        );

    }

    // Pembayaran harus sudah lunas
    if ($order["payment_status"] !== "PAID") {

        throw new RuntimeException(
            "Pembayaran belum diverifikasi."
        );

    }

    $currentStatus = $order["order_status"];

    // Periksa apakah transisi diperbolehkan
    $expectedNextStatus =
        $allowedTransitions[$currentStatus] ?? null;

    if ($expectedNextStatus !== $newStatus) {

        throw new RuntimeException(
            "Perubahan status tidak sesuai urutan."
        );

    }

    // Update status pesanan
    $updateStmt = $pdo->prepare("
        UPDATE orders
        SET order_status = ?
        WHERE id = ?
        AND order_status = ?
        AND payment_status = 'PAID'
    ");

    $updateStmt->execute([
        $newStatus,
        $orderId,
        $currentStatus
    ]);

    if ($updateStmt->rowCount() !== 1) {

        throw new RuntimeException(
            "Status pesanan gagal diperbarui."
        );

    }

    $pdo->commit();

    // Kembali ke halaman detail
    header(
        "Location: verify-payment.php?id=" .
        $orderId .
        "&status_updated=1"
    );

    exit;

} catch (Throwable $exception) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($exception->getMessage());

    http_response_code(409);

    exit(
        "Perubahan status gagal. " .
        "Periksa status terbaru pesanan melalui CMS."
    );

}
