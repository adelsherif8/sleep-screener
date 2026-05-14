<?php
/**
 * Plugin Name: Sleep Apnea Screener
 * Description: Berlin Sleep Questionnaire and STOP-Bang Questionnaire with scoring, results, and GoHighLevel integration.
 * Version:     1.0.8
 * Author:      Adel Emad
 * Author URI:  https://upwork.com/freelancers/adelsherif8
 * License:     GPL-2.0+
 * GitHub Plugin URI: adelsherif8/sleep-screener
 */

defined('ABSPATH') || exit;

define('SLQ_VERSION',     '1.0.8');
define('SLQ_DIR',         plugin_dir_path(__FILE__));
define('SLQ_URL',         plugin_dir_url(__FILE__));
define('SLQ_GITHUB_REPO', 'adelsherif8/sleep-screener');

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
        // Demographics — shared by both forms
        'age'              => ['label' => 'Age',                          'example' => '45',                         'group' => 'Demographics'],
        'gender'           => ['label' => 'Gender',                       'example' => 'male / female',               'group' => 'Demographics'],
        'height'           => ['label' => 'Height',                       'example' => "5'10\" or 178 cm",            'group' => 'Demographics'],
        'weight'           => ['label' => 'Weight',                       'example' => '185 lb or 80 kg',             'group' => 'Demographics'],
        'bmi'              => ['label' => 'BMI',                          'example' => '26.4',                        'group' => 'Demographics'],
        // Berlin Answers
        'b_snores'         => ['label' => 'Berlin — Snores?',             'example' => 'yes / no / dont_know',        'group' => 'Berlin Answers'],
        'b_snore_volume'   => ['label' => 'Berlin — Snoring volume',      'example' => 'very_loud',                   'group' => 'Berlin Answers'],
        'b_snore_freq'     => ['label' => 'Berlin — Snoring frequency',   'example' => 'nearly_every_day',            'group' => 'Berlin Answers'],
        'b_bothers_others' => ['label' => 'Berlin — Bothers others?',     'example' => 'yes / no',                    'group' => 'Berlin Answers'],
        'b_stop_breathing' => ['label' => 'Berlin — Stop breathing?',     'example' => 'nearly_every_day',            'group' => 'Berlin Answers'],
        'b_tired_sleep'    => ['label' => 'Berlin — Tired after sleep',   'example' => '3_4_times',                   'group' => 'Berlin Answers'],
        'b_tired_day'      => ['label' => 'Berlin — Tired during day',    'example' => 'nearly_every_day',            'group' => 'Berlin Answers'],
        'b_fall_asleep'    => ['label' => 'Berlin — Fall asleep driving?','example' => 'yes / no',                   'group' => 'Berlin Answers'],
        'b_blood_pressure' => ['label' => 'Berlin — Blood pressure?',     'example' => 'yes / no / dont_know',        'group' => 'Berlin Answers'],
        // Berlin Score
        'b_risk_level'     => ['label' => 'Berlin — Risk Level',          'example' => 'High Risk / Low Risk',        'group' => 'Berlin Score'],
        'b_pos_categories' => ['label' => 'Berlin — Positive Categories', 'example' => '2',                          'group' => 'Berlin Score'],
        'b_cat1_positive'  => ['label' => 'Berlin — Category 1 Positive', 'example' => 'Yes / No',                   'group' => 'Berlin Score'],
        'b_cat2_positive'  => ['label' => 'Berlin — Category 2 Positive', 'example' => 'Yes / No',                   'group' => 'Berlin Score'],
        'b_cat3_positive'  => ['label' => 'Berlin — Category 3 Positive', 'example' => 'Yes / No',                   'group' => 'Berlin Score'],
        // STOP-Bang Answers
        'sb_snoring'       => ['label' => 'STOP-Bang — Snoring',          'example' => 'yes / no',                   'group' => 'STOP-Bang Answers'],
        'sb_tired'         => ['label' => 'STOP-Bang — Tired',            'example' => 'yes / no',                   'group' => 'STOP-Bang Answers'],
        'sb_observed'      => ['label' => 'STOP-Bang — Observed',         'example' => 'yes / no',                   'group' => 'STOP-Bang Answers'],
        'sb_pressure'      => ['label' => 'STOP-Bang — High Pressure',    'example' => 'yes / no',                   'group' => 'STOP-Bang Answers'],
        'sb_neck_large'    => ['label' => 'STOP-Bang — Neck ≥ 16″',       'example' => 'yes / no',                   'group' => 'STOP-Bang Answers'],
        // STOP-Bang Score
        'sb_stop_score'    => ['label' => 'STOP-Bang — STOP Score',       'example' => '3',                          'group' => 'STOP-Bang Score'],
        'sb_bang_score'    => ['label' => 'STOP-Bang — BANG Score',       'example' => '2',                          'group' => 'STOP-Bang Score'],
        'sb_total_score'   => ['label' => 'STOP-Bang — Total Score',      'example' => '5',                          'group' => 'STOP-Bang Score'],
        'sb_risk_level'    => ['label' => 'STOP-Bang — Risk Level',       'example' => 'High / Intermediate / Low',  'group' => 'STOP-Bang Score'],
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
});

