<?php
require_once __DIR__ . "/includes/auth.php";
requireOwner();
require_once __DIR__ . "/../config/database.php";

$pageTitle = "Dashboard";
$stats = [
    "total" => (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    "pending" => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'PENDING'")->fetchColumn(),
    "processing" => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'PROCESSING'")->fetchColumn(),
    "completed" => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'COMPLETED'")->fetchColumn()
];
$recent = $pdo->query("SELECT order_code, customer_name, total_amount, payment_status, order_status, created_at FROM orders ORDER BY created_at DESC LIMIT 8")->fetchAll();

require __DIR__ . "/includes/header.php";
?>
<section class="welcome-row">
    <div><span class="eyebrow">RINGKASAN TOKO</span><h1>Halo, <?= htmlspecialchars($_SESSION["owner_name"], ENT_QUOTES, "UTF-8") ?> 👋</h1><p class="muted">Berikut ringkasan aktivitas pemesanan Daun Cincau ID.</p></div>
    <a class="btn primary" href="orders.php">Lihat Pesanan</a>
</section>
<div class="stat-grid">
    <article class="stat-card"><span>Total Pesanan</span><strong><?= $stats["total"] ?></strong><small>Seluruh pesanan masuk</small></article>
    <article class="stat-card"><span>Menunggu Verifikasi</span><strong><?= $stats["pending"] ?></strong><small>Pembayaran berstatus pending</small></article>
    <article class="stat-card"><span>Sedang Diproses</span><strong><?= $stats["processing"] ?></strong><small>Pesanan dalam persiapan</small></article>
    <article class="stat-card"><span>Pesanan Selesai</span><strong><?= $stats["completed"] ?></strong><small>Pesanan telah selesai</small></article>
</div>
<section class="panel">
    <div class="panel-heading"><div><h2>Pesanan Terbaru</h2><p class="muted">Delapan pesanan terakhir yang tercatat.</p></div><a href="orders.php" class="text-link">Lihat semua →</a></div>
    <?php if (!$recent): ?>
        <div class="empty-state">Belum ada pesanan yang masuk.</div>
    <?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Nomor Pesanan</th><th>Pelanggan</th><th>Total</th><th>Pembayaran</th><th>Tanggal</th></tr></thead><tbody>
    <?php foreach ($recent as $row): ?>
        <tr>
            <td><strong><?= htmlspecialchars($row["order_code"], ENT_QUOTES, "UTF-8") ?></strong></td>
            <td><?= htmlspecialchars($row["customer_name"], ENT_QUOTES, "UTF-8") ?></td>
            <td>Rp<?= number_format((float)$row["total_amount"], 0, ",", ".") ?></td>
            <td><span class="badge <?= strtolower($row["payment_status"]) ?>"><?= htmlspecialchars($row["payment_status"]) ?></span></td>
            <td><?= htmlspecialchars($row["created_at"]) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . "/includes/footer.php"; ?>
