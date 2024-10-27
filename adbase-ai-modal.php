<?php
/**
 * SendSquared Plugin.
 *
 * This is the plugin root.
 *
 * @link              https://wordpress.org/plugins/adbase-ai-popup-growth/
 * @since             1.0.12
 * @package           SendSquared
 *
 * @wordpress-plugin
 * Plugin Name:       SendSquared
 * Plugin URI:        https://wordpress.org/plugins/adbase-ai-popup-growth/
 * Description:       Enables you to install popups, email posts, install subscribe forms and lightweight analytics.  The design and data focused email marketing platform.
 * Version:           1.0.12
 * Author:            SendSquared
 * Author URI:        https://sendsquared.com/
 * Text Domain:       sendsquared
 */

/**
 *
 */
class AdBaseWpGrowth
{

    private $send_squared_host = 'https://app-api.sendsquared.com';
    // private $send_squared_host = 'http://localhost:3000';
    private $adbase_tracking_path = 'tracking/v1/bse-analytics-es3.js';
    private $adbase_popup_path = 'popup/v1/bse-popup.js';
    private $adbase_popup_post = 'v1/pub/popup/';

    /**
     * Constructor
     */
    public function __construct()
    {

        // Plugin Details
        $this->plugin = new stdClass;
        $this->plugin->name = 'ad-base-wordpress-modal'; // Plugin Folder
        $this->plugin->displayName = 'SendSquared'; // Plugin Name
        $this->plugin->version = '1.0.12';
        $this->plugin->folder = plugin_dir_path(__FILE__);
        $this->plugin->url = plugin_dir_url(__FILE__);
        $this->plugin->db_welcome_dismissed_key = $this->plugin->name . '_welcome_dismissed_key';

        // Check if the global wpb_feed_append variable exists. If not, set it.
        if (!array_key_exists('wpb_feed_append', $GLOBALS)) {
            $GLOBALS['wpb_feed_append'] = false;
        }

        // Hooks
        add_action('admin_init', array(&$this, 'registerSettings'));
        add_action('admin_menu', array(&$this, 'adminPanelsAndMetaBoxes'));
        add_action('admin_notices', array(&$this, 'dashboardNotices'));
        add_action('wp_ajax_' . $this->plugin->name . '_dismiss_dashboard_notices', array(&$this, 'dismissDashboardNotices'));
        add_action('save_post', array(&$this, 'send_squared_mail_new_posts'));

        // Frontend Hooks
        add_action('wp_enqueue_scripts', array(&$this, 'adbaseFooterEnqueue'));
        add_action('wp_footer', array(&$this, 'adbaseFrontEndFooter'));
        add_shortcode('adb_form', array(&$this, 'createForm'));
    }

    /**
     * Show relevant notices for the plugin
     */
    public function dashboardNotices()
    {
        global $pagenow;
        if (!get_option($this->plugin->db_welcome_dismissed_key)) {
            if (!($pagenow == 'options-general.php' && isset($_GET['page']) && $_GET['page'] == 'ad-base-modal')) {
                $setting_page = admin_url('options-general.php?page=' . $this->plugin->name);
                // load the notices view
                include_once $this->plugin->folder . '/views/dashboard-notices.php';
            }
        }
    }

    /**
     * Dismiss the welcome notice for the plugin
     */
    public function dismissDashboardNotices()
    {
        check_ajax_referer($this->plugin->name . '-nonce', 'nonce');
        // user has dismissed the welcome notice
        update_option($this->plugin->db_welcome_dismissed_key, 1);
        exit;
    }

    /**
     * Register Settings
     */
    public function registerSettings()
    {
        // Popup
        register_setting($this->plugin->name, 'adbm_popup_token', 'trim');
        register_setting($this->plugin->name, 'adbm_popup_id', 'trim');

        // Inlay form
        register_setting($this->plugin->name, 'adbm_group_token', 'trim');
        register_setting($this->plugin->name, 'adbm_form_title', 'trim');
        register_setting($this->plugin->name, 'adbm_form_input_type', 'trim');

        // Input styles and classes
        register_setting($this->plugin->name, 'adbm_form_input_class', 'trim');
        register_setting($this->plugin->name, 'adbm_form_input_style', 'trim');

        register_setting($this->plugin->name, 'adbm_form_button_class', 'trim');
        register_setting($this->plugin->name, 'adbm_form_button_style', 'trim');
    }

