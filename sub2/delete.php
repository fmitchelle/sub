<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
ensure_bootstrap_data();
require_login();

$id = $_GET['id'] ?? '';
$servers = load_json('manual.json', []);
$servers = array_values(array_filter($servers, function($s) use ($id) {
  return !isset($s['id']) || $s['id'] !== $id;
}));
save_json('manual.json', $servers);

header('Location: manual.php');
exit;
