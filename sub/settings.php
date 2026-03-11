<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
ensure_bootstrap_data();
require_login();

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title = trim($_POST['title'] ?? '');
    $token = trim($_POST['token'] ?? '');
    $new_pass = $_POST['new_pass'] ?? '';
    
    $settings = load_json('settings.json', []);
    $settings['title'] = $title;
    $settings['subscription_token'] = $token;
    save_json('settings.json', $settings);
    
    if (!empty($new_pass)) {
        $users = load_json('users.json', []);
        $users[0]['password_hash'] = password_hash($new_pass, PASSWORD_DEFAULT);
        save_json('users.json', $users);
    }
    
    $success = "تنظیمات ذخیره شد.";
}

$settings = load_json('settings.json', ["subscription_token" => "", "title" => "Subscription Panel"]);
$page_title = 'تنظیمات';

include __DIR__ . '/layout_header.php';
?>
<div class="row g-3 g-lg-4">
  <div class="col-lg-3 d-none d-lg-block">
    <?php include __DIR__ . '/sidebar.php'; ?>
  </div>
  
  <div class="col-lg-9">
     <?php if(isset($success)): ?>
        <div class="alert alert-success mb-3"><?php echo h($success); ?></div>
     <?php endif; ?>
     
     <div class="app-card p-3 p-lg-4">
        <div class="fw-bold fs-5 mb-3">تنظیمات پنل</div>
        
        <form method="post">
           <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
           
           <div class="mb-3">
              <label class="form-label">عنوان پنل</label>
              <input class="form-control" name="title" value="<?php echo h($settings['title']); ?>">
           </div>
           
           <div class="mb-3">
              <label class="form-label">Token امنیتی (اختیاری)</label>
              <div class="input-group">
                  <input class="form-control mono" name="token" value="<?php echo h($settings['subscription_token']); ?>">
                  <button class="btn btn-ghost" type="button" onclick="document.getElementsByName('token')[0].value='<?php echo bin2hex(random_bytes(8)); ?>'">تولید</button>
              </div>
              <div class="muted small mt-1">اگر تنظیم شود، لینک اشتراک فقط با این توکن کار می‌کند.</div>
           </div>
           
           <hr class="border-secondary border-opacity-10 my-4">
           
           <div class="mb-3">
              <label class="form-label">تغییر رمز عبور ادمین</label>
              <input class="form-control" name="new_pass" type="password" placeholder="خالی بگذارید تا تغییر نکند">
           </div>
           
           <button class="btn btn-accent w-100 fw-bold" type="submit">ذخیره تغییرات</button>
        </form>
     </div>
  </div>
</div>
<?php include __DIR__ . '/layout_footer.php'; ?>
