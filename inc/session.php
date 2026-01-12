<?php
/**
 * Secure Session Configuration
 * MUST be included before any session_start() call
 */

// Secure session configuration
ini_set('session.cookie_httponly', '1');       // Prevent JavaScript access
ini_set('session.cookie_secure', '1');         // Require HTTPS for cookies
ini_set('session.cookie_samesite', 'Lax');     // CSRF protection
ini_set('session.use_strict_mode', '1');       // Reject uninitialized session IDs
ini_set('session.use_only_cookies', '1');      // No URL-based sessions
ini_set('session.name', 'BEACH_FINDER_SESSION'); // Custom session name
ini_set('session.gc_maxlifetime', '1800');     // 30 minutes server-side
ini_set('session.cookie_lifetime', '0');       // Expire on browser close

// Additional security
ini_set('session.sid_length', '48');           // Longer session IDs
ini_set('session.sid_bits_per_character', '6'); // More entropy per character
