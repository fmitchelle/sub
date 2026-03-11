<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
ensure_bootstrap_data();
$settings = load_json('settings.json', ["title" => "Subscription Panel"]);
$page_title = $page_title ?? ($settings['title'] ?? 'Subscription Panel');
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo h($page_title); ?></title>

  <!-- Vazirmatn -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap RTL -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
    :root{
      --bg0:#07070a;
      --bg1:#0b0b10;
      --card:#0f1018;
      --card2:#121427;
      --border:rgba(255,255,255,.08);
      --muted:rgba(255,255,255,.65);
      --text:#f3f4f6;
      --accent:#7c3aed;
      --accent2:#22c55e;
      --warn:#f59e0b;
      --danger:#ef4444;
    }
    html,body{height:100%}
    body{
      font-family:"Vazirmatn", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      background: radial-gradient(1200px 600px at 10% 10%, rgba(124,58,237,.18), transparent 60%),
                  radial-gradient(900px 500px at 90% 20%, rgba(34,197,94,.12), transparent 60%),
                  linear-gradient(180deg, var(--bg0), var(--bg1));
      color:var(--text);
    }
    .app-shell{min-height:100vh}
    .app-card{
      background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.02));
      border: 1px solid var(--border);
      border-radius: 18px;
      box-shadow: 0 16px 40px rgba(0,0,0,.35);
    }
    .muted{color:var(--muted)}
    .mono{font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;}
    .form-control, .form-select, textarea{
      background: rgba(255,255,255,.03) !important;
      border: 1px solid var(--border) !important;
      color:var(--text) !important;
      border-radius: 14px !important;
    }
    .form-control:focus, textarea:focus{
      box-shadow: 0 0 0 .2rem rgba(124,58,237,.18) !important;
      border-color: rgba(124,58,237,.5) !important;
    }
    .btn{border-radius: 14px}
    .btn-accent{
      background: linear-gradient(135deg, rgba(124,58,237,1), rgba(99,102,241,1));
      border:0;
      color:white;
    }
    .btn-accent:hover{filter:brightness(1.05)}
    .btn-ghost{
      background: rgba(255,255,255,.04);
      border: 1px solid var(--border);
      color: var(--text);
    }
    .btn-ghost:hover{background: rgba(255,255,255,.06); color: var(--text);}
    .badge-soft{
      background: rgba(255,255,255,.06);
      border: 1px solid var(--border);
      color: var(--text);
      border-radius: 999px;
      padding: .35rem .65rem;
      font-weight: 600;
    }
    .table-dark{
      --bs-table-bg: transparent;
      --bs-table-striped-bg: rgba(255,255,255,.03);
      --bs-table-border-color: rgba(255,255,255,.06);
      color: var(--text);
    }
    .kbd{
      display:inline-block;
      padding:.2rem .5rem;
      border-radius:10px;
      border:1px solid var(--border);
      background:rgba(255,255,255,.04);
      font-size:.85rem;
    }

    /* Sidebar */
    .sidebar{
      position: sticky;
      top: 18px;
      height: calc(100vh - 36px);
      border-radius: 22px;
      border: 1px solid var(--border);
      background: rgba(15,16,24,.75);
      backdrop-filter: blur(14px);
      padding: 14px;
    }
    .navpill{
      display:flex;
      align-items:center;
      gap:10px;
      padding:10px 12px;
      border-radius:14px;
      color:var(--text);
      text-decoration:none;
      border:1px solid transparent;
    }
    .navpill:hover{
      background: rgba(255,255,255,.04);
      border-color: var(--border);
      color:var(--text);
    }
    .navpill.active{
      background: rgba(124,58,237,.18);
      border-color: rgba(124,58,237,.35);
    }
    .dot{
      width:10px;height:10px;border-radius:50%;
      background: var(--accent);
      box-shadow: 0 0 0 6px rgba(124,58,237,.12);
    }
    .topbar{
      border-radius: 22px;
      border: 1px solid var(--border);
      background: rgba(15,16,24,.55);
      backdrop-filter: blur(14px);
      padding: 12px 14px;
    }

    @media (max-width: 991px){
      .sidebar{ display: none; }
      .app-shell{ padding-bottom: 80px; }
    }
    @media (min-width: 992px){
      .bottom-nav { display: none; }
    }

    /* Bottom Nav */
    .bottom-nav {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: rgba(15,16,24,.95);
      backdrop-filter: blur(20px);
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: space-around;
      padding: 10px 0;
      z-index: 1000;
    }
    .bottom-nav-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      color: var(--muted);
      text-decoration: none;
      font-size: 0.75rem;
      gap: 4px;
    }
    .bottom-nav-item svg {
      width: 20px;
      height: 20px;
      fill: currentColor;
    }
    .bottom-nav-item.active {
      color: var(--accent);
    }
  </style>
</head>
<body>
<div class="container py-3 py-lg-4 app-shell">
