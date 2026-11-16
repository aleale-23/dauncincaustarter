

<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/security.php";

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

if (!is_array($input)) {
    response(false, "Data pencarian tidak valid.", [], 400);
}

$orderCode = strtoupper(
    trim($input["order_code"] ?? "")
);

$phone = preg_replace(
    '/[\s\-]/',
    '',
    $input["phone"] ?? ""
);

$token = trim($input["tracking_token"] ?? "");


// =====================================
// VALIDASI INPUT
// =====================================

if (substr($phone, 0, 1) === "0") {
    $phone = "62" . substr($phone, 1);
} elseif (substr($phone, 0, 1) === "+") {
    $phone = substr($phone, 1);
}

if (
    !preg_match('/^DC[0-9]{8}[A-F0-9]{6}$/', $orderCode) ||
    !preg_match('/^62[0-9]{8,13}$/', $phone) ||
    !preg_match('/^[a-fA-F0-9]{64}$/', $token)
) {
    response(
        false,
        "Format data pencarian tidak valid.",
        [],
        422
    );
}


// =====================================
// RATE LIMITING
// Maksimal 5 percobaan per menit.
// Blokir 5 menit setelah batas terlampaui.
// =====================================

$ipAddress = $_SERVER["REMOTE_ADDR"] ?? "unknown";

$ipHash = hash_hmac(
    "sha256",
    $ipAddress,
    RATE_LIMIT_SECRET
);

try {

    $pdo->beginTransaction();

    $insertLimit = $pdo->prepare("
        INSERT IGNORE INTO rate_limits (
            ip_hash,
            attempts,
            window_started_at
        ) VALUES (?, 0, NOW())
    ");

    $insertLimit->execute([$ipHash]);


    $limitStmt = $pdo->prepare("
        SELECT *
        FROM rate_limits
        WHERE ip_hash = ?
        FOR UPDATE
    ");

    $limitStmt->execute([$ipHash]);

    $limit = $limitStmt->fetch();

    $now = time();

    // Masih dalam masa blokir
    if (
        !empty($limit["blocked_until"]) &&
        strtotime($limit["blocked_until"]) > $now
    ) {

        $pdo->commit();

        response(
            false,
            "Terlalu banyak percobaan. Silakan coba kembali setelah beberapa menit.",
            [],
            429
        );

    }


    $windowStart = strtotime(
        $limit["window_started_at"]
    );

    $attempts = (int) $limit["attempts"];

    // Reset jika sudah lewat satu menit
    if (($now - $windowStart) >= 60) {

        $attempts = 0;

        $resetWindow = true;

    } else {

        $resetWindow = false;

    }

    $attempts++;


    // Percobaan keenam akan diblokir
    if ($attempts > 5) {

        $blockedUntil = date(
            "Y-m-d H:i:s",
            $now + 300
        );

        $updateLimit = $pdo->prepare("
            UPDATE rate_limits
            SET attempts = ?,
                blocked_until = ?
            WHERE ip_hash = ?
        ");

        $updateLimit->execute([
            $attempts,
            $blockedUntil,
            $ipHash
        ]);

        $pdo->commit();

        response(
            false,
            "Batas pencarian tercapai. Coba kembali dalam 5 menit.",
            [],
            429
        );

    }


    $updateLimit = $pdo->prepare("
        UPDATE rate_limits
        SET attempts = ?,
            window_started_at = ?,
            blocked_until = NULL
        WHERE ip_hash = ?
    ");

    $updateLimit->execute([
        $attempts,
        $resetWindow
            ? date("Y-m-d H:i:s", $now)
            : $limit["window_started_at"],
        $ipHash
    ]);

    $pdo->commit();


    // =====================================
    // CARI PESANAN
    // =====================================

    $tokenHash = hash("sha256", $token);

    $stmt = $pdo->prepare("
        SELECT
            id,
            order_code,
            customer_name,
            total_amount,
            payment_method,
            payment_status,
            order_status,
            created_at,
            verified_at
        FROM orders
        WHERE order_code = ?
        AND customer_phone = ?
        AND tracking_token_hash = ?
        LIMIT 1
    ");

    $stmt->execute([
        $orderCode,
        $phone,
        $tokenHash
    ]);

    $order = $stmt->fetch();


    if (!$order) {

        response(
            false,
            "Pesanan tidak ditemukan atau informasi pelacakan tidak sesuai.",
            [],
            404
        );

    }


    // =====================================
    // AMBIL DETAIL ITEM
    // =====================================

    $itemStmt = $pdo->prepare("
        SELECT
            product_name,
            price,
            quantity,
            subtotal
        FROM order_items
        WHERE order_id = ?
    ");

    $itemStmt->execute([
        $order["id"]
    ]);

    $order["items"] = $itemStmt->fetchAll();

    unset($order["id"]);

    response(
        true,
        "Pesanan berhasil ditemukan.",
        $order
    );


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    response(
        false,
        "Terjadi kesalahan saat mencari pesanan.",
        [],
        500
    );

}
