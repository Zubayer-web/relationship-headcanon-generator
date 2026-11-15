<?php
/**
 * Fired when the plugin is uninstalled.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up stored plugin options
delete_option('rhg_settings');
delete_option('rhg_custom_prompts');
delete_option('rhg_default_prompts_version');
