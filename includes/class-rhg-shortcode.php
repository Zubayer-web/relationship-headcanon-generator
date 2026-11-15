<?php
/**
 * Front-end Shortcode Renderer
 */

if (!defined('ABSPATH')) {
    exit;
}

class RHG_Shortcode {

    protected $prompts;

    public function __construct(RHG_Prompts $prompts) {
        $this->prompts = $prompts;
    }

    public function render($atts = []) {
        $atts = shortcode_atts([
            'theme'            => 'auto', // light, dark, auto
            'default_category' => 'all',
            'show_embed'       => 'yes',
            'show_ads'         => 'yes',
            'title'            => __('Relationship Headcanon Generator', 'relationship-headcanon-generator'),
            'subtitle'         => __('Generate cute, angsty, spicy, or hilarious headcanons for your favorite ship or OTP!', 'relationship-headcanon-generator'),
        ], $atts, 'relationship_headcanon_generator');

        // Enqueue assets
        wp_enqueue_style('rhg-style');
        wp_enqueue_script('rhg-generator');

        $settings = get_option('rhg_settings', []);
        $all_prompts = $this->prompts->get_all_prompts();
        $categories  = RHG_Prompts::get_categories();

        $default_person_a = !empty($settings['default_person_a']) ? esc_attr($settings['default_person_a']) : 'Person A';
        $default_person_b = !empty($settings['default_person_b']) ? esc_attr($settings['default_person_b']) : 'Person B';
        $backlink_anchor  = !empty($settings['backlink_anchor']) ? esc_html($settings['backlink_anchor']) : 'Relationship Headcanon Generator';
        $backlink_url     = !empty($settings['backlink_url']) ? esc_url($settings['backlink_url']) : home_url();
        $backlink_rel     = !empty($settings['backlink_rel']) ? esc_attr($settings['backlink_rel']) : 'dofollow';

        // Localize script data for current instance
        $localized_data = [
            'prompts'         => $all_prompts,
            'categories'      => $categories,
            'settings'        => [
                'enableSocial' => !empty($settings['enable_social_share']),
                'enableImage'  => !empty($settings['enable_image_card']),
                'enableFav'    => !empty($settings['enable_favorites']),
                'siteUrl'      => home_url(),
                'siteName'     => get_bloginfo('name'),
                'backlinkText' => $backlink_anchor,
                'backlinkUrl'  => $backlink_url,
            ],
            'i18n'            => [
                'copied'       => __('Headcanon copied to clipboard!', 'relationship-headcanon-generator'),
                'saved'        => __('Saved to favorites!', 'relationship-headcanon-generator'),
                'removed'      => __('Removed from favorites.', 'relationship-headcanon-generator'),
                'generating'   => __('Crafting scenario...', 'relationship-headcanon-generator'),
                'shareTitle'   => __('Check out this OTP headcanon:', 'relationship-headcanon-generator'),
            ]
        ];

        wp_localize_script('rhg-generator', 'rhgData', $localized_data);

        $widget_id = 'rhg-widget-' . wp_rand(1000, 9999);

        ob_start();
        ?>
        <div id="<?php echo esc_attr($widget_id); ?>" class="rhg-wrapper rhg-theme-<?php echo esc_attr($atts['theme']); ?>" data-default-cat="<?php echo esc_attr($atts['default_category']); ?>">
            
            <?php if ($atts['show_ads'] === 'yes' && !empty($settings['ad_slot_top'])): ?>
                <div class="rhg-ad-slot rhg-ad-top">
                    <?php echo do_shortcode($settings['ad_slot_top']); ?>
                </div>
            <?php endif; ?>

            <div class="rhg-card-container">
                <!-- Header -->
                <div class="rhg-header">
                    <div class="rhg-badge-pill">
                        <span class="rhg-sparkle">✨</span> OTP & Ship Generator
                    </div>
                    <h2 class="rhg-title"><?php echo esc_html($atts['title']); ?></h2>
                    <p class="rhg-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
                </div>

                <!-- Input Controls -->
                <div class="rhg-form-section">
                    <div class="rhg-character-inputs">
                        <div class="rhg-input-group">
                            <label for="<?php echo esc_attr($widget_id); ?>-person-a">
                                <span class="rhg-input-icon">💙</span> <?php esc_html_e('Character 1 / Person A', 'relationship-headcanon-generator'); ?>
                            </label>
                            <div class="rhg-input-inner">
                                <input type="text" id="<?php echo esc_attr($widget_id); ?>-person-a" class="rhg-person-input rhg-input-a" placeholder="<?php echo $default_person_a; ?>" value="">
                                <button type="button" class="rhg-btn-clear" title="<?php esc_attr_e('Clear', 'relationship-headcanon-generator'); ?>">&times;</button>
                            </div>
                        </div>

                        <div class="rhg-swap-wrapper">
                            <button type="button" class="rhg-btn-swap" title="<?php esc_attr_e('Swap Characters', 'relationship-headcanon-generator'); ?>" aria-label="Swap names">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 16V4M7 4L3 8M7 4L11 8M17 8V20M17 20L21 16M17 20L13 16"/>
                                </svg>
                            </button>
                        </div>

                        <div class="rhg-input-group">
                            <label for="<?php echo esc_attr($widget_id); ?>-person-b">
                                <span class="rhg-input-icon">💖</span> <?php esc_html_e('Character 2 / Person B', 'relationship-headcanon-generator'); ?>
                            </label>
                            <div class="rhg-input-inner">
                                <input type="text" id="<?php echo esc_attr($widget_id); ?>-person-b" class="rhg-person-input rhg-input-b" placeholder="<?php echo $default_person_b; ?>" value="">
                                <button type="button" class="rhg-btn-clear" title="<?php esc_attr_e('Clear', 'relationship-headcanon-generator'); ?>">&times;</button>
                            </div>
                        </div>
                    </div>

                    <!-- Category Selector -->
                    <div class="rhg-filters-row">
                        <div class="rhg-filter-group">
                            <label for="<?php echo esc_attr($widget_id); ?>-category"><?php esc_html_e('Dynamics / Tropes:', 'relationship-headcanon-generator'); ?></label>
                            <select id="<?php echo esc_attr($widget_id); ?>-category" class="rhg-category-select">
                                <?php foreach ($categories as $cat_key => $cat_data): ?>
                                    <option value="<?php echo esc_attr($cat_key); ?>" <?php selected($atts['default_category'], $cat_key); ?>>
                                        <?php echo esc_html($cat_data['icon'] . ' ' . $cat_data['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="rhg-filter-group rhg-qty-group">
                            <label for="<?php echo esc_attr($widget_id); ?>-count"><?php esc_html_e('Amount:', 'relationship-headcanon-generator'); ?></label>
                            <select id="<?php echo esc_attr($widget_id); ?>-count" class="rhg-count-select">
                                <option value="1">1 Headcanon</option>
                                <option value="3">3 Headcanons</option>
                                <option value="5">5 Headcanons</option>
                            </select>
                        </div>

                        <div class="rhg-filter-group rhg-pair-suggest">
                            <button type="button" class="rhg-btn-random-pair" title="<?php esc_attr_e('Random Trope Names', 'relationship-headcanon-generator'); ?>">
                                🎲 <?php esc_html_e('Random Archetype', 'relationship-headcanon-generator'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Action Trigger Buttons -->
                    <div class="rhg-actions-row">
                        <button type="button" class="rhg-btn-generate">
                            <span class="rhg-btn-icon">⚡</span> <?php esc_html_e('Generate Headcanon', 'relationship-headcanon-generator'); ?>
                        </button>
                        <button type="button" class="rhg-btn-secondary rhg-btn-favorites-toggle">
                            ⭐ <span class="rhg-fav-label"><?php esc_html_e('Saved', 'relationship-headcanon-generator'); ?></span> (<span class="rhg-fav-count">0</span>)
                        </button>
                        <?php if ($atts['show_embed'] === 'yes' && !empty($settings['enable_embed_code'])): ?>
                            <button type="button" class="rhg-btn-secondary rhg-btn-embed-toggle" title="<?php esc_attr_e('Embed on your site', 'relationship-headcanon-generator'); ?>">
                                &lt;/&gt; <?php esc_html_e('Embed', 'relationship-headcanon-generator'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Results Output Display -->
                <div class="rhg-output-area" style="display: none;">
                    <div class="rhg-output-list">
                        <!-- Headcanon cards will be dynamically injected here by JS -->
                    </div>
                </div>

                <!-- Empty Initial State / Greeting Prompt -->
                <div class="rhg-empty-state">
                    <div class="rhg-empty-icon">💭</div>
                    <p><?php esc_html_e('Enter your two characters above and click "Generate Headcanon" to create your customized fanfiction prompts & scenarios!', 'relationship-headcanon-generator'); ?></p>
                </div>

                <!-- Favorites Drawer / Modal -->
                <div class="rhg-modal rhg-favorites-modal" style="display: none;">
                    <div class="rhg-modal-overlay"></div>
                    <div class="rhg-modal-content">
                        <div class="rhg-modal-header">
                            <h3>⭐ <?php esc_html_e('Your Saved Headcanons', 'relationship-headcanon-generator'); ?></h3>
                            <button type="button" class="rhg-modal-close">&times;</button>
                        </div>
                        <div class="rhg-favorites-list">
                            <!-- Injected by JS -->
                        </div>
                        <div class="rhg-modal-footer">
                            <button type="button" class="rhg-btn-clear-favs"><?php esc_html_e('Clear All', 'relationship-headcanon-generator'); ?></button>
                        </div>
                    </div>
                </div>

                <!-- Embed Modal -->
                <?php if ($atts['show_embed'] === 'yes' && !empty($settings['enable_embed_code'])): ?>
                <div class="rhg-modal rhg-embed-modal" style="display: none;">
                    <div class="rhg-modal-overlay"></div>
                    <div class="rhg-modal-content">
                        <div class="rhg-modal-header">
                            <h3>&lt;/&gt; <?php esc_html_e('Embed This Generator On Your Site', 'relationship-headcanon-generator'); ?></h3>
                            <button type="button" class="rhg-modal-close">&times;</button>
                        </div>
                        <p class="rhg-modal-desc"><?php esc_html_e('Copy and paste the HTML embed snippet below to add this interactive generator to your blog, Tumblr, or website:', 'relationship-headcanon-generator'); ?></p>
                        <div class="rhg-embed-code-wrap">
                            <textarea readonly class="rhg-embed-code"><iframe src="<?php echo esc_url(add_query_arg('rhg_embed', '1', get_permalink())); ?>" width="100%" height="680" frameborder="0" style="border: 1px solid #e2e8f0; border-radius: 16px; max-width: 720px; width: 100%;"></iframe><p style="font-size:12px;text-align:center;color:#64748b;margin-top:6px;">Powered by <a href="<?php echo $backlink_url; ?>" target="_blank" rel="<?php echo $backlink_rel; ?>"><?php echo $backlink_anchor; ?></a></p></textarea>
                            <button type="button" class="rhg-btn-copy-embed"><?php esc_html_e('Copy Embed Code', 'relationship-headcanon-generator'); ?></button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Backlink & Attribution Footer -->
                <div class="rhg-footer">
                    <span class="rhg-footer-credit">
                        <?php esc_html_e('Powered by', 'relationship-headcanon-generator'); ?> 
                        <a href="<?php echo $backlink_url; ?>" target="_blank" rel="<?php echo $backlink_rel; ?>" class="rhg-backlink">
                            <?php echo $backlink_anchor; ?>
                        </a>
                    </span>
                    <button type="button" class="rhg-theme-toggle" title="<?php esc_attr_e('Toggle Dark/Light Mode', 'relationship-headcanon-generator'); ?>">
                        <span class="rhg-theme-icon">🌓</span>
                    </button>
                </div>
            </div>

            <?php if ($atts['show_ads'] === 'yes' && !empty($settings['ad_slot_bottom'])): ?>
                <div class="rhg-ad-slot rhg-ad-bottom">
                    <?php echo do_shortcode($settings['ad_slot_bottom']); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
