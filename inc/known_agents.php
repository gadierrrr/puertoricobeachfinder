<?php
/**
 * Known Agents - Agent & LLM Analytics
 * Tracks bot/crawler/AI agent visits via the Known Agents REST API.
 * https://knownagents.com
 */

function track_visit_in_known_agents() {
    // Skip in CLI (init-db, migrate, cron jobs): there is no HTTP request to
    // track, and getallheaders() is unavailable outside web SAPIs.
    if (PHP_SAPI === 'cli' || !function_exists('curl_init')) {
        return;
    }

    $headers = getallheaders() ?: [];
    // Strip sensitive headers
    unset($headers['Cookie'], $headers['Authorization']);

    // Parse response headers into key-value pairs
    $responseHeaders = [];
    foreach (headers_list() as $header) {
        $parts = explode(':', $header, 2);
        if (count($parts) === 2) {
            $responseHeaders[trim($parts[0])] = trim($parts[1]);
        }
    }

    $curl = curl_init('https://api.knownagents.com/visits');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer be6371af-7040-43c0-9704-cb0f53754729',
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'request_path' => $_SERVER['REQUEST_URI'] ?? '/',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'request_headers' => $headers,
            'response_status_code' => http_response_code() ?: 200,
            'response_headers' => $responseHeaders,
        ], JSON_UNESCAPED_SLASHES),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOSIGNAL => true,
        CURLOPT_TIMEOUT => 5,
    ]);
    @curl_exec($curl);
    curl_close($curl);
}

// Register as shutdown function so it runs after the response is sent
register_shutdown_function('track_visit_in_known_agents');
