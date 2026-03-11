<?php
// includes/functions.php
function data_path(string $file): string {
  return __DIR__ . '/../data/' . $file;
}

function load_json(string $file, $default) {
  $p = data_path($file);
  if (!file_exists($p)) return $default;
  $raw = file_get_contents($p);
  $j = json_decode($raw, true);
  return is_array($j) ? $j : $default;
}

function save_json(string $file, $data): void {
  $p = data_path($file);
  $dir = dirname($p);
  if (!is_dir($dir)) mkdir($dir, 0755, true);
  $tmp = $p . '.tmp';
  file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
  rename($tmp, $p);
}

function h($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function now_iso(): string {
  return gmdate('c');
}

function ensure_bootstrap_data(): void {
  // settings.json
  if (!file_exists(data_path('settings.json'))) {
    $settings = [
      "subscription_token" => "",
      "title" => "Hosein Subscription Panel",
      "updated_at" => now_iso()
    ];
    save_json('settings.json', $settings);
  }

  // servers.json (The Output)
  if (!file_exists(data_path('servers.json'))) {
    save_json('servers.json', []);
  }
  
  // manual.json (Manual Inputs)
  if (!file_exists(data_path('manual.json'))) {
    save_json('manual.json', []);
  }
  
  // sources.json (External Links)
  if (!file_exists(data_path('sources.json'))) {
    save_json('sources.json', []);
  }

  // users.json
  if (!file_exists(data_path('users.json'))) {
    // default user: hosein / hosein  (CHANGE IT after first login)
    $users = [
      [
        "username" => "hosein",
        "password_hash" => password_hash("hosein", PASSWORD_DEFAULT),
        "created_at" => now_iso()
      ]
    ];
    save_json('users.json', $users);
  }
}

// --- New Logic ---

function fetch_url($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function parse_server_host_port($uri) {
    $uri = trim($uri);
    if (strpos($uri, 'vmess://') === 0) {
        $b64 = substr($uri, 8);
        $jsonStr = base64_decode($b64);
        $json = json_decode($jsonStr, true);
        // Sometimes vmess json is not standard or empty
        if (is_array($json) && isset($json['add']) && isset($json['port'])) {
            return ['host' => $json['add'], 'port' => $json['port']];
        }
        return null; 
    }
    
    // vless, trojan, ss
    // Try parse_url first
    $parts = parse_url($uri);
    if (isset($parts['host']) && isset($parts['port'])) {
        return ['host' => $parts['host'], 'port' => $parts['port']];
    }
    
    // Fallback Regex
    if (preg_match('/@([^:]+):(\d+)/', $uri, $m)) {
        return ['host' => $m[1], 'port' => $m[2]];
    }
    
    return null;
}

function test_server_connectivity($uri) {
    $target = parse_server_host_port($uri);
    if (!$target) {
        return false; 
    }

    $host = $target['host'];
    $port = $target['port'];

    // Retry mechanism (2 attempts, 5s timeout)
    for ($i = 0; $i < 2; $i++) {
        $fp = @fsockopen($host, $port, $errno, $errstr, 5); 
        if ($fp) {
            fclose($fp);
            return true;
        }
        usleep(200000); // 200ms delay
    }
    return false;
}

function has_persian_text($str) {
    // Check if string contains Persian/Arabic characters
    $str = trim($str);

    // 1. VMess Logic: Decode base64 payload
    if (strpos($str, 'vmess://') === 0) {
        $b64 = substr($str, 8);
        $decoded = base64_decode($b64);
        if ($decoded) {
             if (preg_match('/[\x{0600}-\x{06FF}]/u', $decoded)) {
                 return true;
             }
        }
    }

    // 2. Generic Check (URL Decode for others, e.g. fragments)
    return (bool)preg_match('/[\x{0600}-\x{06FF}]/u', urldecode($str));
}

function rename_uri($uri, $label) {
    $uri = trim($uri);
    if (strpos($uri, 'vmess://') === 0) {
        $b64 = substr($uri, 8);
        $jsonStr = base64_decode($b64);
        $json = json_decode($jsonStr, true);
        if (is_array($json)) {
            $json['ps'] = $label;
            return 'vmess://' . base64_encode(json_encode($json, JSON_UNESCAPED_UNICODE));
        }
        return $uri;
    }
    
    // vless, trojan, ss
    if (strpos($uri, '#') !== false) {
        $parts = explode('#', $uri, 2);
        return $parts[0] . '#' . rawurlencode($label);
    } else {
        return $uri . '#' . rawurlencode($label);
    }
}

function parse_subscription($content) {
    // 1. Try Base64 Decode
    $decoded = base64_decode($content, true);
    
    $payload = ($decoded !== false && ctype_print($decoded)) ? $decoded : $content;
    
    if (strpos($content, 'vmess://') !== false || strpos($content, 'vless://') !== false) {
        $payload = $content;
    } else {
        $d = base64_decode($content, true);
        if ($d) $payload = $d;
    }

    $lines = preg_split('/\r\n|\r|\n/', $payload);
    $servers = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        if (strpos($line, 'vmess://') === 0 || strpos($line, 'vless://') === 0 || strpos($line, 'trojan://') === 0 || strpos($line, 'ss://') === 0) {
            $servers[] = $line;
        }
    }
    return $servers;
}

function aggregate_and_update() {
    // 1. Load Data
    $manual = load_json('manual.json', []);
    $sources = load_json('sources.json', []);
    
    $candidates = [];
    
    // Manual
    foreach ($manual as $m) {
        if (!empty($m['uri'])) {
            $candidates[] = [
                'uri' => $m['uri'],
                'name' => $m['name'] ?? 'Manual',
                'origin' => 'manual'
            ];
        }
    }
    
    // Sources
    foreach ($sources as $src) {
        if (!($src['enabled'] ?? true)) continue;
        $url = $src['url'];
        $content = fetch_url($url);
        if ($content) {
            $list = parse_subscription($content);
            foreach ($list as $uri) {
                $candidates[] = [
                    'uri' => $uri,
                    'name' => $src['name'] ?? 'Auto',
                    'origin' => 'auto'
                ];
            }
        }
    }
    
    // Deduplicate by URI
    $unique = [];
    foreach ($candidates as $c) {
        $unique[$c['uri']] = $c;
    }
    
    // Test
    $working = [];
    foreach ($unique as $item) {
        if (test_server_connectivity($item['uri'])) {
            $item['id'] = md5($item['uri']); 
            $working[] = $item;
        }
    }
    
    // Save
    save_json('servers.json', $working);
    
    $settings = load_json('settings.json', []);
    $settings['updated_at'] = now_iso();
    save_json('settings.json', $settings);
    
    return [
        'total_candidates' => count($unique),
        'working' => count($working)
    ];
}

// --- Statistics ---

function update_server_stats($uri, $success) {
    $id = md5($uri);
    $stats = load_json('server_stats.json', []);
    
    if (!isset($stats[$id])) {
        $stats[$id] = [
            'checks' => 0,
            'success' => 0,
            'last_check' => null
        ];
    }
    
    $stats[$id]['checks']++;
    if ($success) {
        $stats[$id]['success']++;
    }
    $stats[$id]['last_check'] = now_iso();
    
    save_json('server_stats.json', $stats);
}

function track_subscription_hit($user = null) {
    $key = $user ? $user : 'guest';
    $stats = load_json('usage_stats.json', []);
    
    if (!isset($stats[$key])) {
        $stats[$key] = [
            'hits' => 0,
            'last_access' => null
        ];
    }
    
    $stats[$key]['hits']++;
    $stats[$key]['last_access'] = now_iso();
    
    save_json('usage_stats.json', $stats);
}
