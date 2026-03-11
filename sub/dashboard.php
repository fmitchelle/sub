<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
ensure_bootstrap_data();
require_login();

$settings = load_json('settings.json', ["subscription_token" => "", "title" => "Subscription Panel"]);
$servers  = load_json('servers.json', []);
$sources  = load_json('sources.json', []);
$manual   = load_json('manual.json', []);

$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$base   = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
$subUrl = $base . '/sub.php';
$rawUrl = $subUrl . '?raw=1';

$token = trim($settings['subscription_token'] ?? '');
if ($token !== '') {
  $subUrl .= '?token=' . urlencode($token);
  $rawUrl  = $subUrl . '&raw=1';
}

$totalServers = count($servers);
$totalSources = count($sources);
$totalManual  = count($manual);
$lastUpdate   = $settings['updated_at'] ?? '-';

// Load Stats
$usageStats = load_json('usage_stats.json', []);
uasort($usageStats, fn($a, $b) => $b['hits'] <=> $a['hits']);
$topUsers = array_slice($usageStats, 0, 5);

$serverStats = load_json('server_stats.json', []);
// Calculate reliability
foreach ($serverStats as &$s) {
    $s['ratio'] = $s['checks'] > 0 ? ($s['success'] / $s['checks']) : 0;
}
unset($s);
uasort($serverStats, fn($a, $b) => $b['ratio'] <=> $a['ratio']);
$reliableServers = array_slice($serverStats, 0, 5);

$page_title = 'داشبورد';
include __DIR__ . '/layout_header.php';
?>
<div class="row g-3 g-lg-4">
  <div class="col-lg-3 d-none d-lg-block">
    <?php include __DIR__ . '/sidebar.php'; ?>
  </div>

  <div class="col-lg-9">
    <!-- Topbar Mobile (Simplified) or Desktop Header -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <div class="fw-bold fs-4">داشبورد</div>
            <div class="muted small">وضعیت کلی سیستم</div>
        </div>
        <div class="d-flex gap-2">
             <button class="btn btn-accent d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#preUpdateModal">
                <span class="material-icons-round">sync</span> بروزرسانی
             </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Stats -->
        <div class="col-6 col-md-4">
            <div class="app-card p-3 text-center h-100">
                <div class="fs-2 fw-bold text-accent"><?php echo $totalServers; ?></div>
                <div class="muted small">سرورهای فعال</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="app-card p-3 text-center h-100">
                <div class="fs-2 fw-bold text-success"><?php echo $totalSources; ?></div>
                <div class="muted small">منابع لینک</div>
            </div>
        </div>
         <div class="col-12 col-md-4">
            <div class="app-card p-3 text-center h-100">
                <div class="fs-2 fw-bold text-warning"><?php echo $totalManual; ?></div>
                <div class="muted small">سرورهای دستی</div>
            </div>
        </div>
    </div>

    <!-- New Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="app-card p-3 p-lg-4 h-100">
                 <div class="fw-bold mb-3">کاربران فعال (Top 5)</div>
                 <div class="table-responsive">
                     <table class="table table-dark table-sm mb-0">
                        <thead><tr><th>کاربر</th><th>استفاده</th><th>آخرین بازدید</th></tr></thead>
                        <tbody>
                            <?php foreach($topUsers as $u => $d): ?>
                            <tr>
                                <td><?php echo h($u); ?></td>
                                <td><?php echo $d['hits']; ?></td>
                                <td class="small muted"><?php echo str_replace('T',' ',substr($d['last_access'], 0, 16)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($topUsers)): ?><tr><td colspan="3" class="muted text-center">داده‌ای نیست.</td></tr><?php endif; ?>
                        </tbody>
                     </table>
                 </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="app-card p-3 p-lg-4 h-100">
                 <div class="fw-bold mb-3">پایداری سرورها (Top 5)</div>
                 <div class="table-responsive">
                     <table class="table table-dark table-sm mb-0">
                        <thead><tr><th>ID (Hash)</th><th>پایداری</th><th>تست‌ها</th></tr></thead>
                        <tbody>
                            <?php foreach($reliableServers as $id => $d): ?>
                            <tr>
                                <td class="small mono"><?php echo substr($id, 0, 8); ?>...</td>
                                <td class="<?php echo $d['ratio'] > 0.8 ? 'text-success' : 'text-warning'; ?>">
                                    <?php echo round($d['ratio'] * 100); ?>%
                                </td>
                                <td class="small muted"><?php echo $d['checks']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                             <?php if(empty($reliableServers)): ?><tr><td colspan="3" class="muted text-center">داده‌ای نیست.</td></tr><?php endif; ?>
                        </tbody>
                     </table>
                 </div>
            </div>
        </div>
    </div>

    <!-- Link Box -->
    <div class="app-card p-3 p-lg-4 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="fw-bold">لینک اشتراک</div>
            <span class="badge-soft">Subscription</span>
        </div>
        <div class="muted small mb-2">این لینک را در کلاینت (v2rayNG, etc) وارد کن.</div>
        
        <div class="input-group mb-2">
            <input class="form-control mono text-center" id="subUrl" value="<?php echo h($subUrl); ?>" readonly>
            <button class="btn btn-accent" type="button" onclick="copyText('subUrl')">کپی</button>
        </div>
         <div class="d-flex justify-content-center gap-2">
             <a class="btn btn-ghost btn-sm" href="<?php echo h($subUrl); ?>" target="_blank">باز کردن</a>
             <button class="btn btn-ghost btn-sm" onclick="copyText('rawUrl')">کپی لینک Raw</button>
             <input type="hidden" id="rawUrl" value="<?php echo h($rawUrl); ?>">
         </div>
    </div>
    
    <div class="app-card p-3">
        <div class="fw-bold mb-2">آخرین بروزرسانی</div>
        <div class="mono small muted"><?php echo h($lastUpdate); ?></div>
    </div>

  </div>
