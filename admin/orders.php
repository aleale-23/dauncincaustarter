<?php
require_once __DIR__ . "/includes/auth.php";
requireOwner();
require_once __DIR__ . "/../config/database.php";

$pageTitle = "Kelola Pesanan";
$status = $_GET["status"] ?? "";
$allowed = ["PENDING", "PAID", "FAILED", "EXPIRED", "REFUNDED"];
if (!in_array($status, $allowed, true)) $status = "";

if ($status) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE payment_status = ? ORDER BY created_at DESC");
    $stmt->execute([$status]);
    $orders = $stmt->fetchAll();
} else {
    $orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();
}

require __DIR__ . "/includes/header.php";
?>
<section class="page-heading"><div><span class="eyebrow">TRANSAKSI PELANGGAN</span><h1>Kelola Pesanan</h1><p class="muted">Periksa detail pesanan dan status pembayaran pelanggan.</p></div></section>
<div class="filter-row">
    <a class="<?= $status === '' ? 'selected' : '' ?>" href="orders.php">Semua</a>
    <?php foreach ($allowed as $s): ?><a class="<?= $status === $s ? 'selected' : '' ?>" href="?status=<?= urlencode($s) ?>"><?= htmlspecialchars($s) ?></a><?php endforeach; ?>
</div>
<section class="panel">
<?php if (!$orders): ?><div class="empty-state">Tidak ada pesanan pada filter ini.</div><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Nomor Pesanan</th><th>Pelanggan</th><th>WhatsApp</th><th>Total</th><th>Pembayaran</th><th>Status Pesanan</th><th>Tanggal</th><th>Detail</th></tr></thead><tbody>
<?php foreach ($orders as $o): ?>
<tr>
<td><strong><?= htmlspecialchars($o["order_code"]) ?></strong></td>
<td><?= htmlspecialchars($o["customer_name"]) ?></td>
<td><?= htmlspecialchars($o["customer_phone"]) ?></td>
<td>Rp<?= number_format((float)$o["total_amount"],0,",",".") ?></td>
<td><span class="badge <?= strtolower($o["payment_status"]) ?>"><?= htmlspecialchars($o["payment_status"]) ?></span></td>
<td><?= htmlspecialchars($o["order_status"]) ?></td>
<td><?= htmlspecialchars($o["created_at"]) ?></td>
<td><a class="text-link" href="verify-payment.php?id=<?= (int)$o["id"] ?>">Buka</a></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section>
<?php require __DIR__ . "/includes/footer.php"; ?>
