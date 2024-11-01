<?php
/*
  Plugin Name: WK URL 2 WP Thumb
  Plugin URI: http://winkyapps.net/wk-url2wpthumb
  Description: Let you add a post thumbnail form a external link.
  Author: Mohamed Tawfik
  Author URI: http://winkyapps.net/
 */

define("WKTD", "wk-url2wpthumb");

// Check if the class exists
if (!class_exists("wk_url2wpthumb")) {

    class wk_url2wpthumb {

        public function __construct() {
            // Add metabox
            add_action('add_meta_boxes', array($this, 'url2thumb_metabox'));
            // Load textdomain for Multi-Language support
            load_plugin_textdomain('wk-url2wpthumb', FALSE, basename(dirname(__FILE__)) . '/language/');
            // Styles and Javascript
            add_action('admin_enqueue_scripts', array($this, 'wk_styles_scripts'));
            // Ajax Actions
            add_action('wp_ajax_wk_update_thumb', array($this, 'wk_update_thumb'));
            add_action('wp_ajax_wk_url2thumb_save_settings', array($this, 'wk_url2thumb_save_settings'));
            // Register Admin Menu
            add_action('admin_menu', array($this, 'wk_admin_menu'));
        }

        // Enqueue Styles and Scripts
        function wk_styles_scripts() {
            // Load Styles
            wp_enqueue_style("wk_style", plugin_dir_url(__FILE__) . "css/style.css");

            // Load Scripts and Define AJAX
            wp_enqueue_script('wk_ajax', plugin_dir_url(__FILE__) . "js/functions.js", array('jquery'));
            wp_localize_script('wk_ajax', 'wk_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
        }

        // Metabox for inserting the url
        function url2thumb_metabox($post_type) {
            $allowed_post_type = array();

            $allowed_pt = get_option("wk_url2thumb");
            if (is_array($allowed_pt)) {
                foreach ($allowed_pt as $pt) {
                    $allowed_post_type[] = $pt['name'];
                }
            }

            if (in_array($post_type, $allowed_post_type)) {
                add_meta_box('wk-url2wpthumb_mtbx', __('Featured Image URL', WKTD), array($this, 'url2thumb_callback'), $post_type, 'side', 'low');
            }
        }

        // Register Admin Menu
        function wk_admin_menu() {
            // Settings page
            add_submenu_page("options-general.php", __("WK Url2Featured", WKTD), __("WK Url2Featured", "wk-url2wpthumb"), "manage_options", "wk-custom-mtbx", array($this, 'wk_url2thumb_settings'), "dashicons-welcome-widgets-menus");
        }

        // Setting page
        function wk_url2thumb_settings() {

            $allowed_pt = get_option("wk_url2thumb");
            foreach ((array) $allowed_pt as $pt) {
                $allowed_post_type[$pt['name']] = $pt['value'];
            }
            ?>
            <div class="wk_url2thumb_setting_page">
                <h3><? _e("Wk Url 2 Featured Image", WKTD) ?></h3>
                <h4><? _e("Select Post Types to show the widget", WKTD) ?></h4>

                <div class="wk_url2wpthumb_loader"><img src="../wp-includes/images/wpspin-2x.gif" /></div>

                <form id="wk_url2thumb_form">
                    <label>
                        <input type="checkbox" name="post"<? if ($allowed_post_type['post'] == "on") echo ' checked="checked"'; ?> />
                        <? _e("Post", "wkcpt") ?>
                    </label>
                    <label>
                        <input type="checkbox" name="page"<? if ($allowed_post_type['page'] == "on") echo ' checked="checked"'; ?> />
                        <? _e("Page", "wkcpt") ?>
                    </label>
                    <? $post_types = get_post_types(array("_builtin" => false)); ?>
                    <? foreach ($post_types as $post_type): ?>
                        <label>
                            <input type="checkbox" name="<?= $post_type ?>"<? if ($allowed_post_type[$post_type] == "on") echo ' checked="checked"'; ?> />
                            <?= $post_type ?>
                        </label>
                    <? endforeach; ?>
                </form>

                <a href="javascript:;" class="button button-primary button-large" id="wk_url2thumb_save_settings" data-saving="<? _e("Saving...", WKTD) ?>" data-save="<? _e("Save Settings", WKTD) ?>"><? _e("Save Settings", WKTD) ?></a>
            </div>
            <?
        }

        // Save Settings
        function wk_url2thumb_save_settings() {
            update_option("wk_url2thumb", $_POST['post_types']);
            wp_die();
        }

        // url2thumb Metabox content
        function url2thumb_callback() {
            ?>
            <div class="wk-url2wpthumb">
                <input type="text" name="wk_thumb_url" id="wk_thumb_url" placeholder="http://example.com/image.jpg" />
                <a href="javascript:;" id="wk_update_thumb" class="preview button" data-post_id="<? the_ID(); ?>"><? _e('Fetch Image', 'wk-url2wpthumb') ?></a>
                <div class="wk_url2wpthumb_loader"><img src="../wp-includes/images/wpspin-2x.gif" /></div>
            </div>
            <?
        }

        function wk_update_thumb() {
            $post_id = $_POST['post_id'];
            $this->upload_thumb($_POST['url'], $post_id);
            $thumb_id = get_post_thumbnail_id($_POST['post_id']);
            echo _wp_post_thumbnail_html($thumb_id, $post_id);
            wp_die();
        }

        // Upload from url and set as featured image
        function upload_thumb($image_url, $post_id) {
            if ($image_url) {
                $upload_dir = wp_upload_dir();
                $image_data = file_get_contents($image_url);
                $filename = basename($image_url);
                if (wp_mkdir_p($upload_dir['path']))
                    $file = $upload_dir['path'] . '/' . $filename;
                else
                    $file = $upload_dir['basedir'] . '/' . $filename;
                file_put_contents($file, $image_data);

                $wp_filetype = wp_check_filetype($filename, null);
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => sanitize_file_name($filename),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attach_id = wp_insert_attachment($attachment, $file, $post_id);
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $file);
                wp_update_attachment_metadata($attach_id, $attach_data);

                set_post_thumbnail($post_id, $attach_id);
            }
        }

    }

}

new wk_url2wpthumb();
?>
