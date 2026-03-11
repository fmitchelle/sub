<?php
// cron.php - Run this via cron: php /path/to/cron.php

// Ensure we don't timeout
ignore_user_abort(true);
set_time_limit(0);

require_once __DIR__ . '/includes/functions.php';

// Check if run from CLI or Web
$is_cli = (php_sapi_name() === 'cli');
if ($is_cli) {
    echo "Starting update...\n";
}

// 1. Load Settings & Params
ensure_bootstrap_data();
$settings = load_json('settings.json', []);
// Default min_stability is 0 (keep all reachable)
$min_stability = intval($settings['min_stability'] ?? 0);

// 2. Fetch Sources
$sources = load_json('sources.json', []);
$manual = load_json('manual.json', []);

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
    if ($is_cli) echo "Fetching: " . ($src['name'] ?? 'Auto') . " (" . $src['url'] . ")\n";
    
    $content = fetch_url($src['url']);
    if ($content) {
        $list = parse_subscription($content);
        foreach ($list as $uri) {
            $candidates[] = [
                'uri' => $uri,
                'name' => $src['name'] ?? 'Auto',
                'origin' => 'auto'
            ];
        }
    } else {
        if ($is_cli) echo "Failed to fetch: " . $src['url'] . "\n";
    }
}

if ($is_cli) echo "Total candidates (raw): " . count($candidates) . "\n";

// Dedup candidates first to save testing time
$unique_candidates = [];
foreach ($candidates as $c) {
    $unique_candidates[$c['uri']] = $c;
}
$candidates = array_values($unique_candidates);

if ($is_cli) echo "Unique candidates: " . count($candidates) . "\n";

// 3. Test & Filter
$working = [];
$total = count($candidates);
$count = 0;

foreach ($candidates as $item) {
    $count++;
    if ($is_cli && $count % 10 === 0) echo "Testing $count / $total...\n";
    
    // 3 attempts
    $res = test_server_stability($item['uri'], 3);
    update_server_stats($item['uri'], $res['ok']);
    
    // Filter
    if ($res['stability'] >= $min_stability) {
        $item['id'] = md5($item['uri']);
        $item['stability'] = $res['stability'];
        $item['latency'] = $res['latency'];
        $working[] = $item;
    }
}

// 4. Save
save_json('servers.json', $working);

$settings = load_json('settings.json', []);
$settings['updated_at'] = now_iso();
save_json('settings.json', $settings);

if ($is_cli) echo "Update complete. Working: " . count($working) . "\n";
else echo "Update complete. Working: " . count($working);
