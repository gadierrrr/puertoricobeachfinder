<?php
/**
 * Centralized error/exception handling
 */

if (defined('ERROR_HANDLER_PHP_INCLUDED')) {
    return;
}
define('ERROR_HANDLER_PHP_INCLUDED', true);

function appLog(string $level, string $message, array $context = []): void {
    $logDir = __DIR__ . '/../data/logs';
    if (!is_dir($logDir) && !@mkdir($logDir, 0755, true) && !is_dir($logDir)) {
        error_log("[$level] $message " . json_encode($context));
        return;
    }

    $entry = [
        'timestamp' => date('c'),
        'level' => strtoupper($level),
        'message' => $message,
        'context' => $context,
    ];

    $line = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($line === false) {
        $line = json_encode([
            'timestamp' => date('c'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => ['json_encode_error' => json_last_error_msg()],
        ]);
    }

    @file_put_contents($logDir . '/app.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function isApiRequest(): bool {
    if (PHP_SAPI === 'cli') {
        return false;
    }

    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

    return str_starts_with($requestUri, '/api/') || str_contains($scriptName, '/api/');
}

function renderHttpError(int $statusCode, string $publicMessage, ?Throwable $exception = null): void {
    if (!headers_sent()) {
        http_response_code($statusCode);
    }

    $debugMode = function_exists('appDebug') ? appDebug() : false;
    if (!$debugMode && $exception !== null && str_contains($exception->getMessage(), 'Missing required environment variable: DB_PATH')) {
        $publicMessage = 'Configuration error: DB_PATH is missing';
    }

    if (isApiRequest()) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        $payload = [
            'success' => false,
            'error' => $publicMessage,
        ];

        if ($debugMode && $exception !== null) {
            $payload['debug'] = [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        echo json_encode($payload);
        return;
    }

    $errorId = bin2hex(random_bytes(4));
    $showDetails = $debugMode && $exception !== null;
    $errorMessage = $showDetails ? $exception->getMessage() : null;

    // Friendly error pages live under the public docroot.
    // This keeps production docroot locked to public/ while still allowing server-side includes.
    $errorTemplate = (defined('PUBLIC_ROOT') ? PUBLIC_ROOT : (__DIR__ . '/../public')) . '/errors/500.php';
    if (is_file($errorTemplate)) {
        include $errorTemplate;
        return;
    }

    echo '<!doctype html><html><head><meta charset="utf-8"><title>Server Error</title></head><body>';
    echo '<h1>Something went wrong</h1>';
    if ($showDetails && $errorMessage) {
        echo '<pre>' . htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') . '</pre>';
    }
    echo '</body></html>';
}

function renderCliError(Throwable $exception): void {
    $debugMode = function_exists('appDebug') ? appDebug() : false;

    $message = "[ERROR] " . $exception->getMessage() . PHP_EOL;
    if ($debugMode) {
        $message .= $exception->getFile() . ':' . $exception->getLine() . PHP_EOL;
        $message .= $exception->getTraceAsString() . PHP_EOL;
    }

    fwrite(STDERR, $message);
}

function registerErrorHandlers(): void {
    static $registered = false;

    if ($registered) {
        return;
    }

    $registered = true;

    set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    set_exception_handler(function (Throwable $exception): void {
        appLog('error', 'Unhandled exception', [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'request' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'uri' => $_SERVER['REQUEST_URI'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ],
        ]);

        if (PHP_SAPI === 'cli') {
            renderCliError($exception);
            exit(1);
        }

        renderHttpError(500, 'Internal server error', $exception);
        exit;
    });

    register_shutdown_function(function (): void {
        $fatal = error_get_last();
        if ($fatal === null) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array($fatal['type'], $fatalTypes, true)) {
            return;
        }

        $exception = new ErrorException(
            $fatal['message'],
            0,
            $fatal['type'],
            $fatal['file'],
            $fatal['line']
        );

        appLog('critical', 'Fatal shutdown error', [
            'message' => $fatal['message'],
            'file' => $fatal['file'],
            'line' => $fatal['line'],
            'type' => $fatal['type'],
            'request' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'uri' => $_SERVER['REQUEST_URI'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ],
        ]);

        if (PHP_SAPI === 'cli') {
            renderCliError($exception);
            exit(1);
        }

        renderHttpError(500, 'Internal server error', $exception);
    });
}
