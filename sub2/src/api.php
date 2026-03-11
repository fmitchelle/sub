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
        $out = [];
        foreach($sources as $s) {
            if ($s['enabled'] ?? true) $out[] = $s;
        }
        echo json_encode(['status' => 'ok', 'sources' => $out]);
    }
    elseif ($action === 'process_source') {
        $id = $_REQUEST['id'] ?? '';
        $remove_inactive = ($_REQUEST['remove_inactive'] ?? 'false') === 'true';
        $remove_persian = ($_REQUEST['remove_persian'] ?? 'false') === 'true';

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

        // Filter & Test
        $working = [];
        $rename_counter = 1;

        foreach ($candidates as $item) {
            // 1. Persian Filter (Check ORIGINAL URI)
            if ($remove_persian && has_persian_text($item['uri'])) {
                continue;
            }

            // 2. Renaming
            if (!empty($target['custom_label'])) {
                $item['uri'] = rename_uri($item['uri'], $target['custom_label'] . ' ' . $rename_counter);
                $item['name'] = $target['custom_label'];
                $rename_counter++;
            }

            // 3. Connectivity & Inactive Filter
            $is_ok = test_server_connectivity($item['uri']);
            update_server_stats($item['uri'], $is_ok);
            
            // If remove_inactive is TRUE, we strictly require $is_ok
            // If remove_inactive is FALSE, we keep it regardless
            if ($is_ok || !$remove_inactive) {
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
        $remove_inactive = ($_REQUEST['remove_inactive'] ?? 'false') === 'true';
        // Manual items usually don't have Persian Ads we want to remove, but let's respect the flag if user wants?
        // Usually manual items are trusted. But for consistency, let's just do Inactive check.
        // The user specifically talked about "subs" having ads.
        
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
        
        $working = [];
        foreach ($candidates as $item) {
             $is_ok = test_server_connectivity($item['uri']);
             update_server_stats($item['uri'], $is_ok);
             
             if ($is_ok || !$remove_inactive) {
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
