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
    curl_setopt($ch, CURLOPT_TIMEOUT, 20); // Increased timeout
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
        if (is_array($json) && isset($json['add']) && isset($json['port'])) {
            return ['host' => $json['add'], 'port' => $json['port']];
        }
        return null; 
    }
    
    // vless, trojan, ss
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

/**
 * Relaxed testing logic.
 * Returns array: ['ok' => bool, 'stability' => int(0-100), 'latency' => int(ms)]
 */
function test_server_stability($uri, $attempts = 3) {
    $target = parse_server_host_port($uri);
    if (!$target) {
        return ['ok' => false, 'stability' => 0, 'latency' => 0];
    }

    $host = $target['host'];
    $port = $target['port'];

    $success_count = 0;
    $total_latency = 0;
    
    for ($i = 0; $i < $attempts; $i++) {
        $start = microtime(true);
        // Timeout 3 seconds per attempt
        $fp = @fsockopen($host, $port, $errno, $errstr, 3); 
        if ($fp) {
            $latency = round((microtime(true) - $start) * 1000);
            $total_latency += $latency;
            $success_count++;
            fclose($fp);
        }
    }

    if ($success_count === 0) {
        return ['ok' => false, 'stability' => 0, 'latency' => 0];
    }

    $avg_latency = round($total_latency / $success_count);
    $stability = round(($success_count / $attempts) * 100);

    return [
        'ok' => true,
        'stability' => $stability,
        'latency' => $avg_latency
    ];
}

function rename_config_fragment($uri, $new_name) {
    $uri = trim($uri);
    if (strpos($uri, 'vmess://') === 0) {
        $b64 = substr($uri, 8);
        $jsonStr = base64_decode($b64);
        $json = json_decode($jsonStr, true);
        if (is_array($json)) {
            $json['ps'] = $new_name;
            return 'vmess://' . base64_encode(json_encode($json, JSON_UNESCAPED_UNICODE));
        }
        // Fallback if not valid json vmess
        return $uri;
    }
    
    // For vless, trojan, ss, etc. replace content after #
    if (strpos($uri, '#') !== false) {
         return preg_replace('/#.*$/', '#' . $new_name, $uri);
    } else {
         return $uri . '#' . $new_name;
    }
}

function parse_subscription($content) {
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

// Deprecated: old strict function wrapper for backward compatibility if needed
function test_server_connectivity($uri) {
    $res = test_server_stability($uri, 1);
    return $res['ok'];
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

function has_persian_text($str) {
    return preg_match('/[\x{0600}-\x{06FF}]/u', $str);
}
