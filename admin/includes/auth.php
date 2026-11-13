<?php
require_once __DIR__ . "/bootstrap.php";

function requireOwner(): void {
    if (empty($_SESSION["admin_id"])) {
        header("Location: login.php");
        exit;
    }
}
