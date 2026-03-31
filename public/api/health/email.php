<?php
/**
 * Email provider health probe.
 *
 * Returns JSON with configuration and Resend connectivity status
 * without sending an email.
 */

require_once $_SERVER["DOCUMENT_ROOT"] . "/../bootstrap.php";
require_once APP_ROOT . "/inc/helpers.php";
require_once APP_ROOT . "/inc/email_provider_resend.php";

header("Content-Type: application/json");

$provider = "resend";
$apiKey = resendApiKey();

$checks = [
    "provider" => $provider,
    "configured" => [
        "api_key" => $apiKey !== "",
    ],
    "api" => [
        "reachable" => false,
        "authenticated" => false,
        "status_code" => 0,
        "note" => "",
    ],
];

$healthy = true;

if ($apiKey === "") {
    $healthy = false;
    $checks["api"]["note"] = "Missing RESEND_API_KEY";
} else {
    // Probe Resend API with a lightweight GET /domains request
    $probe = resendRequest("GET", "/domains");

    $checks["api"]["status_code"] = (int) ($probe["status_code"] ?? 0);

    if ($checks["api"]["status_code"] > 0) {
        $checks["api"]["reachable"] = true;
    }

    if (($probe["ok"] ?? false) === true) {
        $checks["api"]["authenticated"] = true;
        $checks["api"]["note"] = "Resend API reachable/authenticated";
    } else {
        $status = (int) ($probe["status_code"] ?? 0);

        if ($status === 401 || $status === 403) {
            $healthy = false;
            $checks["api"]["note"] = "Resend authentication failed";
        } else {
            $healthy = false;
            $checks["api"]["note"] = (string) ($probe["error"] ?? "Resend connectivity probe failed");
        }
    }
}

http_response_code($healthy ? 200 : 503);
jsonResponse([
    "ok" => $healthy,
    "checks" => $checks,
    "timestamp" => gmdate("c"),
], $healthy ? 200 : 503);
