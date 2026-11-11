
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

if (!is_array($input)) {
    response(false, "Data pesanan tidak valid.", [], 400);
}


// KATALOG HARGA DI SERVER
// Harga tidak boleh hanya dipercaya dari browser.

$catalog = [
    "family-combo" => [
        "name" => "Family Combo",
        "price" => 55000
    ],

    "paket-original" => [
        "name" => "Paket Original",
        "price" => 38000
    ],

    "paket-berlima" => [
        "name" => "Paket Berlima",
        "price" => 45000
    ]
];

$name = trim($input["customer"]["name"] ?? "");

$phone = preg_replace(
    '/[\s\-]/',
    '',
    $input["customer"]["phone"] ?? ""
);

$address = trim($input["customer"]["address"] ?? "");

$note = trim($input["customer"]["note"] ?? "");

$items = $input["items"] ?? [];

if (
    $name === "" ||
    strlen($name) > 100 ||
    $address === "" ||
    strlen($address) > 2000 ||
    !preg_match('/^\+?[0-9]{9,15}$/', $phone)
) {
    response(false, "Data pelanggan tidak valid.", [], 422);
}

// Samakan format nomor WhatsApp.
if (substr($phone, 0, 1) === "0") {
    $phone = "62" . substr($phone, 1);
} elseif (substr($phone, 0, 1) === "+") {
    $phone = substr($phone, 1);
}

if (
    !is_array($items) ||
    count($items) < 1 ||
    count($items) > 20
) {
    response(false, "Daftar paket tidak valid.", [], 422);
}

$total = 0;
$validatedItems = [];

foreach ($items as $item) {

    $productId = $item["id"] ?? "";
    $quantity = filter_var(
        $item["quantity"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        !isset($catalog[$productId]) ||
        $quantity === false ||
        $quantity < 1 ||
        $quantity > 99
    ) {
        response(false, "Data paket tidak valid.", [], 422);
    }

    $product = $catalog[$productId];

    $subtotal = $product["price"] * $quantity;

    $total += $subtotal;

    $validatedItems[] = [
        "id" => $productId,
        "name" => $product["name"],
        "price" => $product["price"],
        "quantity" => $quantity,
        "subtotal" => $subtotal
    ];

}

try {

    $pdo->beginTransaction();

    // Nomor pesanan unik.
    $orderCode =
        "DC" .
        date("Ymd") .
        strtoupper(bin2hex(random_bytes(3)));

    // SIMPAN PESANAN
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            order_code,
            customer_name,
            customer_phone,
            customer_address,
            customer_note,
            total_amount,
            payment_method,
            payment_status,
            order_status
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            'QRIS_STATIC',
            'PENDING',
            'WAITING_PAYMENT'
        )
    ");

    $stmt->execute([
        $orderCode,
        $name,
        $phone,
        $address,
        $note,
        $total
    ]);

    $orderId = $pdo->lastInsertId();


    // SIMPAN RINCIAN PAKET
    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id,
            product_id,
            product_name,
            price,
            quantity,
            subtotal
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($validatedItems as $item) {

        $itemStmt->execute([
            $orderId,
            $item["id"],
            $item["name"],
            $item["price"],
            $item["quantity"],
            $item["subtotal"]
        ]);

    }


    // SIMPAN PEMBAYARAN
    $paymentStmt = $pdo->prepare("
        INSERT INTO payments (
            order_id,
            payment_method,
            amount,
            status
        ) VALUES (?, 'QRIS_STATIC', ?, 'PENDING')
    ");

    $paymentStmt->execute([
        $orderId,
        $total
    ]);

    $pdo->commit();

    response(true, "Pesanan berhasil dibuat.", [
        "order_code" => $orderCode,
        "total" => $total,
        "payment_status" => "PENDING",
        "order_status" => "WAITING_PAYMENT",
        "items" => $validatedItems
    ], 201);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    response(
        false,
        "Terjadi kesalahan saat menyimpan pesanan.",
        [],
        500
    );

}
