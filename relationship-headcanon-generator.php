<?php
/**
 * Plugin Name:       Relationship Headcanon Generator
 * Plugin URI:        https://catchaiinfo.com/tools/relationship-headcanon-generator/
 * Description:       An interactive, viral Fandom & Shipping Relationship Headcanon Generator tool. Generates romantic, angst, comedy, AU, and fluff scenarios with instant image cards, social sharing, and SEO backlink embed widgets.
 * Version:           1.0.0
 * Author:            Zubayer-web
 * Author URI:        https://github.com/Zubayer-web
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       relationship-headcanon-generator
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define Plugin Constants
define('RHG_VERSION', '1.0.0');
define('RHG_PLUGIN_FILE', __FILE__);
define('RHG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RHG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RHG_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Require Core Class
require_once RHG_PLUGIN_DIR . 'includes/class-rhg-core.php';

/**
 * Plugin activation hook.
 */
function rhg_activate_plugin() {
    require_once RHG_PLUGIN_DIR . 'includes/class-rhg-prompts.php';
    RHG_Prompts::init_default_data();

    // Default settings
    if (!get_option('rhg_settings')) {
        $default_settings = [
            'primary_color'       => '#ff4b72',
            'secondary_color'     => '#6c5ce7',
            'default_person_a'    => 'Person A',
            'default_person_b'    => 'Person B',
            'enable_social_share' => '1',
            'enable_image_card'   => '1',
            'enable_favorites'    => '1',
            'enable_embed_code'   => '1',
            'backlink_anchor'     => 'Relationship Headcanon Generator',
            'backlink_url'        => 'https://catchaiinfo.com/tools/relationship-headcanon-generator/',
            'ad_slot_top'         => '',
            'ad_slot_bottom'      => '',
            'custom_css'          => '',
        ];
        update_option('rhg_settings', $default_settings);
    }
}
register_activation_hook(__FILE__, 'rhg_activate_plugin');

/**
 * Plugin deactivation hook.
 */
function rhg_deactivate_plugin() {
    // Flush rewrite rules or temporary transients if needed
}
register_deactivation_hook(__FILE__, 'rhg_deactivate_plugin');

/**
 * Initialize the plugin.
 */
function rhg_run_plugin() {
    $plugin = new RHG_Core();
    $plugin->run();
}
rhg_run_plugin();
