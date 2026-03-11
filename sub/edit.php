<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
ensure_bootstrap_data();
require_login();

$id = $_GET['id'] ?? '';
$servers = load_json('manual.json', []);
$server = null;
foreach ($servers as $s) {
  if (isset($s['id']) && $s['id'] === $id) { $server = $s; break; }
}
if (!$server) { header('Location: manual.php'); exit; }

$page_title = 'ویرایش سرور';
include __DIR__ . '/layout_header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="app-card p-3 p-lg-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <div class="fw-bold fs-5">ویرایش سرور</div>
          <div class="muted">اطلاعات را تغییر بده و ذخیره کن.</div>
        </div>
        <a class="btn btn-ghost" href="manual.php">بازگشت</a>
      </div>
      <form method="post" action="update.php">
        <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
        <input type="hidden" name="id" value="<?php echo h($server['id']); ?>">
        <div class="mb-3">
          <label class="form-label">نام نمایشی</label>
          <input class="form-control" name="name" value="<?php echo h($server['name'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">لینک کانفیگ</label>
          <textarea class="form-control mono" name="uri" rows="6" required><?php echo h($server['uri'] ?? ''); ?></textarea>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-accent fw-bold" type="submit">ذخیره</button>
          <a class="btn btn-ghost" href="manual.php">انصراف</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/layout_footer.php'; ?>
