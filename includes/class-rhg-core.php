<?php
/**
 * Core Plugin Orchestrator Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RHG_Core {

    /**
     * @var RHG_Prompts
     */
    protected $prompts;

    /**
     * @var RHG_Shortcode
     */
    protected $shortcode;

    /**
     * @var RHG_Admin
     */
    protected $admin;

    public function __construct() {
        $this->load_dependencies();
    }

    private function load_dependencies() {
        require_once RHG_PLUGIN_DIR . 'includes/class-rhg-prompts.php';
        require_once RHG_PLUGIN_DIR . 'includes/class-rhg-shortcode.php';

        $this->prompts   = new RHG_Prompts();
        $this->shortcode = new RHG_Shortcode($this->prompts);

        if (is_admin()) {
            require_once RHG_PLUGIN_DIR . 'includes/class-rhg-admin.php';
            $this->admin = new RHG_Admin($this->prompts);
        }
    }

    public function run() {
        // Enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);

        // Register shortcodes
        add_shortcode('relationship_headcanon_generator', [$this->shortcode, 'render']);
        add_shortcode('headcanon_generator', [$this->shortcode, 'render']);

        // Add settings action link on plugins page
        add_filter('plugin_action_links_' . RHG_PLUGIN_BASENAME, [$this, 'add_action_links']);
    }

    public function register_assets() {
        // Register style
        wp_register_style(
            'rhg-style',
            RHG_PLUGIN_URL . 'assets/css/rhg-style.css',
            [],
            RHG_VERSION
        );

        // Register script
        wp_register_script(
            'rhg-generator',
            RHG_PLUGIN_URL . 'assets/js/rhg-generator.js',
            [],
            RHG_VERSION,
            true
        );

        // Custom inline CSS from settings if saved
        $settings = get_option('rhg_settings', []);
        $primary_color   = !empty($settings['primary_color']) ? sanitize_hex_color($settings['primary_color']) : '#ff4b72';
        $secondary_color = !empty($settings['secondary_color']) ? sanitize_hex_color($settings['secondary_color']) : '#6c5ce7';
        $custom_css_code = !empty($settings['custom_css']) ? wp_strip_all_tags($settings['custom_css']) : '';

        $inline_css = ":root {
            --rhg-primary: {$primary_color};
            --rhg-secondary: {$secondary_color};
            --rhg-gradient: linear-gradient(135deg, {$primary_color} 0%, {$secondary_color} 100%);
        }
        {$custom_css_code}";

        wp_add_inline_style('rhg-style', $inline_css);
    }

    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=rhg-settings') . '">' . __('Settings', 'relationship-headcanon-generator') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}
