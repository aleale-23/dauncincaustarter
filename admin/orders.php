
<?php

/* =====================================
   KELOLA PESANAN - DAUN CINCAU ID
===================================== */

require_once __DIR__ . "/includes/auth.php";
requireOwner();

require_once __DIR__ . "/../config/database.php";

$pageTitle = "Kelola Pesanan";


// =====================================
// FILTER DAN PENCARIAN
// =====================================

$search = trim($_GET["search"] ?? "");

$paymentStatus = $_GET["payment_status"] ?? "";

$orderStatus = $_GET["order_status"] ?? "";

$allowedPaymentStatuses = [
    "PENDING",
    "PAID",
    "FAILED",
    "EXPIRED",
    "REFUNDED"
];

$allowedOrderStatuses = [
    "WAITING_PAYMENT",
    "PROCESSING",
    "READY",
    "COMPLETED",
    "CANCELLED"
];

if (!in_array(
    $paymentStatus,
    $allowedPaymentStatuses,
    true
)) {
    $paymentStatus = "";
}

if (!in_array(
    $orderStatus,
    $allowedOrderStatuses,
    true
)) {
    $orderStatus = "";
}


// =====================================
// PAGINATION
// =====================================

$page = max(1, (int)($_GET["page"] ?? 1));

$limit = 10;

$offset = ($page - 1) * $limit;


// =====================================
// SUSUN KONDISI PENCARIAN
// =====================================

$where = [];

$params = [];

if ($search !== "") {

    $where[] = "(
        o.order_code LIKE :search_code
        OR o.customer_name LIKE :search_name
    )";

    $params["search_code"] = "%" . $search . "%";
    $params["search_name"] = "%" . $search . "%";

}

if ($paymentStatus !== "") {

    $where[] = "o.payment_status = :payment_status";

    $params["payment_status"] = $paymentStatus;

}

if ($orderStatus !== "") {

    $where[] = "o.order_status = :order_status";

    $params["order_status"] = $orderStatus;

}

$whereSQL = "";

if (!empty($where)) {

    $whereSQL = "WHERE " . implode(" AND ", $where);

}


// =====================================
// HITUNG TOTAL DATA
// =====================================

$countSQL = "
    SELECT COUNT(*)
    FROM orders o
    $whereSQL
";

$countStmt = $pdo->prepare($countSQL);

$countStmt->execute($params);

$totalOrders = (int)$countStmt->fetchColumn();

$totalPages = max(
    1,
    (int)ceil($totalOrders / $limit)
);

$page = min($page, $totalPages);

$offset = ($page - 1) * $limit;


// =====================================
// AMBIL DATA PESANAN
// =====================================

$sql = "
    SELECT
        o.id,
        o.order_code,
        o.customer_name,
        o.customer_phone,
        o.total_amount,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.created_at,

        COALESCE(
            (
                SELECT SUM(oi.quantity)
                FROM order_items oi
                WHERE oi.order_id = o.id
            ),
            0
        ) AS total_items

    FROM orders o

    $whereSQL

    ORDER BY o.created_at DESC, o.id DESC

    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {

    $stmt->bindValue(
        ":" . $key,
        $value,
        PDO::PARAM_STR
    );

}

$stmt->bindValue(
    ":limit",
    $limit,
    PDO::PARAM_INT
);

$stmt->bindValue(
    ":offset",
    $offset,
    PDO::PARAM_INT
);

$stmt->execute();

$orders = $stmt->fetchAll();


// =====================================
// FUNGSI FORMAT
// =====================================

function rupiah($amount) {

    return "Rp" . number_format(
        (float)$amount,
        0,
        ",",
        "."
    );

}

function formatTanggal($date) {

    return date(
        "d M Y, H:i",
        strtotime($date)
    );

}

function paymentLabel($status) {

    $labels = [
        "PENDING" => "Menunggu",
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
        "PROCESSING" => "Diproses",
        "READY" => "Siap Diambil / Dikirim",
        "COMPLETED" => "Selesai",
        "CANCELLED" => "Dibatalkan"
    ];

    return $labels[$status] ?? $status;

}

function e($value) {

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );

}


// =====================================
// MEMPERTAHANKAN FILTER PAGINATION
// =====================================

function pageUrl($targetPage) {

    $query = $_GET;

    $query["page"] = $targetPage;

    return "orders.php?" . http_build_query($query);

}

require __DIR__ . "/includes/header.php";

?>


<!-- =====================================
     JUDUL HALAMAN
===================================== -->

<section class="page-heading">

    <div>

        <span class="eyebrow">
            MANAJEMEN PEMESANAN
        </span>

        <h1>Kelola Pesanan</h1>

        <p class="muted">
            Pantau seluruh pesanan pelanggan,
            periksa pembayaran QRIS,
            dan kelola status pesanan.
        </p>

    </div>

</section>


<!-- =====================================
     FILTER DAN PENCARIAN
===================================== -->

