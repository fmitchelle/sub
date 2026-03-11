<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
ensure_bootstrap_data();

if (is_logged_in()) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = (string)($_POST['password'] ?? '');

  $users = load_json('users.json', []);
  $found = null;
  foreach ($users as $u) {
    if (isset($u['username']) && hash_equals($u['username'], $username)) { $found = $u; break; }
  }

  if ($found && isset($found['password_hash']) && password_verify($password, $found['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user'] = $username;
    header('Location: dashboard.php');
    exit;
  } else {
    $error = 'نام کاربری یا رمز عبور اشتباه است.';
  }
}

$page_title = 'ورود';
include __DIR__ . '/layout_header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-7 col-lg-5">
    <div class="app-card p-4 p-lg-5">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
          <div class="fw-bold fs-5">ورود به پنل</div>
          <div class="muted">مدیریت سرورها و خروجی سابسکریپشن</div>
        </div>
        <span class="badge-soft">v1</span>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo h($error); ?></div>
      <?php endif; ?>

      <form method="post" class="mt-3">
        <div class="mb-3">
          <label class="form-label">نام کاربری</label>
          <input class="form-control" name="username" autocomplete="username" required>
        </div>
        <div class="mb-3">
          <label class="form-label">رمز عبور</label>
          <input class="form-control" type="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-accent w-100 py-2 fw-bold" type="submit">ورود</button>
      </form>

      <div class="muted mt-3">
        پیش‌فرض: <span class="kbd mono">hosein</span> / <span class="kbd mono">hosein</span>
        <div class="small">بعد از ورود، از «تنظیمات» رمز را تغییر بده.</div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/layout_footer.php'; ?>
