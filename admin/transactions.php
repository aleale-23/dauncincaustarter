<?php
require_once __DIR__ . "/includes/auth.php";
requireOwner();
require_once __DIR__ . "/../config/database.php";

$pageTitle = "Riwayat Transaksi";
$stmt = $pdo->query("
    SELECT o.order_code, o.customer_name, o.total_amount,
           p.payment_method, p.status, p.verified_at, p.created_at
    FROM payments p
    INNER JOIN orders o ON o.id = p.order_id
    ORDER BY p.created_at DESC
");
$rows = $stmt->fetchAll();
require __DIR__ . "/includes/header.php";
?>
<section class="page-heading"><div><span class="eyebrow">PEMBAYARAN</span><h1>Riwayat Transaksi</h1><p class="muted">Daftar pembayaran yang tercatat di sistem.</p></div></section>
<section class="panel">
<?php if (!$rows): ?><div class="empty-state">Belum ada transaksi.</div><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Nomor Pesanan</th><th>Pelanggan</th><th>Metode</th><th>Nominal</th><th>Status</th><th>Diverifikasi</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
<td><?= htmlspecialchars($r["order_code"]) ?></td>
<td><?= htmlspecialchars($r["customer_name"]) ?></td>
<td><?= htmlspecialchars($r["payment_method"]) ?></td>
<td>Rp<?= number_format((float)$r["total_amount"],0,",",".") ?></td>
<td><span class="badge <?= strtolower($r["status"]) ?>"><?= htmlspecialchars($r["status"]) ?></span></td>
<td><?= htmlspecialchars($r["verified_at"] ?? "-") ?></td>
</tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section>
<?php require __DIR__ . "/includes/footer.php"; ?>
