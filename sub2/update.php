<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
ensure_bootstrap_data();
require_login();
csrf_check();

$id   = $_POST['id'] ?? '';
$name = trim($_POST['name'] ?? '');
$uri  = trim($_POST['uri'] ?? '');

$servers = load_json('manual.json', []);
for ($i=0; $i<count($servers); $i++) {
  if (isset($servers[$i]['id']) && $servers[$i]['id'] === $id) {
    $servers[$i]['name'] = $name;
    $servers[$i]['uri']  = $uri;
    $servers[$i]['updated_at'] = now_iso();
    break;
  }
}
save_json('manual.json', $servers);

header('Location: manual.php');
exit;