/* ─── Admin AJAX: force update check ──────────────────────── */

add_action('wp_ajax_slq_force_update_check', function () {
    check_ajax_referer('slq_force_update', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    delete_transient('slq_github_release');
    delete_site_transient('update_plugins');
    wp_send_json_success(['message' => 'Cache cleared. Go to Dashboard → Updates and click "Check Again".']);
});

/* ─── Admin AJAX: GHL run full setup (auto folder + fields) ─ */

add_action('wp_ajax_slq_setup_all', function () {
    check_ajax_referer('slq_ghl', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    $api_key     = get_option('slq_ghl_api_key', '');
    $location_id = get_option('slq_ghl_location_id', '');
    if (!$api_key || !$location_id) {
        wp_send_json_error(['message' => 'Save your API Key and Location ID first.']); return;
    }
    $base    = 'https://services.leadconnectorhq.com';
    $headers = ['Authorization' => 'Bearer ' . $api_key, 'Version' => '2021-07-28', 'Content-Type' => 'application/json'];

    // ── Auto-create "Sleep Screener" folder ──
    $folder_id = '';
    $r_folder  = wp_remote_post("{$base}/locations/{$location_id}/customFieldsFolders", [
        'headers' => $headers, 'timeout' => 15,
        'body'    => wp_json_encode(['name' => 'Sleep Screener', 'model' => 'contact']),
    ]);
    if (!is_wp_error($r_folder)) {
        $b_folder  = json_decode(wp_remote_retrieve_body($r_folder), true) ?? [];
        $folder_id = $b_folder['folder']['id'] ?? $b_folder['id'] ?? '';
    }

    // ── Fetch all existing custom fields ──
    $r_list   = wp_remote_get("{$base}/locations/{$location_id}/customFields", ['headers' => $headers, 'timeout' => 15]);
    $b_list   = is_wp_error($r_list) ? [] : (json_decode(wp_remote_retrieve_body($r_list), true) ?? []);
    $existing = []; // bare fieldKey → id
    foreach ($b_list['customFields'] ?? [] as $f) {
        $bare = strtolower(preg_replace('/^contact\./', '', $f['fieldKey'] ?? ''));
        if ($bare) $existing[$bare] = $f['id'];
    }

    // ── If folder creation returned no ID, detect from an already-saved field ──
    if (!$folder_id) {
        foreach ($b_list['customFields'] ?? [] as $f) {
            $bare = strtolower(preg_replace('/^contact\./', '', $f['fieldKey'] ?? ''));
            if (isset(slq_field_list()[$bare]) && !empty($f['parentId'])) {
                $folder_id = $f['parentId']; break;
            }
        }
    }

    $created = 0; $skipped = 0; $moved = 0; $errors = [];

    foreach (slq_field_list() as $slug => $meta) {
        $saved_id = get_option('slq_cf_' . $slug, '');

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

        // Move field into folder
        if ($folder_id && $saved_id) {
            wp_remote_request("{$base}/locations/{$location_id}/customFields/{$saved_id}", [
                'method' => 'PUT', 'headers' => $headers, 'timeout' => 15,
                'body'   => wp_json_encode(['parentId' => $folder_id]),
            ]);
            $moved++;
        }
    }

    // ── Verification GET — catch any IDs missed above ──
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

    wp_send_json_success([
        'created' => $created,
        'skipped' => $skipped,
        'moved'   => $moved,
        'errors'  => $errors,
    ]);
});

/* ─── Settings page ────────────────────────────────────────── */

function slq_render_settings() {
    if (isset($_POST['slq_nonce']) && wp_verify_nonce($_POST['slq_nonce'], 'slq_save')) {
        update_option('slq_ghl_api_key',     sanitize_text_field($_POST['slq_ghl_api_key']     ?? ''));
        update_option('slq_ghl_location_id', sanitize_text_field($_POST['slq_ghl_location_id'] ?? ''));
        update_option('slq_booking_url',     sanitize_text_field($_POST['slq_booking_url']     ?? '/book-appointment'));
        update_option('slq_primary_color',   sanitize_hex_color($_POST['slq_primary_color']    ?? '#2d6a5a') ?: '#2d6a5a');
        foreach (array_keys(slq_field_list()) as $key) {
            update_option('slq_cf_' . $key, sanitize_text_field($_POST['slq_cf_' . $key] ?? ''));
        }
        echo '<div class="notice notice-success is-dismissible"><p><strong>Settings saved.</strong></p></div>';
    }

    $api_key     = get_option('slq_ghl_api_key',     '');
    $location_id = get_option('slq_ghl_location_id', '');
    $booking_url = get_option('slq_booking_url',     '/book-appointment');
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
                        <th><label for="slq_booking_url">Booking Page URL</label></th>
                        <td>
                            <input type="text" id="slq_booking_url" name="slq_booking_url"
                                   value="<?php echo esc_attr($booking_url); ?>" class="regular-text"
                                   placeholder="/book-appointment" />
                            <p class="description">The "Book Appointment" button on both results screens links here.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- ── GHL One-Click Setup ── -->
            <div class="slq-card">
                <p class="slq-card-title">GHL One-Click Field Setup</p>
                <p style="margin:0 0 16px;color:#475569;font-size:13px">
                    Creates all <?php echo count(slq_field_list()); ?> custom fields in GHL inside a <strong>Sleep Screener</strong> folder and saves their IDs automatically.
                    Make sure your API Key and Location ID are saved above first.
                </p>
                <div class="slq-ghl-row">
                    <button type="button" class="button button-primary" id="slq-setup-all-btn" style="height:36px;padding:0 20px">
                        Run GHL Setup
                    </button>
                </div>
                <p class="slq-log" id="slq-setup-log"></p>
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

            // Run GHL Setup
            document.getElementById('slq-setup-all-btn').addEventListener('click', function() {
                var btn = this, log = document.getElementById('slq-setup-log');
                btn.disabled = true; btn.textContent = 'Working…';
                log.style.color = '#475569';
                log.textContent = 'Creating Sleep Screener folder and fields — this may take 20–30 seconds…';
                ghlPost('slq_setup_all', {}, function(res) {
                    btn.disabled = false; btn.textContent = 'Run GHL Setup';
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
    wp_enqueue_style('slq-berlin',  SLQ_URL . 'assets/berlin-style.css',  [], SLQ_VERSION);
    wp_enqueue_script('slq-berlin', SLQ_URL . 'assets/berlin-script.js', [], SLQ_VERSION, true);
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
    if (intval($d['_elapsed'] ?? 0) < 8)       { wp_send_json_success([]); return; }
    $score = slq_berlin_score($d);
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
        $fid = get_option('slq_cf_' . $key, '');
        if ($fid && $value !== '') $custom_fields[] = ['id' => $fid, 'field_value' => $value];
    }

    $payload = [
        'firstName'  => $parts[0] ?? '',
        'lastName'   => $parts[1] ?? '',
        'email'      => $d['email'] ?? '',
        'phone'      => $d['phone'] ?? '',
        'locationId' => $location_id,
        'source'     => 'Berlin Sleep Screener',
        'tags'       => ['sleep-apnea-screening', $score['risk_level'] === 'High Risk' ? 'berlin-high-risk' : 'berlin-low-risk'],
    ];
    if (!empty($custom_fields)) $payload['customFields'] = $custom_fields;

    wp_remote_post('https://services.leadconnectorhq.com/contacts/', [
        'headers'  => ['Authorization' => 'Bearer ' . $api_key, 'Version' => '2021-07-28', 'Content-Type' => 'application/json'],
        'body'     => wp_json_encode($payload),
        'timeout'  => 15,
        'blocking' => false,
    ]);
}

/* ═══════════════════════════════════════════════════════════ */
/* STOP-BANG QUESTIONNAIRE                                     */
/* ═══════════════════════════════════════════════════════════ */

add_shortcode('stopbang_questionnaire', function () {
    wp_enqueue_style('slq-stopbang',  SLQ_URL . 'assets/stopbang-style.css',  [], SLQ_VERSION);
    wp_enqueue_script('slq-stopbang', SLQ_URL . 'assets/stopbang-script.js', [], SLQ_VERSION, true);
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
    if (intval($d['_elapsed'] ?? 0) < 5) { wp_send_json_success([]); return; }
    $score = slq_stopbang_score($d);
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
        'age'           => (string)($d['age']        ?? ''),
        'gender'        => $d['gender']               ?? '',
        'height'        => $d['height_display']        ?? '',
        'weight'        => $d['weight_display']        ?? '',
        'bmi'           => (string) $score['bmi'],
        'sb_snoring'    => $d['snoring']               ?? '',
        'sb_tired'      => $d['tired']                 ?? '',
        'sb_observed'   => $d['observed']              ?? '',
        'sb_pressure'   => $d['pressure']              ?? '',
        'sb_neck_large' => $d['neck_large']            ?? '',
        'sb_stop_score' => (string) $score['stop_score'],
        'sb_bang_score' => (string) $score['bang_score'],
        'sb_total_score'=> (string) $score['total'],
        'sb_risk_level' => $score['risk'],
    ];

    $custom_fields = [];
    foreach ($field_map as $key => $value) {
        $fid = get_option('slq_cf_' . $key, '');
        if ($fid && $value !== '') $custom_fields[] = ['id' => $fid, 'field_value' => $value];
    }

    $payload = [
        'firstName'  => $parts[0] ?? '',
        'lastName'   => $parts[1] ?? '',
        'email'      => $d['email'] ?? '',
        'phone'      => $d['phone'] ?? '',
        'locationId' => $location_id,
        'source'     => 'STOP-Bang Sleep Screener',
        'tags'       => ['sleep-apnea-screening', 'sb-' . strtolower($score['risk']) . '-risk'],
    ];
    if (!empty($custom_fields)) $payload['customFields'] = $custom_fields;

    wp_remote_post('https://services.leadconnectorhq.com/contacts/', [
        'headers'  => ['Authorization' => 'Bearer ' . $api_key, 'Version' => '2021-07-28', 'Content-Type' => 'application/json'],
        'body'     => wp_json_encode($payload),
        'timeout'  => 15,
        'blocking' => false,
    ]);
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
