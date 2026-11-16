<?php
require_once __DIR__ . "/bootstrap.php";
require_once __DIR__ . "/csrf.php";
$pageTitle = $pageTitle ?? "CMS Daun Cincau ID";
$currentPage = basename($_SERVER["PHP_SELF"]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, "UTF-8") ?> | Daun Cincau ID</title>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="dashboard.php">
            <span class="brand-mark">🌿</span>
            <span><strong>DAUN CINCAU ID</strong><small>OWNER CMS</small></span>
        </a>
        <nav class="side-nav">
            <a class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">▦ <span>Dashboard</span></a>
            <a class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>" href="orders.php">▤ <span>Kelola Pesanan</span></a>
            <a class="<?= $currentPage === 'transactions.php' ? 'active' : '' ?>" href="transactions.php">▣ <span>Riwayat Transaksi</span></a>
            <a class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>" href="profile.php">◉ <span>Profil Owner</span></a>
        </nav>
        <div class="sidebar-bottom">
            <form method="post" action="logout.php" class="logout-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, "UTF-8") ?>"><button type="submit">↪ <span>Keluar</span></button></form>
        </div>
    </aside>
    <main class="main-area">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" type="button" aria-label="Buka menu">☰</button>
            <div><span class="topbar-label">CONTENT MANAGEMENT SYSTEM</span><strong><?= htmlspecialchars($pageTitle, ENT_QUOTES, "UTF-8") ?></strong></div>
            <div class="owner-chip">Owner: <?= htmlspecialchars($_SESSION["owner_name"] ?? "Owner", ENT_QUOTES, "UTF-8") ?></div>
        </header>
        <div class="content">