    public function get_templates()
    {
        $endpoint = $this->send_squared_host . '/v1/pub/wp/fetch-templates';
        $popup_token = esc_html(wp_unslash(get_option('adbm_popup_token')));
        $args = array(
            'method' => 'GET',
            'timeout' => 45,
            'headers' => array(
                'adbase-popup-token' => $popup_token,
                'Content-Type' => 'application/json',
            ),
        );
        $request = wp_remote_get($endpoint, $args);
        if (is_wp_error($request)) {
            return false; // Bail early
        }
        $body = wp_remote_retrieve_body($request);
        return json_decode($body);
    }

    public function get_groups()
    {
        $endpoint = $this->send_squared_host . '/v1/pub/wp/fetch-groups';
        $popup_token = esc_html(wp_unslash(get_option('adbm_popup_token')));
        $args = array(
            'method' => 'GET',
            'timeout' => 45,
            'headers' => array(
                'adbase-popup-token' => $popup_token,
                'Content-Type' => 'application/json',
            ),
        );
        $request = wp_remote_get($endpoint, $args);

        if (is_wp_error($request)) {
            return false; // Bail early
        }
        $body = wp_remote_retrieve_body($request);
        return json_decode($body);
    }

    public function send_squared_mail_new_posts($post_id)
    {
        // Check setting - if it's not turned on... return
        if (get_option('adbm_posts_campaign') != 'true') {
            return;
        }

        if (get_post_status($post_id) != 'publish') {
            return;
        }

        // If this is a revision, get real post ID
        if ($parent_id = wp_is_post_revision($post_id)) {
            $post_id = $parent_id;
        }

        // Get the contents
        $post = get_post($post_id);
        $post_meta = get_post_meta($post_id);

        if ($post->post_type != 'post') {
            return;
        }

        // we need to have a toggle which determine if the the post has been emailed yet or not.

        $smart_send = esc_html(wp_unslash(get_option('adbm_posts_smart_send')));
        $template = (int) esc_html(wp_unslash(get_option('adbm_posts_template')));
        $groups = [];
        foreach ($this->get_groups() as $group) {
            $group_data = esc_html(wp_unslash(get_option('adbm_posts_selected_group_' . $group->id)));
            if ($group_data == 'true') {
                $groups[] = $group->id;
            }
        }

        // thumbnail
        $thumbnail = get_the_post_thumbnail_url($post_id, ['', '600']);
        $post_date = get_post_datetime($post_id);
        $post_url = get_permalink($post_id);
        $post_excerpt = get_the_excerpt($post_id);

        // Make the API call to Send Squared.
        $popup_token = esc_html(wp_unslash(get_option('adbm_popup_token')));
        $endpoint = $this->send_squared_host . '/v1/pub/wp/email-single-post';
        $body = [
            'id' => $post->ID,
            'status' => $post->post_status,
            'content' => $post->post_content,
            'contentText' => wp_strip_all_tags($post->post_content, true),
            'excerpt' => $post_excerpt,
            'featuredImage' => $thumbnail,
            'title' => $post->post_title,
            'url' => $post_url,
            'postDate' => $post_date,
            // 'meta' => $post_meta,
            // 'raw-post' => $post,
            'smart_send' => $smart_send,
            'template' => $template,
            'groups' => $groups,
        ];
        $args = array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'blocking' => false,
            'headers' => array(
                // 'Authorization' => 'Bearer {private token goes here!!!!}',
                'adbase-popup-token' => $popup_token,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($body),
        );
        wp_remote_post($endpoint, $args);
    }

