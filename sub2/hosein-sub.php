<?php
// ultragram.ir/sub/hosein-sub.php
// raw: https://ultragram.ir/sub/hosein-sub.php?raw=1

$configs = array(
  'vless://YXV0bzoxMWJkMWU2MS1mMWEwLTRiY2QtYjA2Ni03YTUzZTFlNjNjZTNANzcuMjM3LjkwLjE1Nzo0MDQ2?remarks=%F0%9F%9F%A3%F0%9F%87%AB%F0%9F%87%AE%20Finland%20%7C%20VPN',
);

// تمیزکاری و ساخت payload
$clean = array();
foreach ($configs as $c) {
  $c = trim($c);
  if ($c !== '') $clean[] = $c;
}
$payload = implode("\n", $clean) . "\n";

// هدرها (سازگار با کلاینت‌ها)
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (isset($_GET['raw']) && $_GET['raw'] == '1') {
  echo $payload;              // برای تست
} else {
  echo base64_encode($payload); // خروجی سابسکریپشن
}
exit;