</div>

<!-- Pre-Update Modal -->
<div class="modal fade" id="preUpdateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-dark">
      <div class="modal-header">
        <h5 class="modal-title">تنظیمات بروزرسانی</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">حداقل پایداری برای افزودن:</label>
        <select class="form-select" id="minStabilityVal">
            <option value="0">همه سرورها (حتی با ۱ بار اتصال)</option>
            <option value="10">بالای ۱۰٪</option>
            <option value="50" selected>بالای ۵۰٪ (پیش‌فرض)</option>
            <option value="80">بالای ۸۰٪ (فقط عالی)</option>
            <option value="100">۱۰۰٪ (فقط کامل)</option>
        </select>
        <div class="form-text">سرورهایی که پایداری کمتری دارند به لیست اضافه نمی‌شوند.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary w-100" onclick="startUpdateProcess()">شروع عملیات</button>
      </div>
    </div>
  </div>
</div>

<!-- Update Progress Modal -->
<div class="modal fade" id="updateModal" data-bs-backdrop="static" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--card); border:1px solid var(--border); color:var(--text);">
      <div class="modal-header border-bottom-0">
        <h5 class="modal-title">در حال بروزرسانی...</h5>
      </div>
      <div class="modal-body">
        <div id="updateSteps" class="d-flex flex-column gap-2">
            <!-- Steps will go here -->
        </div>
      </div>
      <div class="modal-footer border-top-0">
        <button type="button" class="btn btn-secondary d-none" id="btnCloseModal" onclick="window.location.reload()">بستن و بازنشانی</button>
      </div>
    </div>
  </div>
</div>

<script>
function copyText(id) {
    const el = document.getElementById(id);
    el.select();
    navigator.clipboard.writeText(el.value);
    alert('کپی شد!');
}

let preModal, updateModal;

function startUpdateProcess() {
    // Close pre modal
    const preEl = document.getElementById('preUpdateModal');
    preModal = bootstrap.Modal.getInstance(preEl);
    preModal.hide();
    
    // Open update modal
    const upEl = document.getElementById('updateModal');
    updateModal = new bootstrap.Modal(upEl);
    updateModal.show();
    
    triggerUpdate();
}

async function triggerUpdate() {
    const minStability = document.getElementById('minStabilityVal').value;
    const container = document.getElementById('updateSteps');
    const btnClose = document.getElementById('btnCloseModal');
    container.innerHTML = ''; 
    btnClose.classList.add('d-none');
    
    const addStep = (text, status = 'loading') => {
        const div = document.createElement('div');
        div.className = 'd-flex align-items-center justify-content-between p-2 rounded';
        div.style.background = 'rgba(255,255,255,0.03)';
        div.innerHTML = `<span>${text}</span> <span class="step-icon">${status === 'loading' ? '⏳' : status}</span>`;
        container.appendChild(div);
        return div;
    }
    
    const updateStep = (div, text, status) => {
        div.querySelector('span').innerText = text;
        div.querySelector('.step-icon').innerHTML = status;
    }

    try {
        // 1. Init
        addStep('آماده‌سازی...', '✅');
        await fetch('api.php?action=init_update');
        
        // 2. Get Sources
        const sRes = await fetch('api.php?action=get_sources');
        const sData = await sRes.json();
        const sources = sData.sources || [];
        
        // 3. Process Sources
        for (const src of sources) {
            const step = addStep(`تست منبع: ${src.name}`, 'loading');
            try {
                const res = await fetch(`api.php?action=process_source&id=${src.id}&min_stability=${minStability}`);
                const data = await res.json();
                if(data.status === 'ok') {
                    updateStep(step, `${src.name}: ${data.working} سرور`, '✅');
                } else {
                    updateStep(step, `${src.name}: خطا`, '❌');
                }
            } catch(e) {
                updateStep(step, `${src.name}: خطا شبکه`, '❌');
            }
        }
        
        // 4. Process Manual
        const mStep = addStep('سرورهای دستی...', 'loading');
        try {
            const mRes = await fetch(`api.php?action=process_manual&min_stability=${minStability}`);
            const mData = await mRes.json();
            updateStep(mStep, `دستی: ${mData.working} سرور`, '✅');
        } catch(e) {
             updateStep(mStep, `دستی: خطا`, '❌');
        }
        
        // 5. Finish
        const fStep = addStep('ذخیره نهایی...', 'loading');
        await fetch('api.php?action=finish_update');
        updateStep(fStep, 'پایان!', '🎉');
        
        btnClose.classList.remove('d-none');
        
    } catch (e) {
        addStep('خطای کلی: ' + e.message, '❌');
        btnClose.classList.remove('d-none');
    }
}
</script>

<?php include __DIR__ . '/layout_footer.php'; ?>
