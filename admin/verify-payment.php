<?php
require_once __DIR__ . "/includes/auth.php";
requireOwner();
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/../config/database.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit("ID pesanan tidak valid."); }

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) { http_response_code(404); exit("Pesanan tidak ditemukan."); }

$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemStmt->execute([$id]);
$items = $itemStmt->fetchAll();

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verifyCsrfToken($_POST["csrf_token"] ?? "");
    $action = $_POST["action"] ?? "";

    if ($action === "verify" && $order["payment_status"] === "PENDING") {
        try {
            $pdo->beginTransaction();
            $up = $pdo->prepare("UPDATE orders SET payment_status='PAID', order_status='PROCESSING', verified_at=NOW() WHERE id=? AND payment_status='PENDING'");
            $up->execute([$id]);
            if ($up->rowCount() !== 1) throw new RuntimeException("Status pembayaran sudah berubah.");

            $pay = $pdo->prepare("UPDATE payments SET status='PAID', verified_at=NOW(), verification_note=? WHERE order_id=? AND status='PENDING'");
            $pay->execute(["Diverifikasi oleh owner melalui CMS.", $id]);
            if ($pay->rowCount() < 1) throw new RuntimeException("Data pembayaran pending tidak ditemukan.");

            $pdo->commit();
            header("Location: verify-payment.php?id=" . $id . "&updated=1");
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log($e->getMessage());
            $error = "Verifikasi gagal. Periksa kembali data pembayaran.";
        }
    } else {
        $error = "Tindakan tidak tersedia untuk status pembayaran ini.";
    }
    $stmt->execute([$id]);
    $order = $stmt->fetch();
}

$pageTitle = "Detail & Verifikasi Pesanan";
require __DIR__ . "/includes/header.php";
?>
<section class="page-heading"><div><span class="eyebrow">DETAIL TRANSAKSI</span><h1><?= htmlspecialchars($order["order_code"]) ?></h1><p class="muted">Periksa bukti transaksi pada aplikasi/riwayat QRIS sebelum melakukan verifikasi.</p></div><a class="btn secondary" href="orders.php">← Kembali</a></section>
<?php if (isset($_GET["updated"])): ?><div class="alert success">Pembayaran berhasil diverifikasi dan pesanan dipindahkan ke status diproses.</div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="detail-grid">
<section class="panel"><h2>Informasi Pelanggan</h2>
<dl class="details">
<dt>Nama</dt><dd><?= htmlspecialchars($order["customer_name"]) ?></dd>
<dt>WhatsApp</dt><dd><?= htmlspecialchars($order["customer_phone"]) ?></dd>
<dt>Alamat</dt><dd><?= nl2br(htmlspecialchars($order["customer_address"])) ?></dd>
<dt>Catatan</dt><dd><?= nl2br(htmlspecialchars($order["customer_note"] ?? "-")) ?></dd>
<dt>Tanggal</dt><dd><?= htmlspecialchars($order["created_at"]) ?></dd>
</dl></section>
<section class="panel"><h2>Informasi Pembayaran</h2>
<dl class="details">
<dt>Metode</dt><dd><?= htmlspecialchars($order["payment_method"]) ?></dd>
<dt>Total</dt><dd><strong>Rp<?= number_format((float)$order["total_amount"],0,",",".") ?></strong></dd>
<dt>Status pembayaran</dt><dd><span class="badge <?= strtolower($order["payment_status"]) ?>"><?= htmlspecialchars($order["payment_status"]) ?></span></dd>
<dt>Status pesanan</dt><dd><?= htmlspecialchars($order["order_status"]) ?></dd>
<dt>Waktu verifikasi</dt><dd><?= htmlspecialchars($order["verified_at"] ?? "-") ?></dd>
</dl>
<?php if ($order["payment_status"] === "PENDING"): ?>
<form method="post" onsubmit="return confirm('Pastikan transaksi QRIS sudah benar-benar diterima. Verifikasi pembayaran sekarang?')">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
<input type="hidden" name="action" value="verify">
<button class="btn primary full" type="submit">Verifikasi Pembayaran</button>
</form>
<?php else: ?><p class="muted">Tidak ada tindakan verifikasi yang tersedia untuk status ini.</p><?php endif; ?>
</section></div>
<section class="panel"><h2>Rincian Paket</h2><div class="table-wrap"><table><thead><tr><th>Paket</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th></tr></thead><tbody>
<?php foreach ($items as $item): ?><tr><td><?= htmlspecialchars($item["product_name"]) ?></td><td>Rp<?= number_format((float)$item["price"],0,",",".") ?></td><td><?= (int)$item["quantity"] ?></td><td>Rp<?= number_format((float)$item["subtotal"],0,",",".") ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php require __DIR__ . "/includes/footer.php"; ?>
