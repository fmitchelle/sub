<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
ensure_bootstrap_data();
require_login();

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'init_update') {
        save_json('temp_servers.json', []);
        echo json_encode(['status' => 'ok']);
    }
    elseif ($action === 'get_sources') {
        $sources = load_json('sources.json', []);
        // Filter enabled only? The UI might want to show all but skip disabled.
        // Let's return all and let UI decide or filter here.
        // The previous logic filtered enabled.
        $out = [];
        foreach($sources as $s) {
            if ($s['enabled'] ?? true) $out[] = $s;
        }
        echo json_encode(['status' => 'ok', 'sources' => $out]);
    }
    elseif ($action === 'process_source') {
        $id = $_REQUEST['id'] ?? '';
        $sources = load_json('sources.json', []);
        $target = null;
        foreach($sources as $s) {
            if (($s['id'] ?? '') === $id) {
                $target = $s;
                break;
            }
        }
        
        if (!$target) {
            throw new Exception("Source not found");
        }
        
        $url = $target['url'];
        $content = fetch_url($url);
        $candidates = [];
        if ($content) {
            $list = parse_subscription($content);
            foreach ($list as $uri) {
                $candidates[] = [
                    'uri' => $uri,
                    'name' => $target['name'] ?? 'Auto',
                    'origin' => 'auto'
                ];
            }
        }
        
        // Test connectivity
        $working = [];
        foreach ($candidates as $item) {
            $is_ok = test_server_connectivity($item['uri']);
            update_server_stats($item['uri'], $is_ok);
            if ($is_ok) {
                $item['id'] = md5($item['uri']); 
                $working[] = $item;
            }
        }
        
        // Append to temp_servers.json
        $current_temp = load_json('temp_servers.json', []);
        $merged = array_merge($current_temp, $working);
        save_json('temp_servers.json', $merged);
        
        echo json_encode([
            'status' => 'ok', 
            'found' => count($candidates), 
            'working' => count($working)
        ]);
    }
    elseif ($action === 'process_manual') {
        $manual = load_json('manual.json', []);
        $candidates = [];
        foreach ($manual as $m) {
            if (!empty($m['uri'])) {
                $candidates[] = [
                    'uri' => $m['uri'],
                    'name' => $m['name'] ?? 'Manual',
                    'origin' => 'manual'
                ];
            }
        }
        
        // Test connectivity for manual? 
        // Usually manual entries are assumed good, but testing is safer.
        // The original code tested EVERYTHING (including manual).
        
        $working = [];
        foreach ($candidates as $item) {
             $is_ok = test_server_connectivity($item['uri']);
             update_server_stats($item['uri'], $is_ok);
             if ($is_ok) {
                $item['id'] = md5($item['uri']); 
                $working[] = $item;
            }
        }
        
        $current_temp = load_json('temp_servers.json', []);
        $merged = array_merge($current_temp, $working);
        save_json('temp_servers.json', $merged);
        
        echo json_encode([
            'status' => 'ok', 
            'found' => count($candidates), 
            'working' => count($working)
        ]);
    }
    elseif ($action === 'finish_update') {
        $temp = load_json('temp_servers.json', []);
        
        // Deduplicate by URI (global deduplication)
        $unique = [];
        foreach ($temp as $c) {
            $unique[$c['uri']] = $c;
        }
        $final_list = array_values($unique);
        
        save_json('servers.json', $final_list);
        
        // Update timestamp
        $settings = load_json('settings.json', []);
        $settings['updated_at'] = now_iso();
        save_json('settings.json', $settings);
        
        // Clean up temp (optional, but good practice)
        if(file_exists(data_path('temp_servers.json'))) {
            unlink(data_path('temp_servers.json'));
        }
        
        echo json_encode(['status' => 'ok', 'total' => count($final_list)]);
    }
    elseif ($action === 'client_list') {
        $clients = load_json('clients.json', []);
        echo json_encode(['status' => 'ok', 'clients' => $clients]);
    }
    elseif ($action === 'client_add') {
        $name = trim($_POST['name'] ?? '');
        $limit = trim($_POST['limit'] ?? '0');
        
        if ($name === '') throw new Exception("Name required");
        
        $clients = load_json('clients.json', []);
        // Check duplicate name
        foreach($clients as $c) {
            if(strtolower($c['username']) === strtolower($name)) throw new Exception("Username exists");
        }
        
        $clients[] = [
            'id' => bin2hex(random_bytes(8)),
            'username' => $name,
            'limit_gb' => (float)$limit,
            'created_at' => now_iso()
        ];
        
        save_json('clients.json', $clients);
        echo json_encode(['status' => 'ok']);
    }
    elseif ($action === 'client_delete') {
        $id = $_POST['id'] ?? '';
        $clients = load_json('clients.json', []);
        $clients = array_values(array_filter($clients, fn($c) => ($c['id'] ?? '') !== $id));
        save_json('clients.json', $clients);
        echo json_encode(['status' => 'ok']);
    }
    else {
        throw new Exception("Invalid action: $action");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