    /**
     * Register the plugin settings panel
     */
    public function adminPanelsAndMetaBoxes()
    {
        add_submenu_page('options-general.php', $this->plugin->displayName, $this->plugin->displayName, 'manage_options', $this->plugin->name, array(&$this, 'adminPanel'));
    }

    /**
     * Output the Administration Panel
     * Save POSTed data from the Administration Panel into a WordPress option
     */
    public function adminPanel()
    {
        // only admin user can access this page
        if (!current_user_can('administrator')) {
            echo '<p>' . __('Sorry, you are not allowed to access this page.', 'ad-base-modal') . '</p>';
            return;
        }

        $groups_source = $this->get_groups();
        $templates_source = $this->get_templates();

        // Save Token & default popup id
        if (isset($_REQUEST['submit'])) {
            // Check nonce
            if (!isset($_REQUEST[$this->plugin->name . '_nonce'])) {
                // Missing nonce
                $this->errorMessage = __('nonce field is missing. Settings NOT saved.', 'ad-base-modal');
            } elseif (!wp_verify_nonce($_REQUEST[$this->plugin->name . '_nonce'], $this->plugin->name)) {
                // Invalid nonce
                $this->errorMessage = __('Invalid nonce specified. Settings NOT saved.', 'ad-base-modal');
            } else {
                // Save
                // $_REQUEST has already been slashed by wp_magic_quotes in wp-settings
                // so do nothing before saving
                $popupToken = sanitize_text_field($_REQUEST['adbm_popup_token']);

                // reject if it's not a valid UUID
                if (!$this->isValidUuid($popupToken)) {
                    $this->errorMessage = __('Invalid token specified. Settings NOT saved.', 'ad-base-modal');
                    $popupToken = '';
                }

                $popupId = 0;
                if (isset($_REQUEST['adbm_popup_id'])) {
                    $popupId = (int) sanitize_text_field($_REQUEST['adbm_popup_id']);
                }

                update_option('adbm_popup_token', $popupToken);
                update_option('adbm_popup_id', $popupId);
                update_option($this->plugin->db_welcome_dismissed_key, 1);
                $this->message = __('Settings Saved.', 'ad-base-modal');
            }
        }

        // Save inlay form settings
        if (isset($_REQUEST['submit-inlay'])) {
            // Check nonce
            if (!isset($_REQUEST[$this->plugin->name . '_inlay_nonce'])) {
                // Missing nonce
                $this->errorMessage = __('nonce field is missing. Settings NOT saved.', 'ad-base-modal');
            } elseif (!wp_verify_nonce($_REQUEST[$this->plugin->name . '_inlay_nonce'], $this->plugin->name)) {
                // Invalid nonce
                $this->errorMessage = __('Invalid nonce specified. Settings NOT saved.', 'ad-base-modal');
            } else {
                // Save
                // $_REQUEST has already been slashed by wp_magic_quotes in wp-settings
                // so do nothing before saving

                $group_token = sanitize_text_field($_REQUEST['adbm_group_token']);

                // reject if it's not a valid UUID
                if (!$this->isValidUuid($group_token)) {
                    $group_token = '';
                }

                $form_title = sanitize_text_field($_REQUEST['adbm_form_title']);
                $form_type = sanitize_text_field($_REQUEST['adbm_form_input_type']);
                $form_input_name = isset($_REQUEST['adbm_form_input_name']) ? 'true' : 'false';
                $form_input_gender = isset($_REQUEST['adbm_form_input_gender']) ? 'true' : 'false';
                $form_input_zip_code = isset($_REQUEST['adbm_form_input_zip_code']) ? 'true' : 'false';

                $form_input_class = sanitize_text_field($_REQUEST['adbm_form_input_class']);
                $form_input_style = sanitize_text_field($_REQUEST['adbm_form_input_style']);
                $form_button_class = sanitize_text_field($_REQUEST['adbm_form_button_class']);
                $form_button_style = sanitize_text_field($_REQUEST['adbm_form_button_style']);

                update_option('adbm_group_token', $group_token);
                update_option('adbm_form_title', $form_title);
                update_option('adbm_form_input_type', $form_type);

                update_option('adbm_form_input_name', $form_input_name);
                update_option('adbm_form_input_gender', $form_input_gender);
                update_option('adbm_form_input_zip_code', $form_input_zip_code);

                // input style/class
                update_option('adbm_form_input_class', $form_input_class);
                update_option('adbm_form_input_style', $form_input_style);
                update_option('adbm_form_button_class', $form_button_class);
                update_option('adbm_form_button_style', $form_button_style);

                update_option($this->plugin->db_welcome_dismissed_key, 1);
                $this->message = __('Settings Saved.', 'ad-base-modal');
                $_GET['tab'] = 'form';
            }
        }

        // Save inlay form settings
        if (isset($_REQUEST['submit-new-posts'])) {
            // Check nonce
            if (!isset($_REQUEST[$this->plugin->name . '_new_post_nonce'])) {
                // Missing nonce
                $this->errorMessage = __('nonce field is missing. Settings NOT saved.', 'ad-base-modal');
            } elseif (!wp_verify_nonce($_REQUEST[$this->plugin->name . '_new_post_nonce'], $this->plugin->name)) {
                // Invalid nonce
                $this->errorMessage = __('Invalid nonce specified. Settings NOT saved.', 'ad-base-modal');
            } else {
                // enable post campaigns
                $form_email_posts = (isset($_REQUEST['adbm_posts_campaign']) && $_REQUEST['adbm_posts_campaign'] != null) ? 'true' : 'false';
                update_option('adbm_posts_campaign', $form_email_posts);

                // Smart send
                $form_smart_send = (isset($_REQUEST['adbm_posts_smart_send']) && $_REQUEST['adbm_posts_smart_send'] != null) ? 'true' : 'false';
                update_option('adbm_posts_smart_send', $form_smart_send);

                // Template
                $template_id = (int) $_REQUEST['adbm_posts_template'];
                update_option('adbm_posts_template', $template_id);

                // parse through each group.
                foreach ($groups_source as $group) {
                    $form_group = (isset($_REQUEST['adbm_posts_selected_group_' . $group->id]) && $_REQUEST['adbm_posts_selected_group_' . $group->id] != null) ? 'true' : 'false';
                    update_option('adbm_posts_selected_group_' . $group->id, $form_group);
                }

                update_option($this->plugin->db_welcome_dismissed_key, 1);
                $this->message = __('Settings Saved.', 'ad-base-modal');
                $_GET['tab'] = 'posts';
            }
        }

        // Get latest settings
        $this->settings = array(
            'adbm_popup_token' => esc_html(wp_unslash(get_option('adbm_popup_token'))),
            'adbm_popup_id' => esc_html(wp_unslash(get_option('adbm_popup_id'))),

            // input form
            'adbm_group_token' => esc_html(wp_unslash(get_option('adbm_group_token'))),
            'adbm_form_title' => esc_html(wp_unslash(get_option('adbm_form_title'))),
            'adbm_form_input_type' => esc_html(wp_unslash(get_option('adbm_form_input_type'))),

            'adbm_form_input_name' => esc_html(wp_unslash(get_option('adbm_form_input_name'))),
            'adbm_form_input_gender' => esc_html(wp_unslash(get_option('adbm_form_input_gender'))),
            'adbm_form_input_zip_code' => esc_html(wp_unslash(get_option('adbm_form_input_zip_code'))),

            'adbm_form_input_class' => esc_html(wp_unslash(get_option('adbm_form_input_class'))),
            'adbm_form_input_style' => esc_html(wp_unslash(get_option('adbm_form_input_style'))),
            'adbm_form_button_class' => esc_html(wp_unslash(get_option('adbm_form_button_class'))),
            'adbm_form_button_style' => esc_html(wp_unslash(get_option('adbm_form_button_style'))),

            // Mailer
            'adbm_posts_campaign' => esc_html(wp_unslash(get_option('adbm_posts_campaign'))),
            'adbm_posts_smart_send' => esc_html(wp_unslash(get_option('adbm_posts_smart_send'))),

            'adbm_posts_groups' => $groups_source,
            'adbm_posts_templates' => $templates_source,

            'adbm_posts_template' => (int) esc_html(wp_unslash(get_option('adbm_posts_template'))),
        );

        // append each group
        if ($groups_source) {
            foreach ($groups_source as $group) {
                $this->settings['adbm_posts_selected_group_' . $group->id] = esc_html(wp_unslash(get_option('adbm_posts_selected_group_' . $group->id)));
            }
        }

        // Load Settings Form
        include_once $this->plugin->folder . '/views/settings.php';
    }

