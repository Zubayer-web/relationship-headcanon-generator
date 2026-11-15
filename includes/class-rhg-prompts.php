<?php
/**
 * Prompts Data & Category Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class RHG_Prompts {

    /**
     * Categories list with friendly labels and icons
     */
    public static function get_categories() {
        return [
            'all' => [
                'name'  => __('All Categories', 'relationship-headcanon-generator'),
                'icon'  => '✨',
                'desc'  => __('A random mix of all relationship headcanons', 'relationship-headcanon-generator')
            ],
            'fluff' => [
                'name'  => __('Fluff & Sweet', 'relationship-headcanon-generator'),
                'icon'  => '🌸',
                'desc'  => __('Wholesome, tender, soft, and loving daily moments', 'relationship-headcanon-generator')
            ],
            'angst' => [
                'name'  => __('Angst & Hurt/Comfort', 'relationship-headcanon-generator'),
                'icon'  => '💔',
                'desc'  => __('Emotional weight, fear of loss, comfort, and vulnerability', 'relationship-headcanon-generator')
            ],
            'enemies_to_lovers' => [
                'name'  => __('Enemies to Lovers', 'relationship-headcanon-generator'),
                'icon'  => '⚔️',
                'desc'  => __('Intense rivalry, unspoken truces, grudging care, and fierce passion', 'relationship-headcanon-generator')
            ],
            'friends_to_lovers' => [
                'name'  => __('Friends to Lovers', 'relationship-headcanon-generator'),
                'icon'  => '☕',
                'desc'  => __('Slow burn realizations, unspoken bonds, and comfortable intimacy', 'relationship-headcanon-generator')
            ],
            'domestic' => [
                'name'  => __('Domestic Life & Cohabitation', 'relationship-headcanon-generator'),
                'icon'  => '🏡',
                'desc'  => __('Living together, grocery runs, morning rituals, and cozy chaos', 'relationship-headcanon-generator')
            ],
            'comedy' => [
                'name'  => __('Comedy & Chaotic Energy', 'relationship-headcanon-generator'),
                'icon'  => '🤪',
                'desc'  => __('Bickering, shared braincell moments, and hilarious shenanigans', 'relationship-headcanon-generator')
            ],
            'au' => [
                'name'  => __('Alternate Universe (AU)', 'relationship-headcanon-generator'),
                'icon'  => '🌌',
                'desc'  => __('Coffee shop, soulmates, royalty, fake dating, and college AUs', 'relationship-headcanon-generator')
            ],
            'spicy_romantic' => [
                'name'  => __('Romantic Tension & Spicy', 'relationship-headcanon-generator'),
                'icon'  => '🔥',
                'desc'  => __('Electrifying tension, whispered confessions, and lingering touch', 'relationship-headcanon-generator')
            ],
        ];
    }

    /**
     * Pre-load default prompts on activation or cache
     */
    public static function init_default_data() {
        $json_file = RHG_PLUGIN_DIR . 'assets/data/default-prompts.json';
        if (file_exists($json_file)) {
            $data = json_decode(file_get_contents($json_file), true);
            if (!empty($data) && is_array($data)) {
                update_option('rhg_default_prompts_version', RHG_VERSION);
            }
        }
    }

    /**
     * Get all default prompts from JSON file
     */
    public function get_default_prompts() {
        $json_file = RHG_PLUGIN_DIR . 'assets/data/default-prompts.json';
        if (file_exists($json_file)) {
            $data = json_decode(file_get_contents($json_file), true);
            if (is_array($data)) {
                return $data;
            }
        }
        return [];
    }

    /**
     * Get custom prompts created in admin panel
     */
    public function get_custom_prompts() {
        $custom = get_option('rhg_custom_prompts', []);
        return is_array($custom) ? $custom : [];
    }

    /**
     * Save custom prompts
     */
    public function save_custom_prompts($prompts) {
        return update_option('rhg_custom_prompts', $prompts);
    }

    /**
     * Return all combined prompts (defaults + custom)
     */
    public function get_all_prompts() {
        $defaults = $this->get_default_prompts();
        $custom   = $this->get_custom_prompts();
        return array_merge($defaults, $custom);
    }

    /**
     * Get prompts filtered by category
     */
    public function get_prompts_by_category($category = 'all') {
        $all = $this->get_all_prompts();
        if ($category === 'all' || empty($category)) {
            return $all;
        }

        return array_values(array_filter($all, function($item) use ($category) {
            return isset($item['category']) && $item['category'] === $category;
        }));
    }
}
