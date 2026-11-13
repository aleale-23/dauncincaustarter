<?php
require_once __DIR__ . "/includes/auth.php";
requireOwner();
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Metode tidak diizinkan.");
}
verifyCsrfToken($_POST["csrf_token"] ?? "");

$id = filter_var($_POST["order_id"] ?? null, FILTER_VALIDATE_INT);
$status = $_POST["order_status"] ?? "";

$allowed = ["PROCESSING", "READY", "COMPLETED", "CANCELLED"];
if (!$id || !in_array($status, $allowed, true)) {
    http_response_code(422);
    exit("Data perubahan status tidak valid.");
}

$stmt = $pdo->prepare("SELECT payment_status FROM orders WHERE id=?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    exit("Pesanan tidak ditemukan.");
}
if ($order["payment_status"] !== "PAID" && $status !== "CANCELLED") {
    http_response_code(403);
    exit("Pesanan hanya dapat diproses setelah pembayaran diverifikasi.");
}

$update = $pdo->prepare("UPDATE orders SET order_status=? WHERE id=?");
$update->execute([$status, $id]);

header("Location: verify-payment.php?id=" . $id . "&status_updated=1");
exit;
