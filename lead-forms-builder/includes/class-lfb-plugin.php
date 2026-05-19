<?php

namespace LFB;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin {
    public static function init(): void {
        add_action('init', [self::class, 'register_post_types']);
        add_action('add_meta_boxes', [self::class, 'register_meta_boxes']);
        add_action('save_post_lfb_form', [self::class, 'save_form_meta']);
        add_shortcode('lfb_form', [self::class, 'render_form_shortcode']);
        add_action('wp_enqueue_scripts', [self::class, 'register_assets']);
        add_action('admin_menu', [self::class, 'register_leads_page']);
        add_action('admin_post_lfb_export_leads', [self::class, 'export_leads_csv']);
    }

    public static function register_post_types(): void {
        register_post_type('lfb_form', [
            'labels' => [
                'name' => __('Forms', 'lead-forms-builder'),
                'singular_name' => __('Form', 'lead-forms-builder'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-feedback',
        ]);

        register_post_type('lfb_lead', [
            'labels' => [
                'name' => __('Leads', 'lead-forms-builder'),
                'singular_name' => __('Lead', 'lead-forms-builder'),
            ],
            'public' => false,
            'show_ui' => false,
            'supports' => ['title'],
        ]);
    }

    public static function register_assets(): void {
        wp_register_style('lfb-front', LFB_URL . 'assets/css/front.css', [], '1.0.0');
        wp_register_script('lfb-front', LFB_URL . 'assets/js/front.js', ['jquery'], '1.0.0', true);
    }

    public static function register_meta_boxes(): void {
        add_meta_box('lfb_form_builder', __('Form Builder', 'lead-forms-builder'), [self::class, 'render_form_builder_box'], 'lfb_form', 'normal', 'high');
        add_meta_box('lfb_form_shortcode', __('Shortcode', 'lead-forms-builder'), [self::class, 'render_shortcode_box'], 'lfb_form', 'side', 'high');
    }

    public static function render_form_builder_box(\WP_Post $post): void {
        wp_nonce_field('lfb_save_form_meta', 'lfb_nonce');
        $html = get_post_meta($post->ID, '_lfb_html', true);
        $css = get_post_meta($post->ID, '_lfb_css', true);
        $js = get_post_meta($post->ID, '_lfb_js', true);
        $isolate = (bool) get_post_meta($post->ID, '_lfb_isolate', true);
        $notify_email = (string) get_post_meta($post->ID, '_lfb_notify_email', true);
        $success_message = (string) get_post_meta($post->ID, '_lfb_success_message', true);
        $after_submit_action = (string) get_post_meta($post->ID, '_lfb_after_submit_action', true);
        $redirect_url = (string) get_post_meta($post->ID, '_lfb_redirect_url', true);
        $redirect_delay = (int) get_post_meta($post->ID, '_lfb_redirect_delay', true);
        if (!in_array($after_submit_action, ['message', 'redirect', 'message_redirect'], true)) {
            $after_submit_action = 'message';
        }
        ?>
        <p><?php esc_html_e('Paste your custom form markup. Make sure your form includes fields and submit button.', 'lead-forms-builder'); ?></p>
        <p><button type="button" class="button" id="lfb-load-demo-template"><?php esc_html_e('Load demo Hebrew lead form template', 'lead-forms-builder'); ?></button></p>
        <label for="lfb_html"><strong>HTML</strong></label>
        <textarea id="lfb_html" name="lfb_html" style="width:100%;min-height:220px;"><?php echo esc_textarea($html); ?></textarea>

        <label for="lfb_css" style="display:block;margin-top:16px;"><strong>CSS</strong></label>
        <textarea id="lfb_css" name="lfb_css" style="width:100%;min-height:150px;"><?php echo esc_textarea($css); ?></textarea>

        <label for="lfb_js" style="display:block;margin-top:16px;"><strong>JS (optional)</strong></label>
        <textarea id="lfb_js" name="lfb_js" style="width:100%;min-height:150px;"><?php echo esc_textarea($js); ?></textarea>

        <p style="margin-top:16px;">
            <label><input type="checkbox" name="lfb_isolate" value="1" <?php checked($isolate); ?>> <?php esc_html_e('Isolate form styles from the active theme (Shadow DOM mode)', 'lead-forms-builder'); ?></label>
        </p>

        <label for="lfb_notify_email" style="display:block;margin-top:16px;"><strong>Email notifications</strong></label>
        <input id="lfb_notify_email" name="lfb_notify_email" type="email" style="width:100%;" value="<?php echo esc_attr($notify_email); ?>" placeholder="name@example.com">
        <p class="description"><?php esc_html_e('If set, each new lead from this form will be sent to this email.', 'lead-forms-builder'); ?></p>

        <hr style="margin:18px 0;">
        <h3><?php esc_html_e('After submit behavior', 'lead-forms-builder'); ?></h3>
        <label for="lfb_after_submit_action"><strong><?php esc_html_e('Action', 'lead-forms-builder'); ?></strong></label>
        <select id="lfb_after_submit_action" name="lfb_after_submit_action" style="width:100%;">
            <option value="message" <?php selected($after_submit_action, 'message'); ?>><?php esc_html_e('Show success message', 'lead-forms-builder'); ?></option>
            <option value="redirect" <?php selected($after_submit_action, 'redirect'); ?>><?php esc_html_e('Redirect only', 'lead-forms-builder'); ?></option>
            <option value="message_redirect" <?php selected($after_submit_action, 'message_redirect'); ?>><?php esc_html_e('Show message then redirect', 'lead-forms-builder'); ?></option>
        </select>

        <label for="lfb_success_message" style="display:block;margin-top:12px;"><strong><?php esc_html_e('Success message', 'lead-forms-builder'); ?></strong></label>
        <textarea id="lfb_success_message" name="lfb_success_message" style="width:100%;min-height:90px;"><?php echo esc_textarea($success_message); ?></textarea>

        <label for="lfb_redirect_url" style="display:block;margin-top:12px;"><strong><?php esc_html_e('Redirect URL', 'lead-forms-builder'); ?></strong></label>
        <input id="lfb_redirect_url" name="lfb_redirect_url" type="url" style="width:100%;" value="<?php echo esc_attr($redirect_url); ?>" placeholder="https://example.com/thank-you">
        <label for="lfb_redirect_delay" style="display:block;margin-top:12px;"><strong><?php esc_html_e('Redirect delay (seconds)', 'lead-forms-builder'); ?></strong></label>
        <input id="lfb_redirect_delay" name="lfb_redirect_delay" type="number" min="0" max="30" value="<?php echo esc_attr((string) $redirect_delay); ?>">

        <script>
            (function(){
                var btn = document.getElementById('lfb-load-demo-template');
                if (!btn) { return; }
                btn.addEventListener('click', function(){
                    var html = document.getElementById('lfb_html');
                    var css = document.getElementById('lfb_css');
                    if (html && !html.value.trim()) {
                        html.value = <?php echo wp_json_encode(self::get_default_template_html()); ?>;
                    }
                    if (css && !css.value.trim()) {
                        css.value = <?php echo wp_json_encode(self::get_default_template_css()); ?>;
                    }
                });
            })();
        </script>
        <?php
    }

    public static function render_shortcode_box(\WP_Post $post): void {
        echo '<code>[lfb_form id="' . (int) $post->ID . '"]</code>';
    }

    public static function save_form_meta(int $post_id): void {
        if (!isset($_POST['lfb_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lfb_nonce'])), 'lfb_save_form_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        update_post_meta($post_id, '_lfb_html', isset($_POST['lfb_html']) ? wp_unslash($_POST['lfb_html']) : '');
        update_post_meta($post_id, '_lfb_css', isset($_POST['lfb_css']) ? wp_unslash($_POST['lfb_css']) : '');
        update_post_meta($post_id, '_lfb_js', isset($_POST['lfb_js']) ? wp_unslash($_POST['lfb_js']) : '');
        update_post_meta($post_id, '_lfb_isolate', isset($_POST['lfb_isolate']) ? '1' : '0');
        $notify_email = isset($_POST['lfb_notify_email']) ? sanitize_email(wp_unslash($_POST['lfb_notify_email'])) : '';
        update_post_meta($post_id, '_lfb_notify_email', is_email($notify_email) ? $notify_email : '');
        $success_message = isset($_POST['lfb_success_message']) ? sanitize_textarea_field(wp_unslash($_POST['lfb_success_message'])) : '';
        update_post_meta($post_id, '_lfb_success_message', $success_message);
        $after_submit_action = isset($_POST['lfb_after_submit_action']) ? sanitize_key(wp_unslash($_POST['lfb_after_submit_action'])) : 'message';
        if (!in_array($after_submit_action, ['message', 'redirect', 'message_redirect'], true)) {
            $after_submit_action = 'message';
        }
        update_post_meta($post_id, '_lfb_after_submit_action', $after_submit_action);
        $redirect_url = isset($_POST['lfb_redirect_url']) ? esc_url_raw(wp_unslash($_POST['lfb_redirect_url'])) : '';
        update_post_meta($post_id, '_lfb_redirect_url', $redirect_url);
        $redirect_delay = isset($_POST['lfb_redirect_delay']) ? (int) $_POST['lfb_redirect_delay'] : 0;
        $redirect_delay = max(0, min(30, $redirect_delay));
        update_post_meta($post_id, '_lfb_redirect_delay', $redirect_delay);
    }

    public static function render_form_shortcode(array $atts): string {
        $atts = shortcode_atts(['id' => 0], $atts, 'lfb_form');
        $form_id = (int) $atts['id'];
        if (!$form_id || get_post_type($form_id) !== 'lfb_form') {
            return '';
        }

        $html = (string) get_post_meta($form_id, '_lfb_html', true);
        $css = (string) get_post_meta($form_id, '_lfb_css', true);
        $js = (string) get_post_meta($form_id, '_lfb_js', true);
        $isolate = (bool) get_post_meta($form_id, '_lfb_isolate', true);

        if (empty($html)) {
            return '';
        }

        wp_enqueue_style('lfb-front');
        wp_enqueue_script('lfb-front');

        $submission_result = ['message' => '', 'redirect_url' => '', 'redirect_delay' => 0];
        if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['lfb_form_id']) && (int) $_POST['lfb_form_id'] === $form_id) {
            $submission_result = self::handle_submission($form_id);
        }

        $wrapper_id = 'lfb-form-' . $form_id;
        $output = '<div class="lfb-form-wrapper" id="' . esc_attr($wrapper_id) . '">';
        if (!empty($submission_result['message'])) {
            $output .= '<div class="lfb-message">' . esc_html((string) $submission_result['message']) . '</div>';
        }
        if (!empty($submission_result['redirect_url'])) {
            $delay_ms = max(0, ((int) $submission_result['redirect_delay']) * 1000);
            $output .= '<script>setTimeout(function(){window.location.href=' . wp_json_encode((string) $submission_result['redirect_url']) . ';},' . (int) $delay_ms . ');</script>';
        }

        $form_markup = '<form method="post" class="lfb-form-inner">';
        $form_markup .= self::sanitize_form_html($html);
        $form_markup .= '<input type="hidden" name="lfb_form_id" value="' . esc_attr((string) $form_id) . '">';
        $form_markup .= wp_nonce_field('lfb_submit_' . $form_id, 'lfb_submit_nonce', true, false);
        $form_markup .= '</form>';

        if ($isolate) {
            $output .= '<div class="lfb-shadow-host" data-lfb-shadow="1" data-lfb-form-id="' . esc_attr((string) $form_id) . '" data-lfb-css="' . esc_attr($css) . '" data-lfb-js="' . esc_attr($js) . '">';
            $output .= '<template>' . $form_markup . '</template>';
            $output .= '</div>';
        } else {
            $output .= $form_markup;
            if ($css !== '') {
                $output .= '<style>#' . esc_attr($wrapper_id) . '{max-width:100%;}' . $css . '@media (max-width:767px){#' . esc_attr($wrapper_id) . ' *{max-width:100%;box-sizing:border-box;}}</style>';
            }
            if ($js !== '') {
                $output .= '<script>(function(){' . $js . '})();</script>';
            }
        }
        $output .= '</div>';

        return $output;
    }

