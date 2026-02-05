<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

http_response_code(404);
$appName = $_ENV['APP_NAME'] ?? 'Beach Finder';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Not Found | <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #0f172a; color: #e2e8f0; }
        main { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .card { max-width: 560px; width: 100%; background: #111827; border: 1px solid #334155; border-radius: 16px; padding: 32px; text-align: center; }
        h1 { margin: 0 0 12px; font-size: 2rem; }
        p { margin: 0 0 20px; color: #94a3b8; }
        a { display: inline-block; background: #facc15; color: #0f172a; text-decoration: none; padding: 12px 18px; border-radius: 10px; font-weight: 600; }
    </style>
</head>
<body>
<main>
    <section class="card">
        <h1>Page not found</h1>
        <p>The page you requested does not exist or moved.</p>
        <a href="/">Back to home</a>
    </section>
</main>
</body>
</html>
