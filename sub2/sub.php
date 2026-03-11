<?php
// sub.php - subscription endpoint (Base64)
require_once __DIR__ . '/includes/functions.php';
ensure_bootstrap_data();

$settings = load_json('settings.json', ["subscription_token" => ""]);
$token_required = trim($settings['subscription_token'] ?? '');

if ($token_required !== '') {
  $token = $_GET['token'] ?? '';
  if (!hash_equals($token_required, (string)$token)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "403 Forbidden";
    exit;
  }
}

// Track Hit
track_subscription_hit($_GET['user'] ?? null);

$servers = load_json('servers.json', []);
$lines = [];

// Check for User Info
if (isset($_GET['user'])) {
    $username = trim($_GET['user']);
    $clients = load_json('clients.json', []);
    $found_user = null;
    foreach($clients as $c) {
        if (strtolower($c['username']) === strtolower($username)) {
            $found_user = $c;
            break;
        }
    }
    
    if ($found_user) {
        // Create Info Node
        $limit_gb = $found_user['limit_gb'] > 0 ? $found_user['limit_gb'] . " GB" : "Unlimited";
        
        // Use a dummy UUID
        $uuid = "00000000-0000-0000-0000-000000000000";
        $host = $_SERVER['HTTP_HOST'] ?? 'example.com';
        $remark = "👤 " . $found_user['username'] . " | 📉 Limit: " . $limit_gb;
        $remark_encoded = rawurlencode($remark);
        
        // VLESS Info String
        $info_uri = "vless://{$uuid}@{$host}:443?encryption=none&security=none&type=tcp&headerType=none#{$remark_encoded}";
        $lines[] = $info_uri;
    }
}

foreach ($servers as $s) {
  $uri = trim($s['uri'] ?? '');
  if ($uri !== '') $lines[] = $uri;
}
$payload = implode("\n", $lines) . "\n";

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (isset($_GET['raw']) && $_GET['raw'] == '1') {
  echo $payload;
} else {
  echo base64_encode($payload);
}
exit;
