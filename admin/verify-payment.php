
<?php

/* =========================================
   DETAIL PESANAN & VERIFIKASI PEMBAYARAN
   DAUN CINCAU ID
========================================= */

require_once __DIR__ . "/includes/auth.php";
requireOwner();

require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/../config/database.php";

$pageTitle = "Detail Pesanan";

// =========================================
// VALIDASI ID PESANAN
// =========================================

$orderId = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$orderId || $orderId < 1) {
    http_response_code(400);
    exit("ID pesanan tidak valid.");
}

// =========================================
// FUNGSI HELPER
// =========================================

function e($value) {
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function rupiah($value) {
    return "Rp" . number_format(
        (float)$value,
        0,
        ",",
        "."
    );
}

function paymentLabel($status) {
    $labels = [
        "PENDING" => "Menunggu Verifikasi",
        "PAID" => "Lunas",
        "FAILED" => "Gagal",
        "EXPIRED" => "Kedaluwarsa",
        "REFUNDED" => "Refund"
    ];

    return $labels[$status] ?? $status;
}

function orderLabel($status) {
    $labels = [
        "WAITING_PAYMENT" => "Menunggu Pembayaran",
        "PROCESSING" => "Sedang Diproses",
        "READY" => "Siap Diambil / Dikirim",
        "COMPLETED" => "Selesai",
        "CANCELLED" => "Dibatalkan"
    ];

    return $labels[$status] ?? $status;
}

// =========================================
// PROSES VERIFIKASI PEMBAYARAN
// =========================================

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verifyCsrfToken(
        $_POST["csrf_token"] ?? ""
    );

    $action = $_POST["action"] ?? "";
    $note = trim(
        $_POST["verification_note"] ?? ""
    );

    if ($action !== "verify") {

        $error = "Tindakan tidak valid.";

    } elseif (mb_strlen($note) > 1000) {

        $error = "Catatan maksimal 1000 karakter.";

    } else {

        try {

            // Mulai transaksi database
            $pdo->beginTransaction();

            // Kunci pesanan selama verifikasi
            $checkStmt = $pdo->prepare("
                SELECT
                    id,
                    total_amount,
                    payment_status,
                    order_status
                FROM orders
                WHERE id = ?
                FOR UPDATE
            ");

            $checkStmt->execute([$orderId]);

            $currentOrder = $checkStmt->fetch();

            if (!$currentOrder) {
                throw new RuntimeException(
                    "Pesanan tidak ditemukan."
                );
            }

            if (
                $currentOrder["payment_status"] !== "PENDING" ||
                $currentOrder["order_status"] !== "WAITING_PAYMENT"
            ) {
                throw new RuntimeException(
                    "Pesanan tidak berada dalam status yang dapat diverifikasi."
                );
            }

            // Periksa pembayaran
            $paymentStmt = $pdo->prepare("
                SELECT
                    id,
                    status,
                    amount
                FROM payments
                WHERE order_id = ?
                AND status = 'PENDING'
                FOR UPDATE
            ");

            $paymentStmt->execute([$orderId]);

            $payment = $paymentStmt->fetch();

            if (!$payment) {
                throw new RuntimeException(
                    "Data pembayaran pending tidak ditemukan."
                );
            }

            if (
                (float)$payment["amount"] !==
                (float)$currentOrder["total_amount"]
            ) {
                throw new RuntimeException(
                    "Nominal pembayaran tidak sesuai total pesanan."
                );
            }

            // Perbarui tabel payments
            $updatePayment = $pdo->prepare("
                UPDATE payments
                SET
                    status = 'PAID',
                    verified_at = NOW(),
                    verification_note = ?
                WHERE id = ?
                AND status = 'PENDING'
            ");

            $updatePayment->execute([
                $note !== ""
                    ? $note
                    : "Pembayaran QRIS diverifikasi oleh Owner.",
                $payment["id"]
            ]);

            if ($updatePayment->rowCount() !== 1) {
                throw new RuntimeException(
                    "Gagal memperbarui pembayaran."
                );
            }

            // Perbarui tabel orders
            $updateOrder = $pdo->prepare("
                UPDATE orders
                SET
                    payment_status = 'PAID',
                    order_status = 'PROCESSING',
                    verified_at = NOW()
                WHERE id = ?
                AND payment_status = 'PENDING'
                AND order_status = 'WAITING_PAYMENT'
            ");

            $updateOrder->execute([$orderId]);

            if ($updateOrder->rowCount() !== 1) {
                throw new RuntimeException(
                    "Gagal memperbarui status pesanan."
                );
            }

            // Simpan kedua perubahan sekaligus
            $pdo->commit();

            header(
                "Location: verify-payment.php?id=" .
                $orderId .
                "&verified=1"
            );

            exit;

        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($exception->getMessage());

            $error = "Verifikasi gagal. Periksa kembali status pesanan dan data pembayaran.";

        }

    }
}

// =========================================
// AMBIL DATA PESANAN
// =========================================

$stmt = $pdo->prepare("
    SELECT
        id,
        order_code,
        customer_name,
        customer_phone,
        customer_address,
        customer_note,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        created_at,
        verified_at
    FROM orders
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$orderId]);

$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    exit("Pesanan tidak ditemukan.");
}

// =========================================
// AMBIL RINCIAN ITEM
// =========================================

$itemStmt = $pdo->prepare("
    SELECT
        product_name,
        price,
        quantity,
        subtotal
    FROM order_items
    WHERE order_id = ?
    ORDER BY id ASC
");

$itemStmt->execute([$orderId]);

$items = $itemStmt->fetchAll();

// =========================================
// AMBIL INFORMASI PEMBAYARAN
// =========================================

$paymentInfoStmt = $pdo->prepare("
    SELECT
        payment_method,
        amount,
        status,
        verified_at,
        verification_note
    FROM payments
    WHERE order_id = ?
    ORDER BY id DESC
    LIMIT 1
");

$paymentInfoStmt->execute([$orderId]);

$paymentInfo = $paymentInfoStmt->fetch();

// =========================================
// TENTUKAN STATUS SELANJUTNYA
// =========================================

$nextStatus = null;
$nextLabel = "";

if ($order["payment_status"] === "PAID") {

    switch ($order["order_status"]) {

        case "PROCESSING":
            $nextStatus = "READY";
            $nextLabel = "Tandai Siap Diambil / Dikirim";
            break;

        case "READY":
            $nextStatus = "COMPLETED";
            $nextLabel = "Tandai Pesanan Selesai";
            break;

    }

}

require __DIR__ . "/includes/header.php";

?>

<!-- =====================================
     HEADER DETAIL PESANAN
===================================== -->

<section class="page-heading">

    <div>

        <span class="eyebrow">
            MANAJEMEN PEMESANAN
        </span>

        <h1>Detail Pesanan</h1>

        <p class="muted">
            Nomor Pesanan:
            <strong><?= e($order["order_code"]) ?></strong>
        </p>

    </div>

    <a href="orders.php" class="btn secondary">
        ← Kembali ke Pesanan
    </a>

</section>

<?php if (isset($_GET["verified"])): ?>

    <div class="alert success">
        Pembayaran berhasil diverifikasi.
        Status pesanan sekarang sedang diproses.
    </div>

<?php endif; ?>

<?php if (isset($_GET["status_updated"])): ?>

    <div class="alert success">
        Status pesanan berhasil diperbarui.
    </div>

<?php endif; ?>

<?php if ($error): ?>

    <div class="alert error">
        <?= e($error) ?>
    </div>

<?php endif; ?>


<!-- =====================================
     RINGKASAN STATUS
===================================== -->

<div class="order-detail-summary">

    <div class="detail-summary-card">

        <span>Nomor Pesanan</span>

        <strong><?= e($order["order_code"]) ?></strong>

    </div>

    <div class="detail-summary-card">

        <span>Total Pembayaran</span>

        <strong>
            <?= rupiah($order["total_amount"]) ?>
        </strong>

    </div>

    <div class="detail-summary-card">

        <span>Status Pembayaran</span>

        <div>
            <span class="badge <?= e(strtolower($order["payment_status"])) ?>">
                <?= e(paymentLabel($order["payment_status"])) ?>
            </span>
        </div>

    </div>

    <div class="detail-summary-card">

        <span>Status Pesanan</span>

        <strong class="detail-order-status">
            <?= e(orderLabel($order["order_status"])) ?>
        </strong>

    </div>

</div>


<!-- =====================================
     DATA PELANGGAN DAN PEMBAYARAN
===================================== -->

<div class="detail-grid">

    <!-- INFORMASI PELANGGAN -->

    <section class="panel">

        <h2>Informasi Pelanggan</h2>

        <p class="muted">
            Detail pelanggan yang melakukan pemesanan.
        </p>

        <dl class="details">

            <dt>Nama Pelanggan</dt>
            <dd><?= e($order["customer_name"]) ?></dd>

            <dt>Nomor WhatsApp</dt>
            <dd>
                <?= e($order["customer_phone"]) ?>
            </dd>

            <dt>Alamat</dt>
            <dd>
                <?= nl2br(e($order["customer_address"])) ?>
            </dd>

            <dt>Catatan Pesanan</dt>
            <dd>
                <?= nl2br(e($order["customer_note"] ?: "-")) ?>
            </dd>

            <dt>Tanggal Pesanan</dt>
            <dd><?= e($order["created_at"]) ?></dd>

        </dl>

        <?php
            $phone = preg_replace(
                '/\D/',
                '',
                $order["customer_phone"]
            );

            $waMessage = rawurlencode(
                "Halo " . $order["customer_name"] .
                ", kami dari Daun Cincau ID terkait pesanan " .
                $order["order_code"] . "."
            );

            $waLink = "https://wa.me/" .
                $phone .
                "?text=" .
                $waMessage;
        ?>

        <a
            href="<?= e($waLink) ?>"
            target="_blank"
            rel="noopener noreferrer"
            class="btn secondary"
        >
            Hubungi Pelanggan ↗
        </a>

    </section>


    <!-- INFORMASI PEMBAYARAN -->

    <section class="panel">

        <h2>Informasi Pembayaran</h2>

        <p class="muted">
            Pembayaran menggunakan QRIS statis.
        </p>

        <dl class="details">

            <dt>Metode</dt>

            <dd>
                <?= e($order["payment_method"]) ?>
            </dd>

            <dt>Nominal</dt>

            <dd>
                <strong>
                    <?= rupiah($order["total_amount"]) ?>
                </strong>
            </dd>

            <dt>Status</dt>

            <dd>
                <span class="badge <?= e(strtolower($order["payment_status"])) ?>">
                    <?= e(paymentLabel($order["payment_status"])) ?>
                </span>
            </dd>

            <dt>Waktu Verifikasi</dt>

            <dd>
                <?= e($order["verified_at"] ?: "-") ?>
            </dd>

            <dt>Catatan Verifikasi</dt>

            <dd>
                <?= nl2br(e($paymentInfo["verification_note"] ?? "-")) ?>
            </dd>

        </dl>

    </section>

</div>


<!-- =====================================
     RINCIAN PAKET
===================================== -->

<section class="panel">

    <div class="panel-heading">

        <div>

            <h2>Rincian Paket</h2>

            <p class="muted">
                Daftar paket yang dibeli pelanggan.
            </p>

        </div>

    </div>

    <div class="table-wrap">

        <table>

            <thead>

                <tr>
                    <th>Nama Paket</th>
                    <th>Harga Satuan</th>
                    <th>Jumlah</th>
                    <th>Subtotal</th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($items as $item): ?>

                    <tr>

                        <td>
                            <strong>
                                <?= e($item["product_name"]) ?>
                            </strong>
                        </td>

                        <td>
                            <?= rupiah($item["price"]) ?>
                        </td>

                        <td>
                            <?= (int)$item["quantity"] ?>
                        </td>

                        <td>
                            <strong>
                                <?= rupiah($item["subtotal"]) ?>
                            </strong>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</section>


<!-- =====================================
     VERIFIKASI PEMBAYARAN
===================================== -->

<?php if (
    $order["payment_status"] === "PENDING" &&
    $order["order_status"] === "WAITING_PAYMENT"
): ?>

<section class="panel verification-panel">

    <div class="verification-heading">

        <div class="verification-icon">✓</div>

        <div>

            <h2>Verifikasi Pembayaran QRIS</h2>

            <p class="muted">
                Konfirmasi hanya setelah pembayaran
                benar-benar diterima.
            </p>

        </div>

    </div>

    <div class="verification-notice">

        <strong>Periksa sebelum konfirmasi:</strong>

        <p>
            Buka aplikasi atau dashboard merchant QRIS
            Daun Cincau ID. Cocokkan nominal transaksi,
            waktu pembayaran, dan pastikan transaksi
            sudah berhasil diterima.
        </p>

    </div>

    <form
        method="POST"
        id="verifyPaymentForm"
        onsubmit="return confirm('Apakah pembayaran QRIS sudah benar-benar diterima dan diverifikasi?')"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrfToken()) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="verify"
        >

        <label for="verification_note">
            Catatan Verifikasi
        </label>

        <textarea
            id="verification_note"
            name="verification_note"
            rows="3"
            maxlength="1000"
            placeholder="Contoh: Pembayaran diterima melalui QRIS merchant."
        ></textarea>

        <button
            type="submit"
            class="btn primary verify-btn"
        >
            ✓ Konfirmasi Pembayaran
        </button>

    </form>

</section>

<?php endif; ?>


<!-- =====================================
     UPDATE STATUS PESANAN
===================================== -->

<?php if ($order["payment_status"] === "PAID"): ?>

<section class="panel order-process-panel">

    <h2>Pengelolaan Status Pesanan</h2>

    <p class="muted">
        Perbarui status sesuai perkembangan
        persiapan pesanan pelanggan.
    </p>

    <div class="current-order-status">

        <span>Status Saat Ini</span>

        <strong>
            <?= e(orderLabel($order["order_status"])) ?>
        </strong>

    </div>

    <?php if ($nextStatus): ?>

        <form
            method="POST"
            action="update-order.php"
            onsubmit="return confirm('Yakin ingin memperbarui status pesanan?')"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >

            <input
                type="hidden"
                name="order_id"
                value="<?= (int)$orderId ?>"
            >

            <input
                type="hidden"
                name="order_status"
                value="<?= e($nextStatus) ?>"
            >

            <button
                type="submit"
                class="btn primary status-update-btn"
            >
                <?= e($nextLabel) ?> →
            </button>

        </form>

    <?php elseif ($order["order_status"] === "COMPLETED"): ?>

        <div class="order-completed-info">
            ✓ Pesanan ini sudah selesai.
        </div>

    <?php else: ?>

        <p class="muted">
            Tidak ada perubahan status yang tersedia.
        </p>

    <?php endif; ?>

</section>

<?php endif; ?>


<?php
require __DIR__ . "/includes/footer.php";
?>