    /**
     * Loads plugin textdomain
     */
    public function loadLanguageFiles()
    {
        load_plugin_textdomain('ad-base-modal', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Outputs script / CSS to the frontend footer
     */
    public function adbaseFrontEndFooter()
    {
        $token = get_option('adbm_popup_token');
        $popupId = get_option('adbm_popup_id');

        // Ignore admin, feed, robots or trackbacks
        if (is_admin() || is_feed() || is_robots() || is_trackback()) {
            return;
        }

        // provide the opportunity to Ignore IHAF - both headers and footers via filters
        if (apply_filters('disable_adbm', false)) {
            return;
        }

        // validate inputs
        if (empty($token) || trim($token) == '' || !$this->isValidUuid($token)) {
            return;
        }
        if (empty($popupId) || trim($popupId) == '' || !is_numeric($popupId)) {
            return;
        }

        wp_register_script('adb_event_listener', '');
        wp_enqueue_script('adb_event_listener');
        wp_add_inline_script('adb_event_listener', $this->getPopupScript($token, $popupId));

    }

    /**
     *
     */
    public function adbaseFooterEnqueue()
    {
        wp_enqueue_script('adbase_tracking_script', $this->send_squared_host . '/' . $this->adbase_tracking_path, array(), $this->plugin->version, true);
        wp_enqueue_script('adbase_popup_script', $this->send_squared_host . '/' . $this->adbase_popup_path, array(), $this->plugin->version, true);
    }

    /**
     * Validates UUID
     *
     * @param $uuid
     */
    private function isValidUuid($uuid)
    {
        if (!is_string($uuid) || (preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', $uuid) !== 1)) {
            return false;
        }
        return true;
    }

    /**
     *
     */
    private function getPopupScript($token, $popupId)
    {

        $id = (int) $popupId;
        return <<<JAVASCRIPT
			document.addEventListener('DOMContentLoaded', async function(event) {
				AdbPopup.init('{$token}', {$id});
			});
JAVASCRIPT;
    }

    private function getPhoneEmailForm($input_class, $input_style)
    {
        $parentStyle = 'width: 50%; margin-left: 20%;';
        $child_style = 'display:block; width:100%;';
        return <<<HTML
		<div style="{$parentStyle}">
			<label style="{$child_style}"> Email Address: </label>
			<input type="text" name="adbase-email" class="{$input_class}" style="{$child_style}{$input_style}"
				id="adbase-email">
		</div>
HTML;
    }
    private function getPhoneNumberForm($input_class, $input_style)
    {
        $parentStyle = 'width: 50%; margin-left: 20%;';
        $child_style = 'display:block; width:100%;';
        return <<<HTML
		<div style="{$parentStyle}">
			<label style="{$child_style}"> Phone Number: </label>
			<input type="text" name="adbase-phone" class="{$input_class}" style="{$child_style}{$input_style}"
				id="adbase-phone">
		</div>
HTML;
    }
    private function getNameForm($input_class, $input_style)
    {
        $parentStyle = 'width: 50%; margin-left: 20%;';
        $child_style = 'display:block; width:100%;';
        return <<<HTML
		<div style="{$parentStyle}">
			<label style="{$child_style}"> First Name: </label>
			<input type="text" name="adbase-first-name" class="{$input_class}" style="{$child_style}{$input_style}"
				id="adbase-first-name">
		</div>
		<div style="{$parentStyle}">
			<label style="{$child_style}"> Last Name: </label>
			<input type="text" name="adbase-last-name" class="{$input_class}" style="{$child_style}{$input_style}"
				id="adbase-last-name">
		</div>
HTML;
    }

    public function createForm()
    {
        // Ignore admin, feed, robots or trackbacks
        if (is_admin() || is_feed() || is_robots() || is_trackback() || !is_page()) {
            return;
        }

        $groupToken = wp_unslash(get_option('adbm_group_token'));
        $formTitle = wp_unslash(get_option('adbm_form_title'));
        $emailPhoneType = get_option('adbm_form_input_type');

        $askForName = get_option('adbm_form_input_name');
        $askForGender = get_option('adbm_form_input_gender');
        $askForZipCode = get_option('adbm_form_input_zip_code');

        $input_class = get_option('adbm_form_input_class');
        $input_style = get_option('adbm_form_input_style');
        $button_class = get_option('adbm_form_button_class');
        $button_style = get_option('adbm_form_button_style');

        $leTitleForm = '';
        if (strlen(trim($formTitle)) > 0) {
            $leTitleForm = <<<HTML
			<h2 style="text-align: center;">{$formTitle}</h2>
HTML;
        }
        $leTypeInput = '';
        switch ($emailPhoneType) {
            case "email":
            default:
                $leTypeInput = $this->getPhoneEmailForm($input_class, $input_style);
                break;
            case "phone":
                $leTypeInput = $this->getPhoneNumberForm($input_class, $input_style);
                break;
            case "both":
                $leTypeInput = $this->getPhoneEmailForm($input_class, $input_style);
                $leTypeInput .= $this->getPhoneNumberForm($input_class, $input_style);
                break;
        }

        $leNameInput = '';
        if ($askForName && $askForName == 'true') {
            $leNameInput = $this->getNameForm($input_class, $input_style);
        }

        $html = <<<HTML
		{$leTitleForm}
		<form id="adbase-contact-inlay-form">
			{$leNameInput}
			{$leTypeInput}
			<label style="display:block; text-align:center; margin-top:1em;">
				<input type="button" style={$button_style} class="{$button_class}" id="adbase-contact-input" value="Submit" />
			</label>
		</form>
		<script>
			function getValue(id){
				let phone = '';
				try{
					phone = document.getElementById(id).value;
				} catch (e) {
					console.log(e);
				}
				return phone;
			}
			function postEmail(url, content) {
				const t = this;
				const xhr = new XMLHttpRequest();
				xhr.open('POST', url);
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				xhr.onload = function() {
				if (xhr.status === 200) {
					document.getElementById("adbase-contact-inlay-form").innerHTML = '<h2>Thank you for joining our mailing list!</h2>';
					// t.completed = 1;
				} else {
					console.log('Request failed.  Returned status of ' + xhr.status);
				}
				};
				// Build up URL
				let query = '';
				for (const key in content) {
				let adAmp = '';
				if (query.length > 0) {
					adAmp = '&';
				}
				query +=
					adAmp +
					encodeURIComponent(key) +
					'=' +
					encodeURIComponent(content[key]);
				}
				xhr.send(encodeURI(query));
			}
			document.addEventListener('DOMContentLoaded', async function(event) {
				document.getElementById('adbase-contact-input').onclick = function changeContent() {
					const email = getValue("adbase-email");
					const phone = getValue("adbase-phone");
					const fName = getValue("adbase-first-name");
					const lName = getValue("adbase-last-name");
					postEmail('{$this->send_squared_host}/{$this->adbase_popup_post}?token={$groupToken}', {
						email: email,
						phone: phone,
						firstName: fName,
						lastName: lName
					});
				};
			});
		</script>
HTML;
        return $html;
    }
}

$adbase = new AdBaseWpGrowth();
