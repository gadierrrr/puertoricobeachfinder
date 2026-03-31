<?php
// inc/email_provider_resend.php - Resend API client helpers

if (defined("EMAIL_PROVIDER_RESEND_INCLUDED")) {
    return;
}
define("EMAIL_PROVIDER_RESEND_INCLUDED", true);

require_once __DIR__ . "/env.php";

function resendApiKey(): string {
    return (string) env("RESEND_API_KEY", "");
}

function resendBaseUrl(): string {
    return "https://api.resend.com";
}

function resendRequest(string $method, string $path, ?array $payload = null): array {
    $method = strtoupper($method);
    $url = resendBaseUrl() . "/" . ltrim($path, "/");

    $key = resendApiKey();
    if ($key === "") {
        return [
            "ok" => false,
            "status_code" => 0,
            "body" => null,
            "error" => "Missing Resend API key",
        ];
    }

    $headers = [
        "Authorization: Bearer " . $key,
        "Content-Type: application/json",
        "Accept: application/json",
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return [
            "ok" => false,
            "status_code" => $status,
            "body" => null,
            "error" => $curlErr !== "" ? $curlErr : "Unknown cURL error",
        ];
    }

    $decoded = json_decode((string) $response, true);
    $body = is_array($decoded) ? $decoded : ["raw" => (string) $response];
    $ok = $status >= 200 && $status < 300;

    $errorMessage = null;
    if (!$ok) {
        $candidate = $body["error"] ?? $body["message"] ?? "Resend request failed";
        if (is_array($candidate)) {
            $errorMessage = json_encode($candidate, JSON_UNESCAPED_SLASHES);
        } else {
            $errorMessage = (string) $candidate;
        }
    }

    return [
        "ok" => $ok,
        "status_code" => $status,
        "body" => $body,
        "error" => $errorMessage,
    ];
}

function resendSendEmail(string $to, string $subject, string $html, array $options = []): array {
    $fromAddress = (string) ($options["from_address"] ?? "");
    $fromName = (string) ($options["from_name"] ?? (env("APP_NAME", "Beach Finder") ?? "Beach Finder"));

    if ($fromAddress === "") {
        $appUrl = (string) env("APP_URL", "https://puertoricobeachfinder.com");
        $domain = parse_url($appUrl, PHP_URL_HOST) ?: "puertoricobeachfinder.com";
        if (str_starts_with($domain, "www.")) {
            $domain = substr($domain, 4);
        }
        $fromAddress = "noreply@" . $domain;
    }

    $from = $fromName !== "" ? ($fromName . " <" . $fromAddress . ">") : $fromAddress;

    $payload = [
        "from" => $from,
        "to" => [$to],
        "subject" => $subject,
        "html" => $html,
    ];

    $res = resendRequest("POST", "/emails", $payload);

    $messageId = null;
    if (is_array($res["body"])) {
        $messageId = $res["body"]["id"] ?? null;
    }

    return [
        "ok" => (bool) $res["ok"],
        "provider" => "resend",
        "message_id" => $messageId,
        "status_code" => (int) $res["status_code"],
        "error_code" => (string) ($res["body"]["code"] ?? ""),
        "error_message" => $res["error"],
        "raw" => $res["body"],
    ];
}
