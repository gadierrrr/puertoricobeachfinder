<?php
/**
 * Google OAuth 2.0 Handler
 * Handles the OAuth flow for Google Sign-In
 */

/**
 * Get Google OAuth configuration
 */
function getGoogleOAuthConfig(): array {
    return [
        'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
        'redirect_uri' => ($_ENV['APP_URL'] ?? 'http://localhost:8082') . '/auth/google/callback.php',
        'auth_uri' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'userinfo_uri' => 'https://www.googleapis.com/oauth2/v2/userinfo',
        'scopes' => ['openid', 'email', 'profile']
    ];
}

/**
 * Check if Google OAuth is configured
 */
function isGoogleOAuthEnabled(): bool {
    $config = getGoogleOAuthConfig();
    return !empty($config['client_id']) && !empty($config['client_secret']);
}

/**
 * Generate the Google OAuth authorization URL
 */
function getGoogleAuthUrl(?string $redirectAfterLogin = null): string {
    $config = getGoogleOAuthConfig();

    // Generate state token for CSRF protection
    $state = bin2hex(random_bytes(16));
    $_SESSION['google_oauth_state'] = $state;

    // Store redirect URL in session
    if ($redirectAfterLogin) {
        if (function_exists('sanitizeInternalRedirect')) {
            $redirectAfterLogin = sanitizeInternalRedirect($redirectAfterLogin, '/');
        }
        $_SESSION['google_oauth_redirect'] = $redirectAfterLogin;
    }

    $params = [
        'client_id' => $config['client_id'],
        'redirect_uri' => $config['redirect_uri'],
        'response_type' => 'code',
        'scope' => implode(' ', $config['scopes']),
        'state' => $state,
        'access_type' => 'online',
        'prompt' => 'select_account' // Always show account selector
    ];

    return $config['auth_uri'] . '?' . http_build_query($params);
}

/**
 * Exchange authorization code for access token
 */
function exchangeCodeForToken(string $code): ?array {
    $config = getGoogleOAuthConfig();

    $postData = [
        'code' => $code,
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => $config['redirect_uri'],
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init($config['token_uri']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Google OAuth token exchange failed: HTTP $httpCode - $response");
        return null;
    }

    $data = json_decode($response, true);

    if (!isset($data['access_token'])) {
        error_log("Google OAuth token exchange failed: " . ($data['error_description'] ?? 'Unknown error'));
        return null;
    }

    return $data;
}

/**
 * Fetch user info from Google using access token
 */
function getGoogleUserInfo(string $accessToken): ?array {
    $config = getGoogleOAuthConfig();

    $ch = curl_init($config['userinfo_uri']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Google OAuth userinfo failed: HTTP $httpCode - $response");
        return null;
    }

    $data = json_decode($response, true);

    if (!isset($data['id']) || !isset($data['email'])) {
        error_log("Google OAuth userinfo incomplete: " . json_encode($data));
        return null;
    }

    return [
        'google_id' => $data['id'],
        'email' => $data['email'],
        'name' => $data['name'] ?? '',
        'avatar_url' => $data['picture'] ?? null,
        'verified_email' => $data['verified_email'] ?? false
    ];
}

/**
 * Find or create user from Google profile
 * Returns user array with '_is_new_user' flag if newly created
 */
function findOrCreateGoogleUser(array $googleUser): ?array {
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/email.php';

    $db = getDb();

    // First, try to find user by Google ID
    $user = queryOne(
        'SELECT * FROM users WHERE google_id = :google_id',
        [':google_id' => $googleUser['google_id']]
    );

    if ($user) {
        // Update user info from Google (name, avatar might have changed)
        $stmt = $db->prepare('
            UPDATE users
            SET name = :name, avatar_url = :avatar_url, updated_at = datetime("now")
            WHERE id = :id
        ');
        $stmt->bindValue(':name', $googleUser['name'], SQLITE3_TEXT);
        $stmt->bindValue(':avatar_url', $googleUser['avatar_url'], SQLITE3_TEXT);
        $stmt->bindValue(':id', $user['id'], SQLITE3_TEXT);
        $stmt->execute();

        $user = queryOne('SELECT * FROM users WHERE id = :id', [':id' => $user['id']]);
        $user['_is_new_user'] = false;
        return $user;
    }

    // Try to find user by email (link existing account)
    $user = queryOne(
        'SELECT * FROM users WHERE email = :email',
        [':email' => $googleUser['email']]
    );

    if ($user) {
        // Link Google account to existing user
        $stmt = $db->prepare('
            UPDATE users
            SET google_id = :google_id, name = COALESCE(name, :name),
                avatar_url = COALESCE(avatar_url, :avatar_url), updated_at = datetime("now")
            WHERE id = :id
        ');
        $stmt->bindValue(':google_id', $googleUser['google_id'], SQLITE3_TEXT);
        $stmt->bindValue(':name', $googleUser['name'], SQLITE3_TEXT);
        $stmt->bindValue(':avatar_url', $googleUser['avatar_url'], SQLITE3_TEXT);
        $stmt->bindValue(':id', $user['id'], SQLITE3_TEXT);
        $stmt->execute();

        $user = queryOne('SELECT * FROM users WHERE id = :id', [':id' => $user['id']]);
        $user['_is_new_user'] = false;
        return $user;
    }

    // Create new user
    $userId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    $stmt = $db->prepare('
        INSERT INTO users (id, email, name, google_id, avatar_url, created_at, updated_at)
        VALUES (:id, :email, :name, :google_id, :avatar_url, datetime("now"), datetime("now"))
    ');
    $stmt->bindValue(':id', $userId, SQLITE3_TEXT);
    $stmt->bindValue(':email', $googleUser['email'], SQLITE3_TEXT);
    $stmt->bindValue(':name', $googleUser['name'], SQLITE3_TEXT);
    $stmt->bindValue(':google_id', $googleUser['google_id'], SQLITE3_TEXT);
    $stmt->bindValue(':avatar_url', $googleUser['avatar_url'], SQLITE3_TEXT);

    if (!$stmt->execute()) {
        error_log("Failed to create Google user: " . $db->lastErrorMsg());
        return null;
    }

    $user = queryOne('SELECT * FROM users WHERE id = :id', [':id' => $userId]);

    // Send welcome email to new user
    if ($user) {
        sendWelcomeEmail($user['email'], $user['name']);
        $user['_is_new_user'] = true;
    }

    return $user;
}

/**
 * Login user and set session
 */
function loginUser(array $user): void {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['LAST_ACTIVITY'] = time();
    $_SESSION['SESSION_FINGERPRINT'] = hash('sha256',
        ($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? '')
    );
}
