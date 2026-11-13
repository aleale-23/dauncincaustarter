<?php
require_once __DIR__ . "/bootstrap.php";

function csrfToken(): string {
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function verifyCsrfToken($token): void {
    if (
        !is_string($token) ||
        empty($_SESSION["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Permintaan tidak valid. Muat ulang halaman dan coba lagi.");
    }
}