    private static function handle_submission(int $form_id): array {
        if (!isset($_POST['lfb_submit_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lfb_submit_nonce'])), 'lfb_submit_' . $form_id)) {
            return ['message' => __('Security check failed.', 'lead-forms-builder'), 'redirect_url' => '', 'redirect_delay' => 0];
        }

        $payload = [];
        foreach ($_POST as $key => $value) {
            if (in_array($key, ['lfb_form_id', 'lfb_submit_nonce'], true)) {
                continue;
            }
            $payload[sanitize_key((string) $key)] = is_array($value)
                ? array_map('sanitize_text_field', wp_unslash($value))
                : sanitize_text_field(wp_unslash($value));
        }
        foreach (self::extract_checkbox_field_names($form_id) as $field_name) {
            if (!array_key_exists($field_name, $payload)) {
                $payload[$field_name] = '0';
            }
        }

        $lead_id = wp_insert_post([
            'post_type' => 'lfb_lead',
            'post_status' => 'publish',
            'post_title' => sprintf(__('Lead - Form #%d - %s', 'lead-forms-builder'), $form_id, wp_date('Y-m-d H:i:s')),
        ]);

        if (is_wp_error($lead_id)) {
            return ['message' => __('Failed to save lead.', 'lead-forms-builder'), 'redirect_url' => '', 'redirect_delay' => 0];
        }

        update_post_meta($lead_id, '_lfb_form_id', $form_id);
        update_post_meta($lead_id, '_lfb_payload', $payload);
        update_post_meta($lead_id, '_lfb_created_at', current_time('mysql'));

        $notify_email = (string) get_post_meta($form_id, '_lfb_notify_email', true);
        if (is_email($notify_email)) {
            $subject = sprintf(__('New lead from form #%d', 'lead-forms-builder'), $form_id);
            $lines = [
                sprintf(__('Form ID: %d', 'lead-forms-builder'), $form_id),
                sprintf(__('Lead ID: %d', 'lead-forms-builder'), (int) $lead_id),
                sprintf(__('Submitted at: %s', 'lead-forms-builder'), (string) get_post_meta($lead_id, '_lfb_created_at', true)),
                '',
                __('Payload:', 'lead-forms-builder'),
            ];
            foreach ($payload as $k => $v) {
                $lines[] = (string) $k . ': ' . (is_array($v) ? implode(', ', $v) : (string) $v);
            }
            wp_mail($notify_email, $subject, implode("\n", $lines));
        }

        $default_message = __('Thank you! We received your details.', 'lead-forms-builder');
        $success_message = (string) get_post_meta($form_id, '_lfb_success_message', true);
        $action = (string) get_post_meta($form_id, '_lfb_after_submit_action', true);
        $redirect_url = (string) get_post_meta($form_id, '_lfb_redirect_url', true);
        $redirect_delay = (int) get_post_meta($form_id, '_lfb_redirect_delay', true);
        if (!in_array($action, ['message', 'redirect', 'message_redirect'], true)) {
            $action = 'message';
        }
        if (!wp_http_validate_url($redirect_url)) {
            $redirect_url = '';
        }

        $result = ['message' => $success_message !== '' ? $success_message : $default_message, 'redirect_url' => '', 'redirect_delay' => max(0, min(30, $redirect_delay))];
        if ('redirect' === $action && $redirect_url !== '') {
            $result['message'] = '';
            $result['redirect_url'] = $redirect_url;
        } elseif ('message_redirect' === $action && $redirect_url !== '') {
            $result['redirect_url'] = $redirect_url;
        }

        return $result;
    }

    private static function extract_checkbox_field_names(int $form_id): array {
        $html = (string) get_post_meta($form_id, '_lfb_html', true);
        if ($html === '') {
            return [];
        }

        $matches = [];
        preg_match_all('/<input[^>]*type=["\\\']checkbox["\\\'][^>]*name=["\\\']([^"\\\']+)["\\\']/i', $html, $matches);
        if (empty($matches[1])) {
            return [];
        }

        $names = [];
        foreach ($matches[1] as $raw_name) {
            $normalized = sanitize_key(str_replace('[]', '', (string) $raw_name));
            if ($normalized !== '') {
                $names[] = $normalized;
            }
        }

        return array_values(array_unique($names));
    }

    private static function sanitize_form_html(string $html): string {
        $allowed = wp_kses_allowed_html('post');
        $allowed['form'] = [
            'action' => true,
            'method' => true,
            'class' => true,
            'id' => true,
            'enctype' => true,
            'novalidate' => true,
            'target' => true,
        ];
        $allowed['input'] = [
            'type' => true,
            'name' => true,
            'value' => true,
            'class' => true,
            'id' => true,
            'placeholder' => true,
            'required' => true,
            'checked' => true,
            'readonly' => true,
            'disabled' => true,
            'min' => true,
            'max' => true,
            'step' => true,
            'pattern' => true,
            'autocomplete' => true,
            'maxlength' => true,
            'minlength' => true,
            'aria-label' => true,
            'aria-required' => true,
        ];
        $allowed['select'] = [
            'name' => true,
            'class' => true,
            'id' => true,
            'required' => true,
            'multiple' => true,
            'disabled' => true,
            'aria-label' => true,
        ];
        $allowed['option'] = [
            'value' => true,
            'selected' => true,
            'disabled' => true,
            'label' => true,
        ];
        $allowed['textarea'] = [
            'name' => true,
            'class' => true,
            'id' => true,
            'placeholder' => true,
            'required' => true,
            'rows' => true,
            'cols' => true,
            'maxlength' => true,
            'minlength' => true,
            'disabled' => true,
            'aria-label' => true,
        ];
        $allowed['button'] = [
            'type' => true,
            'class' => true,
            'id' => true,
            'name' => true,
            'value' => true,
            'disabled' => true,
        ];
        $allowed['label'] = [
            'for' => true,
            'class' => true,
            'id' => true,
        ];

        return wp_kses($html, $allowed);
    }


    private static function get_default_template_html(): string {
        return '<div class="lfb-demo-form">
'
            . '<label>שם מלא<br><input type="text" name="full_name" required></label><br>
'
            . '<label>טלפון<br><input type="tel" name="phone" required></label><br>
'
            . '<label>מייל<br><input type="email" name="email" required></label><br>
'
            . '<label>תחום עיסוק<br><input type="text" name="occupation"></label><br>
'
            . '<label class="lfb-terms-label"><input type="checkbox" name="terms_approved" value="1" required> מאשר/ת את תנאי השימוש ומדיניות הפרטיות</label><br>
'
            . '<button type="submit" class="lfb-facebook-btn">אני רוצה קמפיינר אוטומטי שמייצר תוצאות</button>
'
            . '</div>';
    }

    private static function get_default_template_css(): string {
        return '.lfb-demo-form,.lfb-demo-form *{box-sizing:border-box;}
'
            . '.lfb-demo-form{display:grid;gap:12px;max-width:520px;direction:rtl;}
'
            . '.lfb-demo-form label{display:block;font-weight:600;color:#1d2327;}
'
            . '.lfb-demo-form input[type="text"],.lfb-demo-form input[type="tel"],.lfb-demo-form input[type="email"]{display:block;width:100%;min-height:46px;padding:12px;background:#fff !important;color:#1d2327;border:1px solid #ccd0d4 !important;border-radius:8px;outline:none;opacity:1 !important;visibility:visible !important;}
'
            . '.lfb-demo-form input[type="text"]:focus,.lfb-demo-form input[type="tel"]:focus,.lfb-demo-form input[type="email"]:focus{border-color:#2271b1 !important;box-shadow:0 0 0 1px #2271b1;}
'
            . '.lfb-demo-form input[type="checkbox"]{appearance:auto !important;-webkit-appearance:checkbox !important;width:18px;height:18px;vertical-align:middle;margin-left:8px;opacity:1 !important;visibility:visible !important;accent-color:#1877f2;}
'
            . '.lfb-demo-form .lfb-terms-label{display:flex;align-items:center;gap:8px;font-weight:500;}
'
            . '.lfb-demo-form .lfb-facebook-btn{background:#1877f2;color:#fff;border:0;border-radius:8px;padding:14px 16px;font-weight:700;cursor:pointer;}
'
            . '.lfb-demo-form .lfb-facebook-btn:hover{background:#166fe5;}';
    }

    public static function register_leads_page(): void {
        add_submenu_page(
            'edit.php?post_type=lfb_form',
            __('Leads', 'lead-forms-builder'),
            __('Leads', 'lead-forms-builder'),
            'manage_options',
            'lfb-leads',
            [self::class, 'render_leads_page']
        );
    }

    public static function render_leads_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $selected_form_id = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
        $forms = get_posts(['post_type' => 'lfb_form', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);

        $meta_query = [];
        if ($selected_form_id > 0) {
            $meta_query[] = ['key' => '_lfb_form_id', 'value' => $selected_form_id, 'compare' => '='];
        }

        $leads = get_posts([
            'post_type' => 'lfb_lead',
            'posts_per_page' => 200,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => $meta_query,
        ]);

        echo '<div class="wrap"><h1>' . esc_html__('Leads', 'lead-forms-builder') . '</h1>';
        echo '<form method="get" style="margin:16px 0;">';
        echo '<input type="hidden" name="post_type" value="lfb_form">';
        echo '<input type="hidden" name="page" value="lfb-leads">';
        echo '<select name="form_id"><option value="0">' . esc_html__('All forms', 'lead-forms-builder') . '</option>';
        foreach ($forms as $form) {
            echo '<option value="' . (int) $form->ID . '" ' . selected($selected_form_id, (int) $form->ID, false) . '>' . esc_html($form->post_title) . ' (#' . (int) $form->ID . ')</option>';
        }
        echo '</select> <button class="button button-primary" type="submit">' . esc_html__('Filter', 'lead-forms-builder') . '</button>';

        $export_url = wp_nonce_url(admin_url('admin-post.php?action=lfb_export_leads&form_id=' . $selected_form_id), 'lfb_export_leads');
        echo ' <a class="button" href="' . esc_url($export_url) . '">' . esc_html__('Export CSV', 'lead-forms-builder') . '</a>';
        echo '</form>';

        echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Form</th><th>Submitted At</th><th>Data</th></tr></thead><tbody>';
        if (!$leads) {
            echo '<tr><td colspan="4">' . esc_html__('No leads found.', 'lead-forms-builder') . '</td></tr>';
        } else {
            foreach ($leads as $lead) {
                $form_id = (int) get_post_meta($lead->ID, '_lfb_form_id', true);
                $payload = (array) get_post_meta($lead->ID, '_lfb_payload', true);
                $created_at = (string) get_post_meta($lead->ID, '_lfb_created_at', true);
                $pairs = [];
                foreach ($payload as $k => $v) {
                    $pairs[] = esc_html((string) $k . ': ' . (is_array($v) ? implode(', ', $v) : $v));
                }
                echo '<tr><td>' . (int) $lead->ID . '</td><td>#' . $form_id . '</td><td>' . esc_html($created_at) . '</td><td>' . implode('<br>', $pairs) . '</td></tr>';
            }
        }
        echo '</tbody></table></div>';
    }

    public static function export_leads_csv(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'lead-forms-builder'));
        }
        check_admin_referer('lfb_export_leads');

        $selected_form_id = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
        $meta_query = [];
        if ($selected_form_id > 0) {
            $meta_query[] = ['key' => '_lfb_form_id', 'value' => $selected_form_id, 'compare' => '='];
        }

        $leads = get_posts([
            'post_type' => 'lfb_lead',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => $meta_query,
        ]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=lfb-leads-' . wp_date('Ymd-His') . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['lead_id', 'form_id', 'submitted_at', 'payload_json']);
        foreach ($leads as $lead) {
            fputcsv($out, [
                $lead->ID,
                (int) get_post_meta($lead->ID, '_lfb_form_id', true),
                (string) get_post_meta($lead->ID, '_lfb_created_at', true),
                wp_json_encode((array) get_post_meta($lead->ID, '_lfb_payload', true), JSON_UNESCAPED_UNICODE),
            ]);
        }
        fclose($out);
        exit;
    }
}
