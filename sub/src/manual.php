<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
ensure_bootstrap_data();
require_login();

$servers = load_json('manual.json', []);
$total = count($servers);
$page_title = 'مدیریت دستی';

include __DIR__ . '/layout_header.php';
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="app-card p-3 p-lg-4 h-100">
      <div class="fw-bold mb-2">افزودن دستی</div>
      <div class="muted mb-3">لینک‌های سرور را اینجا وارد کن.</div>
      <form method="post" action="bulk_save.php">
        <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
        <div class="mb-2">
          <label class="form-label">نام (اختیاری)</label>
          <input class="form-control" name="default_name" placeholder="پیش‌فرض">
        </div>
        <div class="mb-3">
          <label class="form-label">لینک‌ها</label>
          <textarea class="form-control mono" name="uris" rows="6" placeholder="vless://..." required></textarea>
        </div>
        <button class="btn btn-accent w-100 fw-bold" type="submit">ثبت سرورها</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="app-card p-3 p-lg-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="fw-bold">لیست سرورهای دستی</div>
        <span class="badge-soft"><?php echo $total; ?> مورد</span>
      </div>
      
      <div class="table-responsive">
        <table class="table table-dark table-sm align-middle mb-0">
          <thead>
            <tr>
              <th>نام</th>
              <th>لینک</th>
              <th class="text-end">عملیات</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$total): ?>
              <tr><td colspan="3" class="muted text-center py-4">هنوز سروری ثبت نکرده‌ای.</td></tr>
            <?php else: ?>
              <?php foreach ($servers as $s): ?>
                <tr>
                  <td class="fw-semibold"><?php echo h($s['name'] ?? '-'); ?></td>
                  <td class="mono" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?php echo h($s['uri'] ?? ''); ?>
                  </td>
                  <td class="text-end">
                    <a class="btn btn-ghost btn-sm" href="edit.php?id=<?php echo urlencode($s['id']); ?>">ویرایش</a>
                    <a class="btn btn-outline-danger btn-sm" href="delete.php?id=<?php echo urlencode($s['id']); ?>" onclick="return confirm('حذف شود؟')">حذف</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/layout_footer.php'; ?>
