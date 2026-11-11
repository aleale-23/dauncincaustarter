
<?php

header("Content-Type: application/json");

require_once "../config/database.php";

function response($success, $message, $data = [], $code = 200) {

    http_response_code($code);

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    response(false, "Metode tidak diizinkan.", [], 405);
}

$input = json_decode(file_get_contents("php://input"), true);

$orderCode = strtoupper(
    trim($input["order_code"] ?? "")
);

$phone = preg_replace(
    '/[\s\-]/',
    '',
    $input["phone"] ?? ""
);

if (substr($phone, 0, 1) === "0") {
    $phone = "62" . substr($phone, 1);
} elseif (substr($phone, 0, 1) === "+") {
    $phone = substr($phone, 1);
}

if (
    !preg_match('/^DC[0-9]{8}[A-F0-9]{6}$/', $orderCode) ||
    !preg_match('/^62[0-9]{8,13}$/', $phone)
) {
    response(false, "Format nomor pesanan atau WhatsApp tidak valid.", [], 422);
}

try {

    // CARI PESANAN
    $stmt = $pdo->prepare("
        SELECT
            id,
            order_code,
            customer_name,
            customer_phone,
            total_amount,
            payment_method,
            payment_status,
            order_status,
            created_at,
            verified_at
        FROM orders
        WHERE order_code = ?
        AND customer_phone = ?
        LIMIT 1
    ");

    $stmt->execute([
        $orderCode,
        $phone
    ]);

    $order = $stmt->fetch();

    if (!$order) {
        response(
            false,
            "Pesanan tidak ditemukan. Periksa kembali nomor pesanan dan WhatsApp.",
            [],
            404
        );
    }

    // AMBIL RINCIAN PESANAN
    $itemStmt = $pdo->prepare("
        SELECT
            product_name,
            price,
            quantity,
            subtotal
        FROM order_items
        WHERE order_id = ?
    ");

    $itemStmt->execute([$order["id"]]);

    $order["items"] = $itemStmt->fetchAll();

    unset($order["id"]);
    unset($order["customer_phone"]);

    response(true, "Pesanan berhasil ditemukan.", $order);

} catch (Throwable $e) {

    error_log($e->getMessage());

    response(false, "Gagal mengambil data pesanan.", [], 500);

}
