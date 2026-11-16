<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set("session.use_strict_mode", "1");
    ini_set("session.use_only_cookies", "1");

    session_name("dauncincau_admin");

    $isHttps = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/dauncincaustarter/admin",
        "secure" => $isHttps,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
