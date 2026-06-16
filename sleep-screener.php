<?php
/**
 * Plugin Name: Sleep Apnea Screener
 * Description: Berlin Sleep Questionnaire and STOP-Bang Questionnaire with scoring, results, and GoHighLevel integration.
 * Version:     1.3.0
 * Author:      Adel Emad
 * Author URI:  https://upwork.com/freelancers/adelsherif8
 * License:     GPL-2.0+
 * GitHub Plugin URI: adelsherif8/sleep-screener
 */

defined('ABSPATH') || exit;

define('SLQ_VERSION',     '1.3.0');
define('SLQ_DB_VERSION',  '1');
define('SLQ_DIR',         plugin_dir_path(__FILE__));
define('SLQ_URL',         plugin_dir_url(__FILE__));
define('SLQ_GITHUB_REPO', 'adelsherif8/sleep-screener');

/* ─── Database setup ──────────────────────────────────────── */

function slq_maybe_create_table(): void {
    if (get_option('slq_db_version') === SLQ_DB_VERSION) return;
    global $wpdb;
    $table   = $wpdb->prefix . 'slq_entries';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  form varchar(20) NOT NULL,
  submitted_at datetime NOT NULL,
  full_name varchar(255) NOT NULL DEFAULT '',
  email varchar(255) NOT NULL DEFAULT '',
  phone varchar(50) NOT NULL DEFAULT '',
  risk_level varchar(30) NOT NULL DEFAULT '',
  score_data longtext NOT NULL,
  form_data longtext NOT NULL,
  PRIMARY KEY  (id),
  KEY submitted_at (submitted_at),
  KEY email (email(191))
) {$charset};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    update_option('slq_db_version', SLQ_DB_VERSION);
}
add_action('init', 'slq_maybe_create_table');

function slq_save_entry(string $form, string $full_name, string $email, string $phone, string $risk_level, array $score, array $form_data): void {
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'slq_entries',
        [
            'form'         => $form,
            'submitted_at' => current_time('mysql'),
            'full_name'    => $full_name,
            'email'        => $email,
            'phone'        => $phone,
            'risk_level'   => $risk_level,
            'score_data'   => wp_json_encode($score),
            'form_data'    => wp_json_encode($form_data),
        ],
        ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
    );
}

/* ─── CSS variable helper ──────────────────────────────────── */

function slq_css_vars(string $hex, string $prefix): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) $hex = '2d6a5a';
    [$r, $g, $b] = [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
    $dark  = sprintf('#%02x%02x%02x', max(0,(int)($r*.80)), max(0,(int)($g*.80)), max(0,(int)($b*.80)));
    $light = "rgba($r,$g,$b,0.10)";
    return "--{$prefix}-primary:#{$hex};--{$prefix}-primary-dark:{$dark};--{$prefix}-primary-light:{$light}";
}

/* ─── Combined GHL field list ──────────────────────────────── */

function slq_field_list(): array {
    return [
        // Demographics — General Questionnaire folder (shared by both forms)
        'age'              => ['label' => 'Age',                          'example' => '45',                         'group' => 'Demographics',      'folder' => 'General Questionnaire'],
        'gender'           => ['label' => 'Gender',                       'example' => 'male / female',               'group' => 'Demographics',      'folder' => 'General Questionnaire'],
        'height'           => ['label' => 'Height',                       'example' => "5'10\" or 178 cm",            'group' => 'Demographics',      'folder' => 'General Questionnaire'],
        'weight'           => ['label' => 'Weight',                       'example' => '185 lb or 80 kg',             'group' => 'Demographics',      'folder' => 'General Questionnaire'],
        'bmi'              => ['label' => 'BMI',                          'example' => '26.4',                        'group' => 'Demographics',      'folder' => 'General Questionnaire'],
        // Berlin Answers
        'b_snores'         => ['label' => 'Berlin — Snores?',             'example' => 'yes / no / dont_know',        'group' => 'Berlin Answers',    'folder' => 'Berlin Questionnaire'],
        'b_snore_volume'   => ['label' => 'Berlin — Snoring volume',      'example' => 'very_loud',                   'group' => 'Berlin Answers',    'folder' => 'Berlin Questionnaire'],
        'b_snore_freq'     => ['label' => 'Berlin — Snoring frequency',   'example' => 'nearly_every_day',            'group' => 'Berlin Answers',    'folder' => 'Berlin Questionnaire'],
        'b_bothers_others' => ['label' => 'Berlin — Bothers others?',     'example' => 'yes / no',                    'group' => 'Berlin Answers',    'folder' => 'Berlin Questionnaire'],
        'b_stop_breathing' => ['label' => 'Berlin — Stop breathing?',     'example' => 'nearly_every_day',            'group' => 'Berlin Answers',    'folder' => 'Berlin Questionnaire'],
        'b_tired_sleep'    => ['label' => 'Berlin — Tired after sleep',   'example' => '3_4_times',                   'group' => 'Berlin Answers',    'folder' => 'Berlin Questionnaire'],
        'b_tired_day'      => ['label' => 'Berlin — Tired during day',    'example' => 'nearly_every_day',            'group' => 'Berlin Answers',    'folder' => 'Berlin Questionnaire'],
        'b_fall_asleep'    => ['label' => 'Berlin — Fall asleep driving?','example' => 'yes / no',                   'group' => 'Berlin Answers',    'folder' => 'Berlin Questionnaire'],
        'b_blood_pressure' => ['label' => 'Berlin — Blood pressure?',     'example' => 'yes / no / dont_know',        'group' => 'Berlin Answers',    'folder' => 'Berlin Questionnaire'],
        // Berlin Score
        'b_risk_level'     => ['label' => 'Berlin — Risk Level',          'example' => 'High Risk / Low Risk',        'group' => 'Berlin Score',      'folder' => 'Berlin Questionnaire'],
        'b_pos_categories' => ['label' => 'Berlin — Positive Categories', 'example' => '2',                          'group' => 'Berlin Score',      'folder' => 'Berlin Questionnaire'],
        'b_cat1_positive'  => ['label' => 'Berlin — Category 1 Positive', 'example' => 'Yes / No',                   'group' => 'Berlin Score',      'folder' => 'Berlin Questionnaire'],
        'b_cat2_positive'  => ['label' => 'Berlin — Category 2 Positive', 'example' => 'Yes / No',                   'group' => 'Berlin Score',      'folder' => 'Berlin Questionnaire'],
        'b_cat3_positive'  => ['label' => 'Berlin — Category 3 Positive', 'example' => 'Yes / No',                   'group' => 'Berlin Score',      'folder' => 'Berlin Questionnaire'],
        // STOP-Bang Answers
        'sb_snoring'              => ['label' => 'STOP-Bang — Snoring',          'example' => 'yes / no',                   'group' => 'STOP-Bang Answers', 'folder' => 'STOP-Bang Questionnaire'],
        'sb_tired'                => ['label' => 'STOP-Bang — Tired',            'example' => 'yes / no',                   'group' => 'STOP-Bang Answers', 'folder' => 'STOP-Bang Questionnaire'],
        'sb_observed'             => ['label' => 'STOP-Bang — Observed',         'example' => 'yes / no',                   'group' => 'STOP-Bang Answers', 'folder' => 'STOP-Bang Questionnaire'],
        'stopbang__high_pressure' => ['label' => 'STOP-Bang — High Pressure',    'example' => 'yes / no',                   'group' => 'STOP-Bang Answers', 'folder' => 'STOP-Bang Questionnaire'],
        'stopbang__neck__16'      => ['label' => 'STOP-Bang — Neck ≥ 16″',       'example' => 'yes / no',                   'group' => 'STOP-Bang Answers', 'folder' => 'STOP-Bang Questionnaire'],
        // STOP-Bang Score
        'stopbang__stop_score'    => ['label' => 'STOP-Bang — STOP Score',       'example' => '3',                          'group' => 'STOP-Bang Score',   'folder' => 'STOP-Bang Questionnaire'],
        'stopbang__bang_score'    => ['label' => 'STOP-Bang — BANG Score',       'example' => '2',                          'group' => 'STOP-Bang Score',   'folder' => 'STOP-Bang Questionnaire'],
        'stopbang__total_score'   => ['label' => 'STOP-Bang — Total Score',      'example' => '5',                          'group' => 'STOP-Bang Score',   'folder' => 'STOP-Bang Questionnaire'],
        'stopbang__risk_level'    => ['label' => 'STOP-Bang — Risk Level',       'example' => 'High / Intermediate / Low',  'group' => 'STOP-Bang Score',   'folder' => 'STOP-Bang Questionnaire'],
    ];
}

/* ─── Admin menu ───────────────────────────────────────────── */

add_action('admin_menu', function () {
    add_options_page(
        'Sleep Apnea Screener',
        'Sleep Apnea Screener',
        'manage_options',
        'slq-settings',
        'slq_render_settings'
    );
    add_submenu_page(
        'options-general.php',
        'Sleep Screener Entries',
        'Sleep Screener Entries',
        'manage_options',
        'slq-entries',
        'slq_render_entries'
    );
});

/* ─── Admin AJAX: test GHL connection ─────────────────────── */

