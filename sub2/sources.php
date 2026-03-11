<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
ensure_bootstrap_data();
require_login();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $url = trim($_POST['url'] ?? '');
        if ($name && $url) {
            $sources = load_json('sources.json', []);
            $sources[] = [
                'id' => bin2hex(random_bytes(8)),
                'name' => $name,
                'url' => $url,
                'enabled' => true,
                'created_at' => now_iso()
            ];
            save_json('sources.json', $sources);
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        $sources = load_json('sources.json', []);
        $sources = array_values(array_filter($sources, fn($s) => $s['id'] !== $id));
        save_json('sources.json', $sources);
    } elseif ($action === 'toggle') {
        $id = $_POST['id'] ?? '';
        $sources = load_json('sources.json', []);
        foreach ($sources as &$s) {
            if ($s['id'] === $id) {
                $s['enabled'] = !($s['enabled'] ?? true);
                break;
            }
        }
        save_json('sources.json', $sources);
    }
    
    header('Location: sources.php');
    exit;
}

$sources = load_json('sources.json', []);
$page_title = 'منابع لینک';
include __DIR__ . '/layout_header.php';
?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="app-card p-3 p-lg-4">
      <div class="fw-bold mb-2">افزودن منبع</div>
      <div class="muted mb-3">لینک Subscription اکسترنال وارد کن.</div>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
        <input type="hidden" name="action" value="add">
        <div class="mb-2">
          <label class="form-label">نام منبع</label>
          <input class="form-control" name="name" placeholder="مثلاً: Free Sub 1" required>
        </div>
        <div class="mb-3">
          <label class="form-label">لینک URL</label>
          <input class="form-control ltr" name="url" placeholder="https://..." required>
        </div>
        <button class="btn btn-accent w-100 fw-bold" type="submit">افزودن</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="app-card p-3 p-lg-4">
      <div class="fw-bold mb-3">لیست منابع</div>
      <div class="table-responsive">
        <table class="table table-dark table-sm align-middle mb-0">
          <thead>
            <tr>
              <th>نام</th>
              <th>وضعیت</th>
              <th>لینک</th>
              <th class="text-end">عملیات</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($sources)): ?>
              <tr><td colspan="4" class="muted text-center py-4">هیچ منبعی اضافه نشده.</td></tr>
            <?php else: ?>
              <?php foreach ($sources as $s): ?>
                <tr>
                  <td class="fw-semibold"><?php echo h($s['name']); ?></td>
                  <td>
                    <?php if($s['enabled']): ?>
                      <span class="badge bg-success">فعال</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">غیرفعال</span>
                    <?php endif; ?>
                  </td>
                  <td class="small mono text-truncate" style="max-width: 150px;" title="<?php echo h($s['url']); ?>">
                    <?php echo h($s['url']); ?>
                  </td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1">
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo h($s['id']); ?>">
                            <button class="btn btn-ghost btn-sm" title="تغییر وضعیت">
                                <?php echo $s['enabled'] ? 'Stop' : 'Start'; ?>
                            </button>
                        </form>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo h($s['id']); ?>">
                            <button class="btn btn-outline-danger btn-sm" onclick="return confirm('حذف شود؟')">حذف</button>
                        </form>
                    </div>
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
