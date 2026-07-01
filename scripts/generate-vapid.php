<?php
/**
 * Generate a VAPID keypair for web push. Print the values, then paste into .env:
 *   VAPID_PUBLIC_KEY=...   (also used as the client applicationServerKey)
 *   VAPID_PRIVATE_KEY=...
 *
 * Usage: php scripts/generate-vapid.php
 */

$b64url = static fn(string $bin): string => rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');

$res = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
if ($res === false) {
    fwrite(STDERR, "openssl EC key generation failed (is the openssl extension enabled?)\n");
    exit(1);
}
$d = openssl_pkey_get_details($res);
$x = str_pad($d['ec']['x'], 32, "\0", STR_PAD_LEFT);
$y = str_pad($d['ec']['y'], 32, "\0", STR_PAD_LEFT);
$priv = str_pad($d['ec']['d'], 32, "\0", STR_PAD_LEFT);

echo "VAPID_PUBLIC_KEY=" . $b64url("\x04" . $x . $y) . "\n";
echo "VAPID_PRIVATE_KEY=" . $b64url($priv) . "\n";
