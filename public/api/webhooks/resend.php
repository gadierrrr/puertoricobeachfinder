<?php
/**
 * Resend webhooks endpoint.
 *
 * Handles delivery lifecycle and suppression events from Resend.
 * Resend uses Svix for webhook delivery with svix-id, svix-timestamp, svix-signature headers.
 */

require_once $_SERVER["DOCUMENT_ROOT"] . "/../bootstrap.php";
require_once APP_ROOT . "/inc/db.php";
require_once APP_ROOT . "/inc/helpers.php";
require_once APP_ROOT . "/inc/email.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonResponse(["success" => false, "error" => "Method not allowed"], 405);
}

$rawBody = file_get_contents("php://input");
if (!is_string($rawBody) || $rawBody === "") {
    jsonResponse(["success" => false, "error" => "Empty body"], 400);
}

// Verify webhook signature if secret is configured
$secret = (string) env("RESEND_WEBHOOK_SECRET", "");
if ($secret !== "") {
    $svixId = (string) ($_SERVER["HTTP_SVIX_ID"] ?? "");
    $svixTimestamp = (string) ($_SERVER["HTTP_SVIX_TIMESTAMP"] ?? "");
    $svixSignature = (string) ($_SERVER["HTTP_SVIX_SIGNATURE"] ?? "");

    if ($svixId === "" || $svixTimestamp === "" || $svixSignature === "") {
        jsonResponse(["success" => false, "error" => "Missing signature headers"], 401);
    }

    $signedContent = $svixId . "." . $svixTimestamp . "." . $rawBody;

    // Resend webhook secrets are base64-encoded with a "whsec_" prefix
    $secretBytes = base64_decode(str_replace("whsec_", "", $secret));
    $expectedSignature = base64_encode(hash_hmac("sha256", $signedContent, $secretBytes, true));

    // svix-signature may contain multiple signatures separated by spaces
    $signatures = explode(" ", $svixSignature);
    $verified = false;
    foreach ($signatures as $sig) {
        $sigValue = str_starts_with($sig, "v1,") ? substr($sig, 3) : $sig;
        if (hash_equals($expectedSignature, $sigValue)) {
            $verified = true;
            break;
        }
    }

    if (!$verified) {
        jsonResponse(["success" => false, "error" => "Invalid signature"], 401);
    }

    // Check timestamp is within 5 minutes
    $now = time();
    $ts = (int) $svixTimestamp;
    if (abs($now - $ts) > 300) {
        jsonResponse(["success" => false, "error" => "Timestamp too old"], 401);
    }
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    jsonResponse(["success" => false, "error" => "Invalid JSON"], 400);
}

// Resend event format: { type: "email.delivered", data: { email_id: "...", ... } }
$eventType = (string) ($payload["type"] ?? "");
$data = is_array($payload["data"] ?? null) ? $payload["data"] : [];
$providerMessageId = (string) ($data["email_id"] ?? "");
$email = emailNormalizeAddress((string) ($data["to"][0] ?? $data["email"] ?? ""));
$occurredAt = (string) ($data["created_at"] ?? date("c"));

if ($eventType === "") {
    jsonResponse(["success" => false, "error" => "Missing event type"], 400);
}

// Look up local message by provider message ID
$localMessageId = null;
if ($providerMessageId !== "" && emailTrackingTablesAvailable()) {
    $row = queryOne("SELECT id FROM email_messages WHERE provider_message_id = :provider_message_id", [
        ":provider_message_id" => $providerMessageId,
    ]);
    if (is_array($row) && !empty($row["id"])) {
        $localMessageId = (string) $row["id"];
    }
}

emailRecordEvent($localMessageId, "webhook_" . $eventType, $payload, null, $occurredAt);

// Map Resend event types to our status
$statusMap = [
    "email.sent" => "sent",
    "email.delivered" => "delivered",
    "email.delivery_delayed" => "delayed",
    "email.opened" => "opened",
    "email.clicked" => "clicked",
    "email.bounced" => "bounced",
    "email.complained" => "complained",
];

if ($localMessageId !== null && isset($statusMap[$eventType])) {
    emailUpdateMessage($localMessageId, [
        "status" => $statusMap[$eventType],
    ]);
}

// Handle suppression events
if ($email !== "" && in_array($eventType, ["email.bounced", "email.complained"], true)) {
    $reason = $eventType === "email.complained" ? "resend_complaint" : "resend_bounce";
    emailUpsertContactState($email, [
        "unsubscribed" => true,
        "suppressed_reason" => $reason,
    ]);
}

jsonResponse(["success" => true]);
