<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
ensure_bootstrap_data();
require_login();
csrf_check();

$name = trim($_POST['name'] ?? '');
$uri  = trim($_POST['uri'] ?? '');

if ($name === '' || $uri === '') {
  header('Location: dashboard.php');
  exit;
}

$servers = load_json('servers.json', []);
$id = bin2hex(random_bytes(8));

$servers[] = [
  "id" => $id,
  "name" => $name,
  "uri" => $uri,
  "created_at" => now_iso()
];

save_json('servers.json', $servers);

$settings = load_json('settings.json', []);
$settings['updated_at'] = now_iso();
save_json('settings.json', $settings);

header('Location: dashboard.php');
exit;
