<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
ensure_bootstrap_data();
require_login();
csrf_check();

$default_name = trim($_POST['default_name'] ?? '');
$uris_raw = (string)($_POST['uris'] ?? '');
$lines = preg_split('/\R/u', $uris_raw);

// Load MANUAL servers
$servers = load_json('manual.json', []);
$added = 0;

foreach ($lines as $ln) {
  $uri = trim($ln);
  if ($uri === '') continue;
  $id = bin2hex(random_bytes(8));
  // Use user provided name or default. If user provided name, maybe append index if multiple?
  // Logic here: if default_name is set, use it. If multiple, maybe we should differentiate?
  // Current logic: just uses same name. That's fine.
  $name = $default_name !== '' ? $default_name : 'Server ' . ($added + 1);

  $servers[] = [
    "id" => $id,
    "name" => $name,
    "uri" => $uri,
    "created_at" => now_iso()
  ];
  $added++;
}

save_json('manual.json', $servers);

// Note: We do NOT update settings['updated_at'] here because the OUTPUT hasn't changed yet.
// The user needs to click "Update" to merge manual+sources into servers.json.
// However, maybe we should implicitly update?
// Plan says: Aggregate button. So we don't update settings yet.

header('Location: manual.php');
exit;
