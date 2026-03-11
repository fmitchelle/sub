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
        $min_stability = intval($_REQUEST['min_stability'] ?? 0);
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
        
        // Processing & Testing
        $working = [];
        $rename_counter = 1;
        $custom_label = $target['custom_label'] ?? '';

        foreach ($candidates as $item) {
            // 1. Persian Filter
            if ($remove_persian && has_persian_text($item['uri'])) {
                continue;
            }

            // 2. Custom Renaming
            if (!empty($custom_label)) {
                $new_tag = $custom_label . '_' . $rename_counter;
                $item['uri'] = rename_config_fragment($item['uri'], $new_tag);
                $item['name'] = $custom_label . ' ' . $rename_counter;
                $rename_counter++;
            }

            // 3. Check stability with 3 attempts
            $res = test_server_stability($item['uri'], 3);
            
            // Update stats
            update_server_stats($item['uri'], $res['ok']);
            
            // Filter
            if ($res['stability'] >= $min_stability) {
                $item['id'] = md5($item['uri']);
                $item['stability'] = $res['stability'];
                $item['latency'] = $res['latency'];
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
        $min_stability = intval($_REQUEST['min_stability'] ?? 0);
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
             $res = test_server_stability($item['uri'], 3);
             update_server_stats($item['uri'], $res['ok']);
             
             if ($res['stability'] >= $min_stability) {
                $item['id'] = md5($item['uri']);
                $item['stability'] = $res['stability'];
                $item['latency'] = $res['latency'];
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
        $limit_val = trim($_POST['limit'] ?? '');
        $limit = ($limit_val === '') ? 0 : floatval($limit_val);
        
        if ($name === '') throw new Exception("Name required");
        
        $clients = load_json('clients.json', []);
        foreach($clients as $c) {
            if(strtolower($c['username']) === strtolower($name)) throw new Exception("Username exists");
        }
        
        $clients[] = [
            'id' => bin2hex(random_bytes(8)),
            'username' => $name,
            'limit_gb' => $limit,
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
    elseif ($action === 'bulk_delete') {
        $ids = $_POST['ids'] ?? []; // expect array
        if (!is_array($ids)) $ids = explode(',', $ids);
        
        $servers = load_json('servers.json', []);
        $initial_count = count($servers);
        
        $servers = array_values(array_filter($servers, function($s) use ($ids) {
            return !in_array($s['id'], $ids);
        }));
        
        save_json('servers.json', $servers);
        echo json_encode(['status' => 'ok', 'deleted' => $initial_count - count($servers)]);
    }
    elseif ($action === 'bulk_rename') {
        $ids = $_POST['ids'] ?? [];
        $new_name_base = trim($_POST['new_name'] ?? '');
        
        if (!is_array($ids)) $ids = explode(',', $ids);
        if ($new_name_base === '') throw new Exception("New name required");
        
        $servers = load_json('servers.json', []);
        $counter = 1;
        $modified = 0;
        
        // We need to iterate and match IDs.
        // If passed IDs contain the server ID, rename it.
        // We use $counter to append _1, _2...
        // But only increment counter if we actually renamed one in the set? 
        // Or global counter? The user said "all selected configs... #MyBrand_1, #MyBrand_2".
        
        // First map IDs to be sure we have efficient lookup
        $target_ids = array_flip($ids); 
        
        foreach ($servers as &$s) {
            if (isset($target_ids[$s['id']])) {
                $name_with_index = $new_name_base . '_' . $counter;
                $s['uri'] = rename_config_fragment($s['uri'], $name_with_index);
                // Update name field too just in case, though usually 'name' field is from source.
                // But the URI tag is the important part for clients.
                // We can update the display name too.
                $s['name'] = $new_name_base . ' ' . $counter;
                
                // Recalculate ID? No, ID is md5(uri). If URI changes, ID changes.
                // But if we change ID here, we lose track?
                // Actually `md5($item['uri'])` is how ID is generated. 
                // If we change URI, we should update ID.
                $s['id'] = md5($s['uri']);
                
                $counter++;
                $modified++;
            }
        }
        unset($s); // break ref
        
        save_json('servers.json', $servers);
        echo json_encode(['status' => 'ok', 'modified' => $modified]);
    }
    else {
        throw new Exception("Invalid action: $action");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