<section class="panel">

    <h2>Cari dan Filter Pesanan</h2>

    <p class="muted">
        Gunakan pencarian atau filter untuk
        menemukan pesanan pelanggan.
    </p>

    <form method="GET" class="order-filter-form">

        <!-- PENCARIAN -->

        <div class="filter-field">

            <label for="search">
                Cari Pesanan
            </label>

            <input
                type="text"
                id="search"
                name="search"
                placeholder="Nomor pesanan / nama pelanggan"
                value="<?= e($search) ?>"
            >

        </div>


        <!-- STATUS PEMBAYARAN -->

        <div class="filter-field">

            <label for="payment_status">
                Status Pembayaran
            </label>

            <select
                id="payment_status"
                name="payment_status"
            >

                <option value="">
                    Semua Pembayaran
                </option>

                <?php foreach ($allowedPaymentStatuses as $status): ?>

                    <option
                        value="<?= e($status) ?>"
                        <?= $paymentStatus === $status ? "selected" : "" ?>
                    >
                        <?= e(paymentLabel($status)) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- STATUS PESANAN -->

        <div class="filter-field">

            <label for="order_status">
                Status Pesanan
            </label>

            <select
                id="order_status"
                name="order_status"
            >

                <option value="">
                    Semua Status Pesanan
                </option>

                <?php foreach ($allowedOrderStatuses as $status): ?>

                    <option
                        value="<?= e($status) ?>"
                        <?= $orderStatus === $status ? "selected" : "" ?>
                    >
                        <?= e(orderLabel($status)) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- TOMBOL -->

        <div class="filter-actions">

            <button
                type="submit"
                class="btn primary"
            >
                Terapkan Filter
            </button>

            <a
                href="orders.php"
                class="btn secondary"
            >
                Reset
            </a>

        </div>

    </form>

</section>


<!-- =====================================
     TABEL PESANAN
===================================== -->

<section class="panel">

    <div class="panel-heading">

        <div>

            <h2>Daftar Pesanan</h2>

            <p class="muted">
                Total <?= $totalOrders ?> pesanan ditemukan.
            </p>

        </div>

    </div>


    <?php if (empty($orders)): ?>

        <div class="empty-state">

            <h3>Belum Ada Pesanan</h3>

            <p>
                Belum ditemukan pesanan yang sesuai
                dengan pencarian atau filter.
            </p>

        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table class="orders-table">

                <thead>

                    <tr>

                        <th>No.</th>

                        <th>Nomor Pesanan</th>

                        <th>Pelanggan</th>

                        <th>Tanggal</th>

                        <th>Jumlah Paket</th>

                        <th>Total</th>

                        <th>Pembayaran</th>

                        <th>Status Pesanan</th>

                        <th>Aksi</th>

                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($orders as $index => $order): ?>

                        <tr>

                            <!-- NOMOR -->

                            <td>
                                <?= $offset + $index + 1 ?>
                            </td>


                            <!-- KODE PESANAN -->

                            <td>

                                <strong class="order-code">
                                    <?= e($order["order_code"]) ?>
                                </strong>

                            </td>


                            <!-- DATA PELANGGAN -->

                            <td>

                                <div class="customer-info">

                                    <strong>
                                        <?= e($order["customer_name"]) ?>
                                    </strong>

                                    <span>
                                        <?= e($order["customer_phone"]) ?>
                                    </span>

                                </div>

                            </td>


                            <!-- TANGGAL -->

                            <td>
                                <?= e(formatTanggal($order["created_at"])) ?>
                            </td>


                            <!-- JUMLAH -->

                            <td>
                                <?= (int)$order["total_items"] ?> paket
                            </td>


                            <!-- TOTAL -->

                            <td>

                                <strong>
                                    <?= e(rupiah($order["total_amount"])) ?>
                                </strong>

                            </td>


                            <!-- STATUS PEMBAYARAN -->

                            <td>

                                <span class="badge <?= e(strtolower($order["payment_status"])) ?>">

                                    <?= e(paymentLabel($order["payment_status"])) ?>

                                </span>

                            </td>


                            <!-- STATUS PESANAN -->

                            <td>

                                <span class="order-status <?= e(strtolower($order["order_status"])) ?>">

                                    <?= e(orderLabel($order["order_status"])) ?>

                                </span>

                            </td>


                            <!-- AKSI -->

                            <td>

                                <a
                                    href="verify-payment.php?id=<?= (int)$order["id"] ?>"
                                    class="order-detail-btn"
                                >
                                    Lihat Detail →
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>


        <!-- =====================================
             PAGINATION
        ===================================== -->

        <?php if ($totalPages > 1): ?>

            <div class="orders-pagination">

                <?php if ($page > 1): ?>

                    <a href="<?= e(pageUrl($page - 1)) ?>">
                        ← Sebelumnya
                    </a>

                <?php endif; ?>


                <span>
                    Halaman <?= $page ?> dari <?= $totalPages ?>
                </span>


                <?php if ($page < $totalPages): ?>

                    <a href="<?= e(pageUrl($page + 1)) ?>">
                        Selanjutnya →
                    </a>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    <?php endif; ?>

</section>


<?php

require __DIR__ . "/includes/footer.php";

?>
