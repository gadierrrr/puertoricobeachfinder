<?php
// Legacy Plunk webhook endpoint — replaced by Resend.
http_response_code(410);
header("Content-Type: application/json");
echo json_encode(["success" => false, "error" => "Plunk webhooks discontinued. Use /api/webhooks/resend.php"]);
