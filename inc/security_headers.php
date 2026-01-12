<?php
/**
 * Security Headers
 * Include at the top of all public-facing pages
 */

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy - allows MapLibre, Tailwind CDN, HTMX, Tom Select
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.tailwindcss.com unpkg.com cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdn.tailwindcss.com unpkg.com cdn.jsdelivr.net; img-src 'self' data: blob: https://*.cartocdn.com https://*.basemaps.cartocdn.com; font-src 'self' data:; connect-src 'self' https://*.cartocdn.com https://*.basemaps.cartocdn.com unpkg.com cdn.jsdelivr.net; worker-src 'self' blob:");
