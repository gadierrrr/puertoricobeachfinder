<?php
http_response_code(500);
$appName = $_ENV['APP_NAME'] ?? 'Beach Finder';
$errorId = $errorId ?? bin2hex(random_bytes(4));
$showDetails = $showDetails ?? false;
$errorMessage = $errorMessage ?? null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Error | <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #0f172a; color: #e2e8f0; }
        main { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .card { max-width: 680px; width: 100%; background: #111827; border: 1px solid #334155; border-radius: 16px; padding: 32px; }
        h1 { margin: 0 0 12px; font-size: 2rem; }
        p { margin: 0 0 16px; color: #94a3b8; }
        code { display: inline-block; background: #0b1220; border: 1px solid #334155; padding: 2px 8px; border-radius: 8px; color: #f8fafc; }
        pre { margin: 0; padding: 16px; border-radius: 12px; background: #0b1220; border: 1px solid #334155; color: #f8fafc; overflow: auto; }
        a { display: inline-block; margin-top: 20px; background: #facc15; color: #0f172a; text-decoration: none; padding: 12px 18px; border-radius: 10px; font-weight: 600; }
    </style>
</head>
<body>
<main>
    <section class="card">
        <h1>Something went wrong</h1>
        <p>We hit an unexpected error. If this continues, contact support and include error ID <code><?= htmlspecialchars($errorId, ENT_QUOTES, 'UTF-8') ?></code>.</p>
        <?php if ($showDetails && $errorMessage): ?>
            <pre><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></pre>
        <?php endif; ?>
        <a href="/">Back to home</a>
    </section>
</main>
</body>
</html>
