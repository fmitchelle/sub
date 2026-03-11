<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function is_logged_in(): bool {
  return isset($_SESSION['user']);
}

function require_login(): void {
  if (!is_logged_in()) {
    header('Location: login.php');
    exit;
  }
}

function csrf_token(): string {
  if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function csrf_check(): void {
  $t = $_POST['csrf'] ?? '';
  if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "CSRF check failed";
    exit;
  }
}