add_action('wp_ajax_slq_test_ghl', function () {
    check_ajax_referer('slq_ghl', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    $api_key     = get_option('slq_ghl_api_key', '');
    $location_id = get_option('slq_ghl_location_id', '');
    if (!$api_key)     wp_send_json_error(['message' => 'API Key is not saved.']);
    if (!$location_id) wp_send_json_error(['message' => 'Location ID is not saved.']);
    $r = wp_remote_get('https://services.leadconnectorhq.com/locations/' . $location_id, [
        'headers' => slq_ghl_headers(), 'timeout' => 10,
    ]);
    if (is_wp_error($r)) wp_send_json_error(['message' => 'Connection failed: ' . $r->get_error_message()]);
    $code = wp_remote_retrieve_response_code($r);
    $body = json_decode(wp_remote_retrieve_body($r), true) ?? [];
    if ($code === 200) {
        // Also check field map
        delete_transient('slq_ghl_field_map');
        $fr = wp_remote_get('https://services.leadconnectorhq.com/locations/' . $location_id . '/customFields', [
            'headers' => slq_ghl_headers(), 'timeout' => 10,
        ]);
        $field_count = 0;
        $slq_fields  = 0;
        $missing     = [];
        if (!is_wp_error($fr) && wp_remote_retrieve_response_code($fr) < 400) {
            $fields    = json_decode(wp_remote_retrieve_body($fr), true)['customFields'] ?? [];
            $field_count = count($fields);
            $known_map = slq_field_list();
            $known     = array_keys($known_map);
            $found     = [];

            // Build lookup by key and by label, then persist IDs so settings page auto-fills
            $by_key   = [];
            $by_label = [];
            foreach ($fields as $f) {
                $bare = strtolower(preg_replace('/^contact\./', '', $f['fieldKey'] ?? ''));
                if ($bare && !empty($f['id']))              $by_key[$bare]                       = $f['id'];
                $lbl = strtolower(trim($f['name'] ?? ''));
                if ($lbl && !empty($f['id']))               $by_label[$lbl]                      = $f['id'];
            }
            $saved_map = [];
            foreach ($known_map as $slug => $meta) {
                if (isset($by_key[$slug])) {
                    update_option('slq_cf_' . $slug, $by_key[$slug]);
                    $saved_map[$slug] = $by_key[$slug];
                    $slq_fields++; $found[] = $slug;
                } elseif (isset($by_label[strtolower($meta['label'])])) {
                    $id = $by_label[strtolower($meta['label'])];
                    update_option('slq_cf_' . $slug, $id);
                    $saved_map[$slug] = $id;
                    $slq_fields++; $found[] = $slug;
                }
            }
            if (!empty($saved_map)) set_transient('slq_ghl_field_map', $saved_map, HOUR_IN_SECONDS);

            foreach ($known as $k) {
                if (!in_array($k, $found, true)) {
                    $missing[] = $known_map[$k]['label'] . ' (' . $k . ')';
                }
            }
        }
        wp_send_json_success([
            'location'        => $body['location']['name'] ?? 'Unknown',
            'fields_total'    => $field_count,
            'fields_mapped'   => $slq_fields,
            'fields_expected' => count(slq_field_list()),
            'missing'         => $missing,
        ]);
    }
    wp_send_json_error(['message' => 'GHL returned HTTP ' . $code . ': ' . ($body['message'] ?? 'Unknown error')]);
});

/* ─── Admin AJAX: force update check ──────────────────────── */

add_action('wp_ajax_slq_force_update_check', function () {
    check_ajax_referer('slq_force_update', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    delete_transient('slq_github_release');
    delete_site_transient('update_plugins');
    wp_send_json_success(['message' => 'Cache cleared. Go to Dashboard → Updates and click "Check Again".']);
});

/* ─── Helper: GHL headers ──────────────────────────────────── */

function slq_ghl_headers(): array {
    return [
        'Authorization' => 'Bearer ' . get_option('slq_ghl_api_key', ''),
        'Version'       => '2021-07-28',
        'Content-Type'  => 'application/json',
    ];
}

/* ─── Helper: resolve GHL custom field ID by key ──────────── */

function slq_resolve_field_id(string $key): string {
    // Use saved option first — fastest path
    $saved = get_option('slq_cf_' . $key, '');
    if ($saved) return $saved;

    // Try cached map (keyed by our internal slug)
    $map = get_transient('slq_ghl_field_map');
    if (is_array($map) && isset($map[$key])) {
        return $map[$key];
    }

    // Fetch from GHL — only runs once per hour
    $api_key     = get_option('slq_ghl_api_key', '');
    $location_id = get_option('slq_ghl_location_id', '');
    if (!$api_key || !$location_id) return '';

    $r = wp_remote_get('https://services.leadconnectorhq.com/locations/' . $location_id . '/customFields', [
        'headers' => slq_ghl_headers(),
        'timeout' => 10,
    ]);

    if (is_wp_error($r)) {
        error_log('[SLQ] Field map fetch error: ' . $r->get_error_message());
        return '';
    }
    if (wp_remote_retrieve_response_code($r) >= 400) {
        error_log('[SLQ] Field map fetch HTTP ' . wp_remote_retrieve_response_code($r) . ' — check API key');
        return '';
    }

    $ghl_fields = json_decode(wp_remote_retrieve_body($r), true)['customFields'] ?? [];

    // Build lookup tables: by GHL fieldKey (bare) and by lowercase label
    $by_key   = [];
    $by_label = [];
    foreach ($ghl_fields as $f) {
        $bare = strtolower(preg_replace('/^contact\./', '', $f['fieldKey'] ?? ''));
        if ($bare && !empty($f['id']))          $by_key[$bare]   = $f['id'];
        $lbl = strtolower(trim($f['name'] ?? ''));
        if ($lbl && !empty($f['id']))           $by_label[$lbl]  = $f['id'];
    }

    // Map our internal slugs → IDs: try fieldKey match first, then label match
    $map = [];
    foreach (slq_field_list() as $slug => $meta) {
        if (isset($by_key[$slug])) {
            $map[$slug] = $by_key[$slug];
        } elseif (isset($by_label[strtolower($meta['label'])])) {
            $map[$slug] = $by_label[strtolower($meta['label'])];
        }
        // Persist individually so future requests skip the API call
        if (isset($map[$slug])) {
            update_option('slq_cf_' . $slug, $map[$slug]);
        }
    }

    if (!empty($map)) {
        set_transient('slq_ghl_field_map', $map, HOUR_IN_SECONDS);
    }

    return $map[$key] ?? '';
}

/* ─── Helper: get both stored folder IDs ──────────────────── */

function slq_get_folder_ids(): array {
    return [
        'General Questionnaire'   => get_option('slq_folder_id_general',  ''),
        'Berlin Questionnaire'    => get_option('slq_folder_id_berlin',   ''),
        'STOP-Bang Questionnaire' => get_option('slq_folder_id_stopbang', ''),
    ];
}

/* ─── Admin AJAX: create both checker fields ──────────────── */

add_action('wp_ajax_slq_create_checker_field', function () {
    check_ajax_referer('slq_ghl', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    $api_key     = get_option('slq_ghl_api_key', '');
    $location_id = get_option('slq_ghl_location_id', '');
    if (!$api_key || !$location_id) wp_send_json_error(['message' => 'Save API Key and Location ID first.']);
    $base    = 'https://services.leadconnectorhq.com';
    $headers = slq_ghl_headers();
    $checkers = [
        ['name' => 'General Questionnaire Checker',  'fieldKey' => 'slq_checker_general'],
        ['name' => 'Berlin Questionnaire Checker',   'fieldKey' => 'slq_checker_berlin'],
        ['name' => 'STOP-Bang Questionnaire Checker','fieldKey' => 'slq_checker_stopbang'],
    ];
    $created = []; $errors = [];
    foreach ($checkers as $c) {
        $r    = wp_remote_post("{$base}/locations/{$location_id}/customFields", [
            'headers' => $headers, 'timeout' => 15,
            'body'    => wp_json_encode(['name' => $c['name'], 'fieldKey' => $c['fieldKey'], 'dataType' => 'TEXT', 'position' => 0]),
        ]);
        $code = is_wp_error($r) ? 0 : wp_remote_retrieve_response_code($r);
        if ($code >= 200 && $code < 300 || $code === 400) { $created[] = $c['name']; }
        else { $errors[] = $c['name'] . ': ' . (is_wp_error($r) ? $r->get_error_message() : (json_decode(wp_remote_retrieve_body($r), true)['message'] ?? 'HTTP ' . $code)); }
    }
    wp_send_json_success(['created' => $created, 'errors' => $errors]);
});

/* ─── Admin AJAX: delete both checker fields ──────────────── */

add_action('wp_ajax_slq_delete_checker_field', function () {
    check_ajax_referer('slq_ghl', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    $api_key     = get_option('slq_ghl_api_key', '');
    $location_id = get_option('slq_ghl_location_id', '');
    if (!$api_key || !$location_id) wp_send_json_error(['message' => 'Save API Key and Location ID first.']);
    $base    = 'https://services.leadconnectorhq.com';
    $headers = slq_ghl_headers();
    $checker_keys = ['slq_checker_general', 'slq_checker_berlin', 'slq_checker_stopbang'];
    $fr = wp_remote_get("{$base}/locations/{$location_id}/customFields", ['headers' => $headers, 'timeout' => 15]);
    if (is_wp_error($fr)) wp_send_json_error(['message' => $fr->get_error_message()]);
    $deleted = []; $errors = [];
    foreach (json_decode(wp_remote_retrieve_body($fr), true)['customFields'] ?? [] as $f) {
        $bare = strtolower(preg_replace('/^contact\./', '', $f['fieldKey'] ?? ''));
        if (!in_array($bare, $checker_keys, true)) continue;
        $dr   = wp_remote_request("{$base}/locations/{$location_id}/customFields/{$f['id']}", [
            'method' => 'DELETE', 'headers' => $headers, 'timeout' => 15,
        ]);
        $code = is_wp_error($dr) ? 0 : wp_remote_retrieve_response_code($dr);
        if ($code >= 200 && $code < 300) { $deleted[] = $bare; } else { $errors[] = $bare . ': HTTP ' . $code; }
    }
    wp_send_json_success(['deleted' => $deleted, 'errors' => $errors]);
});

/* ─── Admin AJAX: auto-detect both folder IDs ─────────────── */

add_action('wp_ajax_slq_detect_folder_id', function () {
    check_ajax_referer('slq_ghl', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    $api_key     = get_option('slq_ghl_api_key', '');
    $location_id = get_option('slq_ghl_location_id', '');
    if (!$api_key || !$location_id) wp_send_json_error(['message' => 'Save API Key and Location ID first.']);
    $base = 'https://services.leadconnectorhq.com';
    $fr   = wp_remote_get("{$base}/locations/{$location_id}/customFields", ['headers' => slq_ghl_headers(), 'timeout' => 15]);
    if (is_wp_error($fr)) wp_send_json_error(['message' => $fr->get_error_message()]);
    $fields   = json_decode(wp_remote_retrieve_body($fr), true)['customFields'] ?? [];
    $general  = ''; $berlin = ''; $stopbang = '';
    foreach ($fields as $f) {
        $bare = strtolower(preg_replace('/^contact\./', '', $f['fieldKey'] ?? ''));
        if ($bare === 'slq_checker_general'  && !empty($f['parentId'])) $general  = $f['parentId'];
        if ($bare === 'slq_checker_berlin'   && !empty($f['parentId'])) $berlin   = $f['parentId'];
        if ($bare === 'slq_checker_stopbang' && !empty($f['parentId'])) $stopbang = $f['parentId'];
    }
    if (!$general || !$berlin || !$stopbang) {
        // Fallback: match known fields by their folder
        foreach ($fields as $f) {
            if (empty($f['parentId'])) continue;
            $bare = strtolower(preg_replace('/^contact\./', '', $f['fieldKey'] ?? ''));
            $fl   = slq_field_list();
            if (!$general  && isset($fl[$bare]) && $fl[$bare]['folder'] === 'General Questionnaire')   $general  = $f['parentId'];
            if (!$berlin   && isset($fl[$bare]) && $fl[$bare]['folder'] === 'Berlin Questionnaire')    $berlin   = $f['parentId'];
            if (!$stopbang && isset($fl[$bare]) && $fl[$bare]['folder'] === 'STOP-Bang Questionnaire') $stopbang = $f['parentId'];
        }
    }
    if (!$general && !$berlin && !$stopbang) {
        wp_send_json_error(['message' => 'No checker fields found in folders — drag each into its matching folder first, then retry.']);
    }
    if ($general)  update_option('slq_folder_id_general',  $general);
    if ($berlin)   update_option('slq_folder_id_berlin',   $berlin);
    if ($stopbang) update_option('slq_folder_id_stopbang', $stopbang);
    wp_send_json_success(['general' => $general, 'berlin' => $berlin, 'stopbang' => $stopbang]);
});

/* ─── Admin AJAX: save folder IDs manually ────────────────── */

add_action('wp_ajax_slq_save_folder_id', function () {
    check_ajax_referer('slq_ghl', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    $general  = sanitize_text_field($_POST['folder_general']  ?? '');
    $berlin   = sanitize_text_field($_POST['folder_berlin']   ?? '');
    $stopbang = sanitize_text_field($_POST['folder_stopbang'] ?? '');
    update_option('slq_folder_id_general',  $general);
    update_option('slq_folder_id_berlin',   $berlin);
    update_option('slq_folder_id_stopbang', $stopbang);
    wp_send_json_success(['general' => $general, 'berlin' => $berlin, 'stopbang' => $stopbang]);
});

/* ─── Admin AJAX: create + move all fields ─────────────────── */

add_action('wp_ajax_slq_setup_all', function () {
    check_ajax_referer('slq_ghl', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    $api_key     = get_option('slq_ghl_api_key', '');
    $location_id = get_option('slq_ghl_location_id', '');
    if (!$api_key || !$location_id) {
        wp_send_json_error(['message' => 'Save your API Key and Location ID first.']); return;
    }
    $folder_ids = slq_get_folder_ids(); // 'Berlin Questionnaire' => id, 'STOP-Bang Questionnaire' => id
    $base       = 'https://services.leadconnectorhq.com';
    $headers    = slq_ghl_headers();

    // ── Fetch all existing custom fields ──
    $r_list   = wp_remote_get("{$base}/locations/{$location_id}/customFields", ['headers' => $headers, 'timeout' => 15]);
    $b_list   = is_wp_error($r_list) ? [] : (json_decode(wp_remote_retrieve_body($r_list), true) ?? []);
    $existing = [];
    foreach ($b_list['customFields'] ?? [] as $f) {
        $bare = strtolower(preg_replace('/^contact\./', '', $f['fieldKey'] ?? ''));
        if ($bare) $existing[$bare] = $f['id'];
    }

    $created = 0; $skipped = 0; $moved = 0; $errors = [];

    foreach (slq_field_list() as $slug => $meta) {
        $folder_id = $folder_ids[$meta['folder']] ?? '';
        $saved_id  = get_option('slq_cf_' . $slug, '');

        if (!$saved_id && isset($existing[$slug])) {
            $saved_id = $existing[$slug];
            update_option('slq_cf_' . $slug, $saved_id);
            $skipped++;
        } elseif ($saved_id) {
            $skipped++;
        } else {
            $payload = ['name' => $meta['label'], 'fieldKey' => $slug, 'dataType' => 'TEXT', 'position' => 0];
            if ($folder_id) $payload['parentId'] = $folder_id;
            $resp = wp_remote_post("{$base}/locations/{$location_id}/customFields", [
                'headers' => $headers, 'timeout' => 15,
                'body'    => wp_json_encode($payload),
            ]);
            if (is_wp_error($resp)) { $errors[] = $meta['label'] . ': ' . $resp->get_error_message(); continue; }
            $body = json_decode(wp_remote_retrieve_body($resp), true) ?? [];
            $fid  = $body['customField']['id'] ?? $body['id'] ?? '';
            if ($fid) {
                update_option('slq_cf_' . $slug, $fid);
                $saved_id = $fid;
                $created++;
            } else {
                $errors[] = $meta['label'] . ': no ID returned';
                continue;
            }
        }

        if ($folder_id && $saved_id) {
            wp_remote_request("{$base}/locations/{$location_id}/customFields/{$saved_id}", [
                'method' => 'PUT', 'headers' => $headers, 'timeout' => 15,
                'body'   => wp_json_encode(['parentId' => $folder_id]),
            ]);
            $moved++;
        }
    }

    // ── Verification GET ──
    $r_verify = wp_remote_get("{$base}/locations/{$location_id}/customFields", ['headers' => $headers, 'timeout' => 15]);
    if (!is_wp_error($r_verify)) {
        $b_verify = json_decode(wp_remote_retrieve_body($r_verify), true) ?? [];
        foreach ($b_verify['customFields'] ?? [] as $f) {
            $bare = strtolower(preg_replace('/^contact\./', '', $f['fieldKey'] ?? ''));
            if (isset(slq_field_list()[$bare]) && !get_option('slq_cf_' . $bare)) {
                update_option('slq_cf_' . $bare, $f['id']);
            }
        }
    }

    wp_send_json_success(['created' => $created, 'skipped' => $skipped, 'moved' => $moved, 'errors' => $errors]);
});

/* ─── Settings page ────────────────────────────────────────── */

function slq_render_settings() {
    if (isset($_POST['slq_nonce']) && wp_verify_nonce($_POST['slq_nonce'], 'slq_save')) {
        update_option('slq_ghl_api_key',     sanitize_text_field($_POST['slq_ghl_api_key']     ?? ''));
        update_option('slq_ghl_location_id', sanitize_text_field($_POST['slq_ghl_location_id'] ?? ''));
        update_option('slq_booking_url',     sanitize_text_field($_POST['slq_booking_url']     ?? '/thank-you'));
        update_option('slq_primary_color',   sanitize_hex_color($_POST['slq_primary_color']    ?? '#2d6a5a') ?: '#2d6a5a');
        update_option('slq_notify_email',    sanitize_email($_POST['slq_notify_email']          ?? ''));
        foreach (array_keys(slq_field_list()) as $key) {
            update_option('slq_cf_' . $key, sanitize_text_field($_POST['slq_cf_' . $key] ?? ''));
        }
        echo '<div class="notice notice-success is-dismissible"><p><strong>Settings saved.</strong></p></div>';
    }

    $api_key     = get_option('slq_ghl_api_key',     '');
    $location_id = get_option('slq_ghl_location_id', '');
    $booking_url = get_option('slq_booking_url',     '/thank-you');
    $primary     = get_option('slq_primary_color',   '#2d6a5a');

    $groups = [];
    foreach (slq_field_list() as $key => $meta) {
        $groups[$meta['group']][$key] = $meta;
    }
    ?>
    <style>
        .slq-wrap       { max-width:900px; margin-top:20px; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
        .slq-card       { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:24px 28px; margin-bottom:22px; box-shadow:0 1px 3px rgba(0,0,0,.04); }
        .slq-card-title { margin:0 0 4px; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#64748b; padding-bottom:14px; border-bottom:1px solid #f1f5f9; }
        .slq-sc-row     { display:flex; align-items:center; gap:12px; background:#f8fafb; border:1px solid #e2e8f0; border-radius:7px; padding:13px 16px; font-family:monospace; font-size:15px; font-weight:700; color:#1e40af; margin-bottom:10px; }
        .slq-sc-row:last-child { margin-bottom:0; }
        .slq-sc-row button { flex-shrink:0; }
        .slq-ft         { width:100%; border-collapse:collapse; margin-top:16px; }
        .slq-ft th      { text-align:left; padding:7px 12px; background:#f8fafb; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#64748b; border-bottom:2px solid #e2e8f0; }
        .slq-ft td      { padding:8px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; font-size:13px; }
        .slq-ft td:first-child { width:260px; font-weight:500; color:#1e293b; }
        .slq-ft td code { background:#f1f5f9; padding:2px 7px; border-radius:4px; font-size:11px; color:#475569; }
        .slq-ft input   { width:100%; max-width:320px; }
        .slq-auto-tag   { display:inline-block; background:#dcfce7; color:#166534; font-size:10px; font-weight:700; padding:2px 7px; border-radius:99px; margin-left:6px; text-transform:uppercase; letter-spacing:.04em; }
        .slq-form-badge { display:inline-block; background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; font-size:10px; font-weight:700; padding:2px 8px; border-radius:99px; margin-left:8px; text-transform:uppercase; letter-spacing:.04em; }
        .slq-ghl-row    { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:12px; }
        .slq-log        { font-size:12px; color:#64748b; margin-top:8px; min-height:18px; }
    </style>

    <div class="slq-wrap">
        <h1 style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
            <span style="background:#2d6a5a;color:#fff;width:34px;height:34px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">🔬</span>
            Sleep Apnea Screener
        </h1>
        <p style="color:#64748b;margin:0 0 22px">Configure both questionnaires, appearance, GHL connection, and custom field mapping.</p>

        <form method="post">
            <?php wp_nonce_field('slq_save', 'slq_nonce'); ?>

            <!-- ── Shortcodes ── -->
            <div class="slq-card">
                <p class="slq-card-title">Shortcodes</p>
                <p style="margin:0 0 14px;color:#475569;font-size:13px">Paste either shortcode into any page or post.</p>
                <div class="slq-sc-row">
                    <span>[berlin_questionnaire]</span>
                    <button type="button" class="button"
                            onclick="navigator.clipboard.writeText('[berlin_questionnaire]');this.textContent='Copied ✓';setTimeout(()=>this.textContent='Copy',2000)">Copy</button>
                    <span class="slq-form-badge">Berlin Sleep Apnea</span>
                </div>
                <div class="slq-sc-row">
                    <span>[stopbang_questionnaire]</span>
                    <button type="button" class="button"
                            onclick="navigator.clipboard.writeText('[stopbang_questionnaire]');this.textContent='Copied ✓';setTimeout(()=>this.textContent='Copy',2000)">Copy</button>
                    <span class="slq-form-badge">STOP-Bang</span>
                </div>
            </div>

            <!-- ── Appearance ── -->
            <div class="slq-card">
                <p class="slq-card-title">Appearance</p>
                <table class="form-table" style="margin-top:0">
                    <tr>
                        <th style="width:180px"><label for="slq_primary_color">Primary Colour</label></th>
                        <td>
                            <input type="color" id="slq_primary_color" name="slq_primary_color"
                                   value="<?php echo esc_attr($primary); ?>"
                                   style="height:38px;width:60px;cursor:pointer;border:1px solid #e2e8f0;border-radius:6px;padding:2px" />
                            <span style="margin-left:8px;font-size:13px;color:#475569">Applied to both questionnaires — header, buttons, progress bar, and selections.</span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ── GHL Connection ── -->
            <div class="slq-card">
                <p class="slq-card-title">GoHighLevel Connection</p>
                <table class="form-table" style="margin-top:0">
                    <tr>
                        <th style="width:180px"><label for="slq_ghl_api_key">API Key</label></th>
                        <td>
                            <input type="password" id="slq_ghl_api_key" name="slq_ghl_api_key"
                                   value="<?php echo esc_attr($api_key); ?>" class="regular-text"
                                   placeholder="eyJ…" autocomplete="off" />
                            <p class="description">GHL → Settings → Private Integrations → Create Key → enable <strong>Contacts: Read + Write</strong> and <strong>Custom Fields: Read + Write</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="slq_ghl_location_id">Location ID</label></th>
                        <td>
                            <input type="text" id="slq_ghl_location_id" name="slq_ghl_location_id"
                                   value="<?php echo esc_attr($location_id); ?>" class="regular-text"
                                   placeholder="xxxxxxxxxxxxxxxxxxxxxxxx" />
                            <p class="description">GHL → Settings → Business Info → Location ID</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="slq_booking_url">Thank You Page URL</label></th>
                        <td>
                            <input type="text" id="slq_booking_url" name="slq_booking_url"
                                   value="<?php echo esc_attr($booking_url); ?>" class="regular-text"
                                   placeholder="/thank-you" />
                            <p class="description">After completing either questionnaire, the CTA button sends patients here.</p>
                        </td>
                    </tr>
                </table>
                <div style="margin-top:16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                    <button type="button" id="slq-test-ghl-btn" class="button">&#9654; Test GHL Connection</button>
                    <span id="slq-test-ghl-result" style="font-size:13px;color:#64748b"></span>
                </div>
            </div>

            <!-- ── GHL Field Setup ── -->
            <div class="slq-card">
                <p class="slq-card-title">GHL Field Setup</p>

                <!-- Folder IDs sub-section -->
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px 20px;margin-bottom:22px;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
                        <div>
                            <strong style="font-size:13px;color:#1e293b;">Folder IDs</strong>
                            <div style="margin-top:8px;font-size:11px;color:#64748b;line-height:1.9;">
                                <strong style="color:#0f172a;font-size:11.5px;">One-time setup — follow these steps in order:</strong><br>
                                <strong style="color:#2563eb;">Step 1.</strong> In GHL → Settings → Custom Fields → create 3 folders named exactly:&nbsp;<code style="background:#e2e8f0;padding:1px 5px;border-radius:3px;">General Questionnaire</code>&nbsp;<code style="background:#e2e8f0;padding:1px 5px;border-radius:3px;">Berlin Questionnaire</code>&nbsp;<code style="background:#e2e8f0;padding:1px 5px;border-radius:3px;">STOP-Bang Questionnaire</code><br>
                                <strong style="color:#2563eb;">Step 2.</strong> Click <strong>+ Create Checker Fields</strong> — 3 temporary marker fields will appear in GHL under Additional Info<br>
                                <strong style="color:#2563eb;">Step 3.</strong> In GHL, drag each checker field into its matching folder (names make it obvious)<br>
                                <strong style="color:#2563eb;">Step 4.</strong> Click <strong>Auto-detect</strong> — folder IDs are found and saved automatically<br>
                                <strong style="color:#2563eb;">Step 5.</strong> Click <strong>Create &amp; Move All Fields</strong> — all <?php echo count(slq_field_list()); ?> fields created and organised into their folders<br>
                                <strong style="color:#2563eb;">Step 6.</strong> Click <strong>Delete Checkers</strong> — temporary marker fields removed from GHL
                            </div>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end;">
                            <button type="button" id="slq-create-checker-btn" class="button button-primary" style="font-size:11px;padding:4px 14px;white-space:nowrap;">
                                &#43; Create Checker Fields
                            </button>
                            <button type="button" id="slq-detect-folder-btn" class="button" style="font-size:11px;padding:4px 14px;white-space:nowrap;">
                                &#128269; Auto-detect
                            </button>
                            <button type="button" id="slq-delete-checker-btn" class="button" style="font-size:11px;padding:4px 14px;white-space:nowrap;color:#dc2626;border-color:#fca5a5;">
                                &#128465; Delete Checkers
                            </button>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;" id="slq-folder-id-grid">
                        <?php
                        $saved_fids = slq_get_folder_ids();
                        foreach ($saved_fids as $fname => $fid):
                            $fkey = strtolower(str_replace([' ', '-'], '_', $fname));
                        ?>
                        <label style="font-size:12px;color:#374151;">
                            <?php echo esc_html($fname); ?>
                            <input type="text" id="slq-fid-<?php echo esc_attr($fkey); ?>" placeholder="folder ID…"
                                   value="<?php echo esc_attr($fid); ?>"
                                   style="display:block;width:100%;margin-top:4px;font-family:monospace;font-size:11px;padding:4px 7px;border:1px solid #cbd5e1;border-radius:4px;box-sizing:border-box;">
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top:12px;padding:10px 12px;background:#f1f5f9;border-radius:6px;font-size:11px;color:#374151;">
                        <strong>Paste GHL URL to extract ID:</strong>
                        <div style="display:flex;gap:8px;margin-top:6px;">
                            <input type="text" id="slq-folder-url-input" placeholder="Paste GHL URL here (e.g. …?folderId=AbCdEf…)" style="flex:1;font-size:11px;padding:4px 7px;border:1px solid #cbd5e1;border-radius:4px;">
                            <select id="slq-folder-url-target" style="font-size:11px;padding:4px 7px;border:1px solid #cbd5e1;border-radius:4px;">
                                <option value="general_questionnaire">General Questionnaire</option>
                                <option value="berlin_questionnaire">Berlin Questionnaire</option>
                                <option value="stop_bang_questionnaire">STOP-Bang Questionnaire</option>
                            </select>
                            <button type="button" id="slq-folder-url-btn" class="button" style="font-size:11px;padding:3px 10px;">Extract</button>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;margin-top:12px;">
                        <button type="button" id="slq-save-folder-id-btn" class="button button-secondary" style="font-size:12px;padding:4px 14px;">
                            &#10003; Save Folder IDs
                        </button>
                        <span id="slq-folder-status" style="font-size:11px;color:#6b7280;"></span>
                    </div>
                </div>

                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <button type="button" class="button button-primary" id="slq-setup-all-btn" style="height:36px;padding:0 20px">
                        Create &amp; Move All Fields
                    </button>
                    <span id="slq-setup-log" style="font-size:12px;color:#64748b;"></span>
                </div>
            </div>

            <!-- ── GHL Custom Field IDs ── -->
            <div class="slq-card">
                <p class="slq-card-title">GHL Custom Field IDs</p>
                <p style="margin:0;color:#475569;font-size:13px">
                    IDs are filled automatically by the one-click setup above.
                    Fields marked <span class="slq-auto-tag">Auto</span> are mapped automatically (Name, Email, Phone, Source, Tags).
                    Leave any field blank to skip it.
                </p>

                <table class="slq-ft" style="margin-top:18px">
                    <thead><tr><th>Field</th><th>Value sent</th><th>Mapping</th></tr></thead>
                    <tbody>
                        <tr><td>First Name</td><td><code>From full name</code></td><td><span class="slq-auto-tag">Auto</span></td></tr>
                        <tr><td>Last Name</td><td><code>From full name</code></td><td><span class="slq-auto-tag">Auto</span></td></tr>
                        <tr><td>Email</td><td><code>patient email</code></td><td><span class="slq-auto-tag">Auto</span></td></tr>
                        <tr><td>Phone</td><td><code>patient phone</code></td><td><span class="slq-auto-tag">Auto</span></td></tr>
                        <tr><td>Source</td><td><code>Berlin / STOP-Bang Sleep Screener</code></td><td><span class="slq-auto-tag">Auto</span></td></tr>
                        <tr><td>Tags</td><td><code>sleep-apnea-screening, berlin-high-risk, sb-high-risk…</code></td><td><span class="slq-auto-tag">Auto</span></td></tr>
                    </tbody>
                </table>

                <?php foreach ($groups as $group_name => $fields): ?>
                <table class="slq-ft" style="margin-top:16px">
                    <thead>
                        <tr><th colspan="3"><?php echo esc_html($group_name); ?></th></tr>
                        <tr><th>Field</th><th>Example value</th><th>GHL Custom Field ID</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($fields as $key => $meta): ?>
                        <tr>
                            <td><?php echo esc_html($meta['label']); ?></td>
                            <td><code><?php echo esc_html($meta['example']); ?></code></td>
                            <td>
                                <input type="text" name="slq_cf_<?php echo esc_attr($key); ?>"
                                       id="slq_cf_<?php echo esc_attr($key); ?>"
                                       value="<?php echo esc_attr(get_option('slq_cf_' . $key, '')); ?>"
                                       placeholder="Auto-filled or paste GHL field ID…" />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endforeach; ?>
            </div>

            <?php submit_button('Save Settings', 'primary large'); ?>
        </form>

        <!-- ── Force Update Check ── -->
        <div class="slq-card" style="margin-top:22px">
            <p class="slq-card-title">Plugin Updates</p>
            <p style="margin:0 0 14px;color:#475569;font-size:13px">
                WordPress caches update data for up to 12 hours. If a new release was pushed to GitHub and it isn't showing in
                <strong>Dashboard → Updates</strong>, click below to clear the cache.
            </p>
            <button type="button" id="slq-force-update-btn" class="button button-secondary">Force Update Check</button>
            <span id="slq-force-update-msg" style="margin-left:12px;font-size:13px;color:#475569"></span>
        </div>

        <script>
        (function () {
            var ghlNonce    = '<?php echo esc_js(wp_create_nonce('slq_ghl')); ?>';
            var updateNonce = '<?php echo esc_js(wp_create_nonce('slq_force_update')); ?>';

            function ghlPost(action, extra, cb) {
                var fd = new FormData();
                fd.append('action', action);
                fd.append('nonce', ghlNonce);
                if (extra) Object.keys(extra).forEach(function(k){ fd.append(k, extra[k]); });
                fetch(ajaxurl, { method: 'POST', body: fd })
                    .then(function(r){ return r.json(); })
                    .then(cb)
                    .catch(function(){ cb({ success: false, data: { message: 'Request failed.' } }); });
            }

            var fst = document.getElementById('slq-folder-status');

            // Test GHL Connection
            document.getElementById('slq-test-ghl-btn').addEventListener('click', function() {
                var btn = this, res = document.getElementById('slq-test-ghl-result');
                btn.disabled = true; res.textContent = 'Testing…'; res.style.color = '#6b7280';
                ghlPost('slq_test_ghl', {}, function(r) {
                    btn.disabled = false;
                    if (r.success) {
                        var d = r.data;
                        res.style.color = d.fields_mapped >= d.fields_expected ? '#16a34a' : '#d97706';
                        res.textContent = '✓ Connected to "' + d.location + '" — '
                            + d.fields_mapped + '/' + d.fields_expected + ' screener fields found in GHL'
                            + (d.missing && d.missing.length ? ' — Missing: ' + d.missing.join(', ') : '');
                    } else {
                        res.style.color = '#dc2626';
                        res.textContent = '✗ ' + (r.data && r.data.message || 'Error');
                    }
                });
            });

            // Save Folder IDs
            document.getElementById('slq-save-folder-id-btn').addEventListener('click', function() {
                var btn = this;
                btn.disabled = true; fst.textContent = 'Saving…'; fst.style.color = '#6b7280';
                ghlPost('slq_save_folder_id', {
                    folder_general:  document.getElementById('slq-fid-general_questionnaire').value.trim(),
                    folder_berlin:   document.getElementById('slq-fid-berlin_questionnaire').value.trim(),
                    folder_stopbang: document.getElementById('slq-fid-stop_bang_questionnaire').value.trim(),
                }, function(res) {
                    btn.disabled = false;
                    fst.textContent = res.success ? '✓ Saved' : '✗ ' + (res.data && res.data.message || 'Error');
                    fst.style.color  = res.success ? '#16a34a' : '#dc2626';
                });
            });

            // URL Extractor
            document.getElementById('slq-folder-url-btn').addEventListener('click', function() {
                var url    = document.getElementById('slq-folder-url-input').value.trim();
                var target = document.getElementById('slq-folder-url-target').value;
                var m = url.match(/[?#&\/]folderId[=\/]([A-Za-z0-9_-]+)/i) ||
                        url.match(/folder[_-]?id[=:\/]([A-Za-z0-9_-]+)/i) ||
                        url.match(/\/([A-Za-z0-9]{15,25})(?:[/?#]|$)/);
                if (!m) { fst.textContent = '✗ No folder ID found in URL'; fst.style.color = '#dc2626'; return; }
                var el = document.getElementById('slq-fid-' + target);
                if (el) { el.value = m[1]; fst.textContent = '✓ Extracted: ' + m[1]; fst.style.color = '#16a34a'; }
                document.getElementById('slq-folder-url-input').value = '';
            });

            // Create Checker Fields
            document.getElementById('slq-create-checker-btn').addEventListener('click', function() {
                var btn = this;
                btn.disabled = true; fst.textContent = 'Creating checker fields…'; fst.style.color = '#6b7280';
                ghlPost('slq_create_checker_field', {}, function(res) {
                    btn.disabled = false;
                    if (res.success) {
                        var d = res.data;
                        fst.textContent = '✓ Created ' + d.created.length + ' of 3 checker field(s) in GHL. Drag each into its matching folder, then click Auto-detect.' + (d.errors.length ? ' Errors: ' + d.errors.join(', ') : '');
                        fst.style.color = d.errors.length ? '#d97706' : '#16a34a';
                    } else { fst.textContent = '✗ ' + (res.data && res.data.message || 'Error'); fst.style.color = '#dc2626'; }
                });
            });

            // Delete Checker Fields
            document.getElementById('slq-delete-checker-btn').addEventListener('click', function() {
                var btn = this;
                btn.disabled = true; fst.textContent = 'Deleting checker fields…'; fst.style.color = '#6b7280';
                ghlPost('slq_delete_checker_field', {}, function(res) {
                    btn.disabled = false;
                    if (res.success) {
                        var d = res.data;
                        fst.textContent = d.deleted.length ? '✓ Deleted ' + d.deleted.length + ' checker field(s).' + (d.errors.length ? ' Errors: ' + d.errors.join(', ') : '') : '⚠ Checker fields not found in GHL (may already be deleted).';
                        fst.style.color = d.errors.length ? '#d97706' : '#16a34a';
                    } else { fst.textContent = '✗ ' + (res.data && res.data.message || 'Error'); fst.style.color = '#dc2626'; }
                });
            });

            // Auto-detect folder IDs
            document.getElementById('slq-detect-folder-btn').addEventListener('click', function() {
                var btn = this;
                btn.disabled = true; fst.textContent = 'Detecting…'; fst.style.color = '#6b7280';
                ghlPost('slq_detect_folder_id', {}, function(res) {
                    btn.disabled = false;
                    if (res.success) {
                        var d = res.data;
                        if (d.general)  document.getElementById('slq-fid-general_questionnaire').value   = d.general;
                        if (d.berlin)   document.getElementById('slq-fid-berlin_questionnaire').value    = d.berlin;
                        if (d.stopbang) document.getElementById('slq-fid-stop_bang_questionnaire').value = d.stopbang;
                        var found = (d.general ? 1 : 0) + (d.berlin ? 1 : 0) + (d.stopbang ? 1 : 0);
                        fst.textContent = '✓ Detected ' + found + ' of 3 folder IDs — saved automatically.';
                        fst.style.color = found === 3 ? '#16a34a' : '#d97706';
                    } else { fst.textContent = '✗ ' + (res.data && res.data.message || 'Error'); fst.style.color = '#dc2626'; }
                });
            });

            // Create & Move All Fields
            document.getElementById('slq-setup-all-btn').addEventListener('click', function() {
                var btn = this, log = document.getElementById('slq-setup-log');
                btn.disabled = true; btn.textContent = 'Working…';
                log.style.color = '#475569';
                log.textContent = 'Creating and moving fields — this may take 20–30 seconds…';
                ghlPost('slq_setup_all', {}, function(res) {
                    btn.disabled = false; btn.textContent = 'Create & Move All Fields';
                    if (!res.success) {
                        log.style.color = '#991b1b';
                        log.textContent = '✗ ' + (res.data && res.data.message || 'Error');
                        return;
                    }
                    var d = res.data;
                    log.style.color = '#166534';
                    log.textContent = '✓ Done! Created: ' + d.created + '  Already existed: ' + d.skipped
                        + '  In folder: ' + d.moved
                        + (d.errors && d.errors.length ? '  Errors: ' + d.errors.join(', ') : '')
                        + ' — Reload the page to see the saved IDs.';
                });
            });

            // Force Update Check
            document.getElementById('slq-force-update-btn').addEventListener('click', function() {
                var btn = this, msg = document.getElementById('slq-force-update-msg');
                btn.disabled = true; btn.textContent = 'Checking…'; msg.textContent = '';
                var fd = new FormData();
                fd.append('action', 'slq_force_update_check');
                fd.append('nonce', updateNonce);
                fetch(ajaxurl, { method: 'POST', body: fd })
                    .then(function(r){ return r.json(); })
                    .then(function(res) {
                        btn.disabled = false; btn.textContent = 'Force Update Check';
                        if (res.success) { msg.style.color = '#166534'; msg.textContent = res.data.message; }
                        else             { msg.style.color = '#991b1b'; msg.textContent = 'Error — try again.'; }
                    })
                    .catch(function() {
                        btn.disabled = false; btn.textContent = 'Force Update Check';
                        msg.style.color = '#991b1b'; msg.textContent = 'Request failed.';
                    });
            });
        })();
        </script>
    </div>
    <?php
}

/* ═══════════════════════════════════════════════════════════ */
/* BERLIN QUESTIONNAIRE                                        */
/* ═══════════════════════════════════════════════════════════ */

add_shortcode('berlin_questionnaire', function () {
    wp_enqueue_style('intl-tel-input', 'https://cdn.jsdelivr.net/npm/intl-tel-input@23/build/css/intlTelInput.css', [], '23');
    wp_enqueue_script('intl-tel-input', 'https://cdn.jsdelivr.net/npm/intl-tel-input@23/build/js/intlTelInput.min.js', [], '23', true);
    wp_enqueue_style('slq-berlin',  SLQ_URL . 'assets/berlin-style.css',  [], SLQ_VERSION);
    wp_enqueue_script('slq-berlin', SLQ_URL . 'assets/berlin-script.js', ['intl-tel-input'], SLQ_VERSION, true);
    wp_localize_script('slq-berlin', 'BSQ', [
        'ajax_url'    => admin_url('admin-ajax.php'),
        'nonce'       => wp_create_nonce('bsq_submit'),
        'booking_url' => get_option('slq_booking_url', '/book-appointment'),
    ]);
    $primary = get_option('slq_primary_color', '#2d6a5a');
    ob_start();
    echo '<style>#bsq-wrap{' . slq_css_vars($primary, 'bsq') . '}</style>';
    include SLQ_DIR . 'templates/berlin.php';
    return ob_get_clean();
});

add_action('wp_ajax_bsq_submit',        'slq_berlin_submit');
add_action('wp_ajax_nopriv_bsq_submit', 'slq_berlin_submit');

function slq_berlin_submit() {
    check_ajax_referer('bsq_submit', 'nonce');
    $raw = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
    $d   = array_map('sanitize_text_field', $raw);
    if (!empty($d['_hp']))                     { wp_send_json_success([]); return; }
    if (intval($d['_elapsed'] ?? 0) < 3)        { wp_send_json_success([]); return; }
    $score = slq_berlin_score($d);
    slq_save_entry('berlin', $d['full_name'] ?? '', $d['email'] ?? '', $d['phone'] ?? '', $score['risk_level'], $score, $d);
    if (get_option('slq_ghl_api_key') && get_option('slq_ghl_location_id')) {
        slq_berlin_ghl($d, $score);
    }
    wp_send_json_success($score);
}

function slq_berlin_score(array $d): array {
    $c1 = 0;
    if (($d['q2'] ?? '') === 'yes')                                              $c1 += 1;
    if (in_array($d['q3'] ?? '', ['louder_than_talking','very_loud'], true))     $c1 += 1;
    if (in_array($d['q4'] ?? '', ['nearly_every_day','3_4_times'], true))        $c1 += 1;
    if (($d['q5'] ?? '') === 'yes')                                              $c1 += 1;
    if (in_array($d['q6'] ?? '', ['nearly_every_day','3_4_times'], true))        $c1 += 2;

    $c2 = 0;
    if (in_array($d['q7'] ?? '', ['nearly_every_day','3_4_times'], true))        $c2 += 1;
    if (in_array($d['q8'] ?? '', ['nearly_every_day','3_4_times'], true))        $c2 += 1;
    if (($d['q9'] ?? '') === 'yes')                                              $c2 += 1;

    $bmi = 0.0;
    $wt  = floatval($d['weight_lbs'] ?? 0);
    $ht  = floatval($d['height_in']  ?? 0);
    if ($wt > 0 && $ht > 0) $bmi = ($wt / ($ht * $ht)) * 703;

    $c3_pos = (($d['q10'] ?? '') === 'yes' || $bmi > 30);
    $pos = ($c1 >= 2 ? 1 : 0) + ($c2 >= 2 ? 1 : 0) + ($c3_pos ? 1 : 0);

    return [
        'cat1_score'     => $c1,
        'cat1_positive'  => $c1 >= 2,
        'cat2_score'     => $c2,
        'cat2_positive'  => $c2 >= 2,
        'cat3_positive'  => $c3_pos,
        'bmi'            => round($bmi, 1),
        'pos_categories' => $pos,
        'risk_level'     => $pos >= 2 ? 'High Risk' : 'Low Risk',
    ];
}

function slq_berlin_ghl(array $d, array $score): void {
    $api_key     = get_option('slq_ghl_api_key', '');
    $location_id = get_option('slq_ghl_location_id', '');
    if (!$api_key || !$location_id) return;

    $parts = explode(' ', trim($d['full_name'] ?? ''), 2);

    $field_map = [
        'age'              => $d['age']              ?? '',
        'gender'           => $d['gender']           ?? '',
        'height'           => $d['height_display']   ?? '',
        'weight'           => $d['weight_lbs']       ?? '',
        'bmi'              => (string) $score['bmi'],
        'b_snores'         => $d['q2']               ?? '',
        'b_snore_volume'   => $d['q3']               ?? '',
        'b_snore_freq'     => $d['q4']               ?? '',
        'b_bothers_others' => $d['q5']               ?? '',
        'b_stop_breathing' => $d['q6']               ?? '',
        'b_tired_sleep'    => $d['q7']               ?? '',
        'b_tired_day'      => $d['q8']               ?? '',
        'b_fall_asleep'    => $d['q9']               ?? '',
        'b_blood_pressure' => $d['q10']              ?? '',
        'b_risk_level'     => $score['risk_level'],
        'b_pos_categories' => (string) $score['pos_categories'],
        'b_cat1_positive'  => $score['cat1_positive'] ? 'Yes' : 'No',
        'b_cat2_positive'  => $score['cat2_positive'] ? 'Yes' : 'No',
        'b_cat3_positive'  => $score['cat3_positive'] ? 'Yes' : 'No',
    ];

    $custom_fields = [];
    foreach ($field_map as $key => $value) {
        $fid = slq_resolve_field_id($key);
        if ($fid && $value !== '') $custom_fields[] = ['id' => $fid, 'field_value' => (string)$value];
    }

    $payload = [
        'firstName'  => $parts[0] ?? '',
        'lastName'   => $parts[1] ?? '',
        'email'      => $d['email'] ?? '',
        'phone'      => $d['phone'] ?? '',
        'locationId' => $location_id,
        'source'     => 'Berlin Sleep Screener',
        'tags'       => ['berlin-questionnaire'],
    ];
    if (!empty($custom_fields)) $payload['customFields'] = $custom_fields;

    $resp = wp_remote_post('https://services.leadconnectorhq.com/contacts/', [
        'headers' => ['Authorization' => 'Bearer ' . $api_key, 'Version' => '2021-07-28', 'Content-Type' => 'application/json'],
        'body'    => wp_json_encode($payload),
        'timeout' => 15,
    ]);
    if (is_wp_error($resp)) {
        error_log('[SLQ Berlin] GHL error: ' . $resp->get_error_message());
    } elseif (wp_remote_retrieve_response_code($resp) >= 400) {
        error_log('[SLQ Berlin] GHL HTTP ' . wp_remote_retrieve_response_code($resp) . ': ' . wp_remote_retrieve_body($resp));
    }
}

/* ═══════════════════════════════════════════════════════════ */
/* STOP-BANG QUESTIONNAIRE                                     */
/* ═══════════════════════════════════════════════════════════ */

add_shortcode('stopbang_questionnaire', function () {
    wp_enqueue_style('intl-tel-input', 'https://cdn.jsdelivr.net/npm/intl-tel-input@23/build/css/intlTelInput.css', [], '23');
    wp_enqueue_script('intl-tel-input', 'https://cdn.jsdelivr.net/npm/intl-tel-input@23/build/js/intlTelInput.min.js', [], '23', true);
    wp_enqueue_style('slq-stopbang',  SLQ_URL . 'assets/stopbang-style.css',  [], SLQ_VERSION);
    wp_enqueue_script('slq-stopbang', SLQ_URL . 'assets/stopbang-script.js', ['intl-tel-input'], SLQ_VERSION, true);
    wp_localize_script('slq-stopbang', 'SBQ', [
        'ajax_url'    => admin_url('admin-ajax.php'),
        'nonce'       => wp_create_nonce('sbq_submit'),
        'booking_url' => get_option('slq_booking_url', '/book-appointment'),
    ]);
    $primary = get_option('slq_primary_color', '#2d6a5a');
    ob_start();
    echo '<style>#sbq-wrap{' . slq_css_vars($primary, 'sbq') . '}</style>';
    include SLQ_DIR . 'templates/stopbang.php';
    return ob_get_clean();
});

add_action('wp_ajax_sbq_submit',        'slq_stopbang_submit');
add_action('wp_ajax_nopriv_sbq_submit', 'slq_stopbang_submit');

function slq_stopbang_submit() {
    check_ajax_referer('sbq_submit', 'nonce');
    $raw = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
    $d   = array_map('sanitize_text_field', $raw);
    if (!empty($d['_hp']))               { wp_send_json_success([]); return; }
    if (intval($d['_elapsed'] ?? 0) < 3) { wp_send_json_success([]); return; }
    $score = slq_stopbang_score($d);
    slq_save_entry('stopbang', $d['full_name'] ?? '', $d['email'] ?? '', $d['phone'] ?? '', $score['risk'], $score, $d);
    if (get_option('slq_ghl_api_key') && get_option('slq_ghl_location_id')) {
        slq_stopbang_ghl($d, $score);
    }
    wp_send_json_success($score);
}

function slq_stopbang_score(array $d): array {
    $s = ($d['snoring']  ?? '') === 'yes' ? 1 : 0;
    $t = ($d['tired']    ?? '') === 'yes' ? 1 : 0;
    $o = ($d['observed'] ?? '') === 'yes' ? 1 : 0;
    $p = ($d['pressure'] ?? '') === 'yes' ? 1 : 0;

    $bmi = (float)($d['bmi'] ?? 0);
    $b   = $bmi > 35 ? 1 : 0;
    $age = (int)($d['age'] ?? 0);
    $a   = $age > 50 ? 1 : 0;
    $n   = ($d['neck_large'] ?? '') === 'yes'  ? 1 : 0;
    $g   = ($d['gender']     ?? '') === 'male' ? 1 : 0;

    $stop  = $s + $t + $o + $p;
    $bang  = $b + $a + $n + $g;
    $total = $stop + $bang;

    if ($total >= 5 || ($stop >= 2 && ($g || $b || $a))) $risk = 'High';
    elseif ($total >= 3) $risk = 'Intermediate';
    else $risk = 'Low';

    return [
        's' => $s, 't' => $t, 'o' => $o, 'p' => $p,
        'b' => $b, 'a' => $a, 'n' => $n, 'g' => $g,
        'bmi'        => round($bmi, 1),
        'age'        => $age,
        'stop_score' => $stop,
        'bang_score' => $bang,
        'total'      => $total,
        'risk'       => $risk,
    ];
}

function slq_stopbang_ghl(array $d, array $score): void {
    $api_key     = get_option('slq_ghl_api_key', '');
    $location_id = get_option('slq_ghl_location_id', '');
    if (!$api_key || !$location_id) return;

    $parts = explode(' ', trim($d['full_name'] ?? ''), 2);

    $field_map = [
        'age'                      => (string)($d['age']   ?? ''),
        'gender'                   => $d['gender']          ?? '',
        'height'                   => $d['height_display']  ?? '',
        'weight'                   => $d['weight_display']  ?? '',
        'bmi'                      => (string) $score['bmi'],
        'sb_snoring'               => $d['snoring']         ?? '',
        'sb_tired'                 => $d['tired']           ?? '',
        'sb_observed'              => $d['observed']        ?? '',
        'stopbang__high_pressure'  => $d['pressure']        ?? '',
        'stopbang__neck__16'       => $d['neck_large']      ?? '',
        'stopbang__stop_score'     => (string) $score['stop_score'],
        'stopbang__bang_score'     => (string) $score['bang_score'],
        'stopbang__total_score'    => (string) $score['total'],
        'stopbang__risk_level'     => $score['risk'],
    ];

    $custom_fields = [];
    foreach ($field_map as $key => $value) {
        $fid = slq_resolve_field_id($key);
        if ($fid && $value !== '') $custom_fields[] = ['id' => $fid, 'field_value' => (string)$value];
    }

    $payload = [
        'firstName'  => $parts[0] ?? '',
        'lastName'   => $parts[1] ?? '',
        'email'      => $d['email'] ?? '',
        'phone'      => $d['phone'] ?? '',
        'locationId' => $location_id,
        'source'     => 'STOP-Bang Sleep Screener',
        'tags'       => ['stop-bang-questionnaire'],
    ];
    if (!empty($custom_fields)) $payload['customFields'] = $custom_fields;

    $resp = wp_remote_post('https://services.leadconnectorhq.com/contacts/', [
        'headers' => ['Authorization' => 'Bearer ' . $api_key, 'Version' => '2021-07-28', 'Content-Type' => 'application/json'],
        'body'    => wp_json_encode($payload),
        'timeout' => 15,
    ]);
    if (is_wp_error($resp)) {
        error_log('[SLQ STOP-Bang] GHL error: ' . $resp->get_error_message());
    } elseif (wp_remote_retrieve_response_code($resp) >= 400) {
        error_log('[SLQ STOP-Bang] GHL HTTP ' . wp_remote_retrieve_response_code($resp) . ': ' . wp_remote_retrieve_body($resp));
    }
}

/* ═══════════════════════════════════════════════════════════ */
/* ENTRIES PAGE                                                */
/* ═══════════════════════════════════════════════════════════ */

add_action('admin_post_slq_delete_entry', function () {
    check_admin_referer('slq_delete_entry');
    if (!current_user_can('manage_options')) wp_die('Forbidden');
    global $wpdb;
    $id = intval($_GET['entry_id'] ?? 0);
    if ($id) $wpdb->delete($wpdb->prefix . 'slq_entries', ['id' => $id], ['%d']);
    wp_safe_redirect(admin_url('options-general.php?page=slq-entries&deleted=1'));
    exit;
});

add_action('admin_post_slq_export_csv', function () {
    check_admin_referer('slq_export_csv');
    if (!current_user_can('manage_options')) wp_die('Forbidden');
    global $wpdb;
    $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}slq_entries ORDER BY submitted_at DESC", ARRAY_A);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sleep-screener-entries-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Form', 'Date', 'Name', 'Email', 'Phone', 'Risk Level']);
    foreach ($rows as $row) {
        fputcsv($out, [$row['id'], strtoupper($row['form']), $row['submitted_at'], $row['full_name'], $row['email'], $row['phone'], $row['risk_level']]);
    }
    fclose($out);
    exit;
});

function slq_render_entries(): void {
    global $wpdb;
    $table = $wpdb->prefix . 'slq_entries';

    $risk_colors = [
        'High Risk' => '#dc2626', 'High' => '#dc2626',
        'Intermediate' => '#d97706',
        'Low Risk' => '#16a34a', 'Low' => '#16a34a',
    ];

    /* ── Detail view ── */
    $entry_id = intval($_GET['entry_id'] ?? 0);
    if ($entry_id) {
        $e = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $entry_id));
        if (!$e) { echo '<div class="wrap"><p>Entry not found.</p></div>'; return; }

        $fd    = json_decode($e->form_data,  true) ?? [];
        $sd    = json_decode($e->score_data, true) ?? [];
        $color = $risk_colors[$e->risk_level] ?? '#64748b';
        $back  = admin_url('options-general.php?page=slq-entries');
        $del   = wp_nonce_url(admin_url('admin-post.php?action=slq_delete_entry&entry_id=' . $e->id), 'slq_delete_entry');

        $berlin_labels = [
            'age' => 'Age', 'gender' => 'Gender', 'height_display' => 'Height', 'weight_lbs' => 'Weight (lbs)', 'bmi' => 'BMI',
            'q2'  => 'Do you snore?',
            'q3'  => 'Snoring volume',
            'q4'  => 'Snoring frequency',
            'q5'  => 'Does snoring bother others?',
            'q6'  => 'Observed stopping breathing',
            'q7'  => 'Tired / fatigued after sleep',
            'q8'  => 'Tired / fatigued during the day',
            'q9'  => 'Ever fallen asleep while driving?',
            'q10' => 'High blood pressure?',
        ];
        $stopbang_labels = [
            'age' => 'Age', 'gender' => 'Gender', 'height_display' => 'Height', 'weight_display' => 'Weight', 'bmi' => 'BMI',
            'snoring'    => 'Snoring loudly?',
            'tired'      => 'Often tired / fatigued?',
            'observed'   => 'Observed stopping breathing?',
            'pressure'   => 'High blood pressure?',
            'neck_large' => 'Neck circumference ≥ 16″?',
        ];
        $score_labels_berlin = [
            'risk_level'     => 'Risk Level',
            'pos_categories' => 'Positive Categories (of 3)',
            'cat1_positive'  => 'Category 1 Positive (Snoring & Breathing)',
            'cat2_positive'  => 'Category 2 Positive (Fatigue & Alertness)',
            'cat3_positive'  => 'Category 3 Positive (BP / BMI)',
            'cat1_score'     => 'Category 1 Score',
            'cat2_score'     => 'Category 2 Score',
            'bmi'            => 'Calculated BMI',
        ];
        $score_labels_stopbang = [
            'risk'       => 'Risk Level',
            'total'      => 'Total Score (out of 8)',
            'stop_score' => 'STOP Score (out of 4)',
            'bang_score' => 'BANG Score (out of 4)',
            'bmi'        => 'Calculated BMI',
            's' => 'S — Snoring', 't' => 'T — Tired', 'o' => 'O — Observed', 'p' => 'P — Pressure',
            'b' => 'B — BMI > 35', 'a' => 'A — Age > 50', 'n' => 'N — Neck ≥ 16″', 'g' => 'G — Gender (Male)',
        ];

        $field_labels  = $e->form === 'berlin' ? $berlin_labels  : $stopbang_labels;
        $score_labels  = $e->form === 'berlin' ? $score_labels_berlin : $score_labels_stopbang;

        function slq_fmt($v): string {
            if (is_bool($v)) return $v ? 'Yes' : 'No';
            if ($v === 1 || $v === '1') return 'Yes';
            if ($v === 0 || $v === '0') return 'No';
            return str_replace('_', ' ', ucfirst((string)$v));
        }
        ?>
        <style>
        .slq-detail-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:24px 28px;margin-bottom:20px}
        .slq-detail-card h2{margin:0 0 16px;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;padding-bottom:12px;border-bottom:1px solid #f1f5f9}
        .slq-field-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}
        .slq-field{background:#f8fafc;border-radius:7px;padding:10px 14px}
        .slq-field-label{font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px}
        .slq-field-value{font-size:14px;color:#1e293b;font-weight:500}
        </style>
        <div class="wrap" style="max-width:900px">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap">
                <a href="<?php echo esc_url($back); ?>" class="button">&larr; All Entries</a>
                <h1 style="margin:0;font-size:20px"><?php echo esc_html($e->full_name); ?></h1>
                <span style="background:<?php echo $e->form === 'berlin' ? '#eff6ff' : '#faf5ff'; ?>;color:<?php echo $e->form === 'berlin' ? '#1d4ed8' : '#7e22ce'; ?>;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase"><?php echo esc_html($e->form); ?></span>
                <span style="color:<?php echo $color; ?>;font-weight:700;font-size:14px"><?php echo esc_html($e->risk_level); ?></span>
                <span style="color:#94a3b8;font-size:13px;margin-left:auto"><?php echo esc_html(date('M j, Y g:i a', strtotime($e->submitted_at))); ?></span>
            </div>

            <!-- Contact -->
            <div class="slq-detail-card">
                <h2>Contact</h2>
                <div class="slq-field-grid">
                    <div class="slq-field"><div class="slq-field-label">Name</div><div class="slq-field-value"><?php echo esc_html($e->full_name); ?></div></div>
                    <div class="slq-field"><div class="slq-field-label">Email</div><div class="slq-field-value"><a href="mailto:<?php echo esc_attr($e->email); ?>"><?php echo esc_html($e->email); ?></a></div></div>
                    <div class="slq-field"><div class="slq-field-label">Phone</div><div class="slq-field-value"><?php echo esc_html($e->phone); ?></div></div>
                </div>
            </div>

            <!-- Questionnaire Answers -->
            <div class="slq-detail-card">
                <h2>Questionnaire Answers</h2>
                <div class="slq-field-grid">
                <?php foreach ($field_labels as $key => $label):
                    $val = $fd[$key] ?? null;
                    if ($val === null || $val === '') continue;
                ?>
                    <div class="slq-field">
                        <div class="slq-field-label"><?php echo esc_html($label); ?></div>
                        <div class="slq-field-value"><?php echo esc_html(slq_fmt($val)); ?></div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>

            <!-- Score -->
            <div class="slq-detail-card">
                <h2>Score &amp; Risk Assessment</h2>
                <div class="slq-field-grid">
                <?php foreach ($score_labels as $key => $label):
                    $val = $sd[$key] ?? null;
                    if ($val === null || $val === '') continue;
                ?>
                    <div class="slq-field">
                        <div class="slq-field-label"><?php echo esc_html($label); ?></div>
                        <div class="slq-field-value" <?php if ($key === 'risk_level' || $key === 'risk') echo 'style="color:' . esc_attr($color) . ';font-weight:700"'; ?>>
                            <?php echo esc_html(slq_fmt($val)); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>

            <a href="<?php echo esc_url($del); ?>" class="button" style="color:#dc2626;border-color:#fca5a5" onclick="return confirm('Delete this entry?')">Delete Entry</a>
        </div>
        <?php
        return;
    }

    /* ── List view ── */
    $per_page = 25;
    $page     = max(1, intval($_GET['paged'] ?? 1));
    $filter   = sanitize_text_field($_GET['form_filter'] ?? '');
    $search   = sanitize_text_field($_GET['s'] ?? '');

    $where = 'WHERE 1=1';
    if ($filter === 'berlin')   $where .= " AND form = 'berlin'";
    if ($filter === 'stopbang') $where .= " AND form = 'stopbang'";
    if ($search) {
        $like   = '%' . $wpdb->esc_like($search) . '%';
        $where .= $wpdb->prepare(" AND (full_name LIKE %s OR email LIKE %s OR phone LIKE %s)", $like, $like, $like);
    }

    $total   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");
    $offset  = ($page - 1) * $per_page;
    $entries = $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY submitted_at DESC LIMIT {$per_page} OFFSET {$offset}");
    $pages   = max(1, ceil($total / $per_page));
    ?>
    <div class="wrap" style="max-width:1100px">
        <h1 style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
            <span>Sleep Screener Entries
                <span style="font-size:14px;font-weight:400;color:#64748b;margin-left:8px"><?php echo $total; ?> total</span>
            </span>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin:0">
                <?php wp_nonce_field('slq_export_csv'); ?>
                <input type="hidden" name="action" value="slq_export_csv">
                <button type="submit" class="button button-secondary">&#11015; Export CSV</button>
            </form>
        </h1>

        <?php if (!empty($_GET['deleted'])): ?>
            <div class="notice notice-success is-dismissible"><p>Entry deleted.</p></div>
        <?php endif; ?>

        <form method="get" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:16px">
            <input type="hidden" name="page" value="slq-entries">
            <input type="text" name="s" placeholder="Search name / email / phone…" value="<?php echo esc_attr($search); ?>" style="width:240px">
            <select name="form_filter">
                <option value="">All Forms</option>
                <option value="berlin"   <?php selected($filter, 'berlin'); ?>>Berlin</option>
                <option value="stopbang" <?php selected($filter, 'stopbang'); ?>>STOP-Bang</option>
            </select>
            <button type="submit" class="button">Filter</button>
            <?php if ($search || $filter): ?>
                <a href="<?php echo admin_url('options-general.php?page=slq-entries'); ?>" class="button">Clear</a>
            <?php endif; ?>
        </form>

        <?php if (empty($entries)): ?>
            <p style="color:#64748b">No entries yet — entries will appear here as patients complete the forms.</p>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped" style="border-radius:8px;overflow:hidden">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th style="width:140px">Date</th>
                    <th style="width:80px">Form</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th style="width:130px">Phone</th>
                    <th style="width:130px">Risk Level</th>
                    <th style="width:90px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($entries as $e):
                $color      = $risk_colors[$e->risk_level] ?? '#64748b';
                $view_url   = admin_url('options-general.php?page=slq-entries&entry_id=' . $e->id);
                $delete_url = wp_nonce_url(admin_url('admin-post.php?action=slq_delete_entry&entry_id=' . $e->id), 'slq_delete_entry');
            ?>
                <tr>
                    <td style="color:#94a3b8"><?php echo $e->id; ?></td>
                    <td style="font-size:12px;color:#475569"><?php echo esc_html(date('M j, Y g:i a', strtotime($e->submitted_at))); ?></td>
                    <td><span style="background:<?php echo $e->form === 'berlin' ? '#eff6ff' : '#faf5ff'; ?>;color:<?php echo $e->form === 'berlin' ? '#1d4ed8' : '#7e22ce'; ?>;padding:2px 8px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase"><?php echo esc_html($e->form); ?></span></td>
                    <td style="font-weight:500"><a href="<?php echo esc_url($view_url); ?>"><?php echo esc_html($e->full_name); ?></a></td>
                    <td><a href="mailto:<?php echo esc_attr($e->email); ?>"><?php echo esc_html($e->email); ?></a></td>
                    <td><?php echo esc_html($e->phone); ?></td>
                    <td><span style="color:<?php echo $color; ?>;font-weight:700;font-size:12px"><?php echo esc_html($e->risk_level); ?></span></td>
                    <td>
                        <a href="<?php echo esc_url($view_url); ?>" style="font-size:12px">View</a>
                        &nbsp;|&nbsp;
                        <a href="<?php echo esc_url($delete_url); ?>" style="color:#dc2626;font-size:12px" onclick="return confirm('Delete this entry?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($pages > 1): ?>
        <div style="margin-top:16px;display:flex;gap:6px;align-items:center">
            <?php for ($i = 1; $i <= $pages; $i++):
                $url = add_query_arg(['page' => 'slq-entries', 'paged' => $i, 's' => $search, 'form_filter' => $filter], admin_url('options-general.php'));
            ?>
                <a href="<?php echo esc_url($url); ?>"
                   style="padding:4px 10px;border-radius:4px;border:1px solid <?php echo $i === $page ? '#2563eb' : '#e2e8f0'; ?>;background:<?php echo $i === $page ? '#2563eb' : '#fff'; ?>;color:<?php echo $i === $page ? '#fff' : '#374151'; ?>;text-decoration:none;font-size:13px">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

/* ═══════════════════════════════════════════════════════════ */
/* GITHUB AUTO-UPDATER                                         */
/* ═══════════════════════════════════════════════════════════ */

add_filter('pre_set_site_transient_update_plugins', 'slq_check_for_update');
function slq_check_for_update($transient) {
    if (empty($transient->checked)) return $transient;
    $release = slq_get_github_release();
    if (!$release || empty($release->tag_name)) return $transient;
    $new_version = ltrim($release->tag_name, 'v');
    if (!version_compare($new_version, SLQ_VERSION, '>')) return $transient;
    $download_url = '';
    if (!empty($release->assets)) {
        foreach ($release->assets as $asset) {
            if (substr($asset->name, -4) === '.zip') { $download_url = $asset->browser_download_url; break; }
        }
    }
    if ($download_url) {
        $transient->response[plugin_basename(__FILE__)] = (object)[
            'slug'        => dirname(plugin_basename(__FILE__)),
            'plugin'      => plugin_basename(__FILE__),
            'new_version' => $new_version,
            'url'         => $release->html_url,
            'package'     => $download_url,
        ];
    }
    return $transient;
}

add_filter('plugins_api', 'slq_plugin_info', 20, 3);
function slq_plugin_info($result, $action, $args) {
    if ($action !== 'plugin_information') return $result;
    if ($args->slug !== dirname(plugin_basename(__FILE__))) return $result;
    $release = slq_get_github_release();
    if (!$release) return $result;
    return (object)[
        'name'          => 'Sleep Apnea Screener',
        'slug'          => dirname(plugin_basename(__FILE__)),
        'version'       => ltrim($release->tag_name ?? '', 'v'),
        'author'        => 'Riverwalk Dentistry',
        'homepage'      => 'https://github.com/' . SLQ_GITHUB_REPO,
        'sections'      => ['description' => $release->body ?? ''],
        'download_link' => !empty($release->assets[0]) ? $release->assets[0]->browser_download_url : '',
    ];
}

add_filter('auto_update_plugin', function ($update, $item) {
    return (isset($item->plugin) && $item->plugin === plugin_basename(__FILE__)) ? true : $update;
}, 10, 2);

function slq_get_github_release() {
    $cached = get_transient('slq_github_release');
    if ($cached !== false) return $cached;
    $response = wp_remote_get('https://api.github.com/repos/' . SLQ_GITHUB_REPO . '/releases/latest', [
        'timeout' => 10,
        'headers' => ['Accept' => 'application/vnd.github.v3+json', 'User-Agent' => 'WordPress/' . get_bloginfo('version')],
    ]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return false;
    $release = json_decode(wp_remote_retrieve_body($response));
    set_transient('slq_github_release', $release, 12 * HOUR_IN_SECONDS);
    return $release;
}
