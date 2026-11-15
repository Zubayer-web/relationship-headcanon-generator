<?php
/**
 * Admin Settings & Prompts Manager Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class RHG_Admin {

    protected $prompts;

    public function __construct(RHG_Prompts $prompts) {
        $this->prompts = $prompts;

        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'handle_custom_prompt_actions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function register_admin_menu() {
        add_options_page(
            __('Relationship Headcanon Generator', 'relationship-headcanon-generator'),
            __('Headcanon Generator', 'relationship-headcanon-generator'),
            'manage_options',
            'rhg-settings',
            [$this, 'render_settings_page']
        );
    }

    public function enqueue_admin_assets($hook) {
        if ($hook !== 'settings_page_rhg-settings') {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }

    public function handle_custom_prompt_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Save General Settings
        if (isset($_POST['rhg_save_settings']) && check_admin_referer('rhg_settings_nonce')) {
            $settings = [
                'primary_color'       => sanitize_hex_color($_POST['primary_color'] ?? '#ff4b72'),
                'secondary_color'     => sanitize_hex_color($_POST['secondary_color'] ?? '#6c5ce7'),
                'default_person_a'    => sanitize_text_field($_POST['default_person_a'] ?? 'Person A'),
                'default_person_b'    => sanitize_text_field($_POST['default_person_b'] ?? 'Person B'),
                'enable_social_share' => isset($_POST['enable_social_share']) ? '1' : '0',
                'enable_image_card'   => isset($_POST['enable_image_card']) ? '1' : '0',
                'enable_favorites'    => isset($_POST['enable_favorites']) ? '1' : '0',
                'enable_embed_code'   => isset($_POST['enable_embed_code']) ? '1' : '0',
                'backlink_anchor'     => sanitize_text_field($_POST['backlink_anchor'] ?? 'Relationship Headcanon Generator'),
                'backlink_url'        => esc_url_raw($_POST['backlink_url'] ?? home_url()),
                'ad_slot_top'         => wp_unslash($_POST['ad_slot_top'] ?? ''),
                'ad_slot_bottom'      => wp_unslash($_POST['ad_slot_bottom'] ?? ''),
                'custom_css'          => wp_strip_all_tags($_POST['custom_css'] ?? ''),
            ];

            update_option('rhg_settings', $settings);
            add_settings_error('rhg_messages', 'rhg_settings_saved', __('Settings saved successfully.', 'relationship-headcanon-generator'), 'updated');
        }

        // Add Custom Prompt
        if (isset($_POST['rhg_add_prompt']) && check_admin_referer('rhg_prompt_nonce')) {
            $text     = sanitize_textarea_field($_POST['prompt_text'] ?? '');
            $category = sanitize_key($_POST['prompt_category'] ?? 'fluff');

            if (!empty($text)) {
                $custom = $this->prompts->get_custom_prompts();
                $custom[] = [
                    'id'       => 'c_' . time() . '_' . wp_rand(100, 999),
                    'category' => $category,
                    'text'     => $text,
                ];
                $this->prompts->save_custom_prompts($custom);
                add_settings_error('rhg_messages', 'rhg_prompt_added', __('Custom prompt added successfully!', 'relationship-headcanon-generator'), 'updated');
            }
        }

        // Delete Custom Prompt
        if (isset($_GET['action']) && $_GET['action'] === 'delete_prompt' && isset($_GET['prompt_id']) && check_admin_referer('rhg_delete_prompt_' . $_GET['prompt_id'])) {
            $delete_id = sanitize_text_field($_GET['prompt_id']);
            $custom    = $this->prompts->get_custom_prompts();
            $custom    = array_values(array_filter($custom, function($item) use ($delete_id) {
                return (string)($item['id'] ?? '') !== $delete_id;
            }));

            $this->prompts->save_custom_prompts($custom);
            add_settings_error('rhg_messages', 'rhg_prompt_deleted', __('Prompt deleted.', 'relationship-headcanon-generator'), 'updated');
            wp_safe_redirect(admin_url('options-general.php?page=rhg-settings&tab=prompts'));
            exit;
        }
    }

    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        $settings   = get_option('rhg_settings', []);
        $categories = RHG_Prompts::get_categories();

        $defaults_count = count($this->prompts->get_default_prompts());
        $custom_prompts = $this->prompts->get_custom_prompts();
        $custom_count   = count($custom_prompts);
        ?>
        <div class="wrap rhg-admin-wrap">
            <h1>✨ <?php esc_html_e('Relationship Headcanon Generator Settings', 'relationship-headcanon-generator'); ?></h1>
            <p><?php esc_html_e('Configure your viral fanfiction / shipping generator tool, manage custom prompts, and optimize backlink embed settings.', 'relationship-headcanon-generator'); ?></p>

            <?php settings_errors('rhg_messages'); ?>

            <nav class="nav-tab-wrapper">
                <a href="?page=rhg-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('General & Branding', 'relationship-headcanon-generator'); ?>
                </a>
                <a href="?page=rhg-settings&tab=backlinks" class="nav-tab <?php echo $active_tab === 'backlinks' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Backlink & Embed Strategy', 'relationship-headcanon-generator'); ?>
                </a>
                <a href="?page=rhg-settings&tab=prompts" class="nav-tab <?php echo $active_tab === 'prompts' ? 'nav-tab-active' : ''; ?>">
                    <?php printf(esc_html__('Prompt Database (%d)', 'relationship-headcanon-generator'), $defaults_count + $custom_count); ?>
                </a>
                <a href="?page=rhg-settings&tab=ads" class="nav-tab <?php echo $active_tab === 'ads' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Monetization & Ads', 'relationship-headcanon-generator'); ?>
                </a>
                <a href="?page=rhg-settings&tab=docs" class="nav-tab <?php echo $active_tab === 'docs' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Shortcodes & Guide', 'relationship-headcanon-generator'); ?>
                </a>
            </nav>

            <div class="rhg-tab-content" style="margin-top: 20px; background: #fff; padding: 24px; border: 1px solid #ccd0d4; border-radius: 8px; max-width: 900px;">
                
                <?php if ($active_tab === 'general'): ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('rhg_settings_nonce'); ?>
                        <h2><?php esc_html_e('Look & Feel Settings', 'relationship-headcanon-generator'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="primary_color"><?php esc_html_e('Primary Accent Color', 'relationship-headcanon-generator'); ?></label></th>
                                <td>
                                    <input type="text" name="primary_color" id="primary_color" class="rhg-color-picker" value="<?php echo esc_attr($settings['primary_color'] ?? '#ff4b72'); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="secondary_color"><?php esc_html_e('Secondary Gradient Color', 'relationship-headcanon-generator'); ?></label></th>
                                <td>
                                    <input type="text" name="secondary_color" id="secondary_color" class="rhg-color-picker" value="<?php echo esc_attr($settings['secondary_color'] ?? '#6c5ce7'); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="default_person_a"><?php esc_html_e('Default Character 1 Placeholder', 'relationship-headcanon-generator'); ?></label></th>
                                <td>
                                    <input type="text" name="default_person_a" id="default_person_a" class="regular-text" value="<?php echo esc_attr($settings['default_person_a'] ?? 'Person A'); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="default_person_b"><?php esc_html_e('Default Character 2 Placeholder', 'relationship-headcanon-generator'); ?></label></th>
                                <td>
                                    <input type="text" name="default_person_b" id="default_person_b" class="regular-text" value="<?php echo esc_attr($settings['default_person_b'] ?? 'Person B'); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Feature Toggles', 'relationship-headcanon-generator'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="enable_social_share" value="1" <?php checked(!empty($settings['enable_social_share'])); ?>>
                                            <?php esc_html_e('Enable Social Share Buttons (X/Twitter, Tumblr, WhatsApp)', 'relationship-headcanon-generator'); ?>
                                        </label><br><br>
                                        <label>
                                            <input type="checkbox" name="enable_image_card" value="1" <?php checked(!empty($settings['enable_image_card'])); ?>>
                                            <?php esc_html_e('Enable "Save as Card Image" (Viral Canvas Card Generator)', 'relationship-headcanon-generator'); ?>
                                        </label><br><br>
                                        <label>
                                            <input type="checkbox" name="enable_favorites" value="1" <?php checked(!empty($settings['enable_favorites'])); ?>>
                                            <?php esc_html_e('Enable Local Favorites / Star Bookmarks', 'relationship-headcanon-generator'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="custom_css"><?php esc_html_e('Custom CSS', 'relationship-headcanon-generator'); ?></label></th>
                                <td>
                                    <textarea name="custom_css" id="custom_css" rows="5" class="large-text code"><?php echo esc_textarea($settings['custom_css'] ?? ''); ?></textarea>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(__('Save Settings', 'relationship-headcanon-generator'), 'primary', 'rhg_save_settings'); ?>
                    </form>

                <?php elseif ($active_tab === 'backlinks'): ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('rhg_settings_nonce'); ?>
                        <h2><?php esc_html_e('Backlink & Viral Embed Strategy', 'relationship-headcanon-generator'); ?></h2>
                        <p class="description">
                            <?php esc_html_e('The embed button lets bloggers and fandom creators embed your tool on their websites. This drives direct authority backlinks back to your website.', 'relationship-headcanon-generator'); ?>
                        </p>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Embed Feature', 'relationship-headcanon-generator'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="enable_embed_code" value="1" <?php checked(!empty($settings['enable_embed_code'])); ?>>
                                        <?php esc_html_e('Show "Embed Widget" button on front-end', 'relationship-headcanon-generator'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="backlink_anchor"><?php esc_html_e('Backlink Anchor Text', 'relationship-headcanon-generator'); ?></label></th>
                                <td>
                                    <input type="text" name="backlink_anchor" id="backlink_anchor" class="regular-text" value="<?php echo esc_attr($settings['backlink_anchor'] ?? 'Relationship Headcanon Generator'); ?>">
                                    <p class="description"><?php esc_html_e('The anchor text used in the footer and embed attribution link (e.g. "Relationship Headcanon Generator" or "OTP Prompts Generator").', 'relationship-headcanon-generator'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="backlink_url"><?php esc_html_e('Target Backlink URL', 'relationship-headcanon-generator'); ?></label></th>
                                <td>
                                    <input type="url" name="backlink_url" id="backlink_url" class="regular-text" value="<?php echo esc_attr($settings['backlink_url'] ?? home_url()); ?>">
                                    <p class="description"><?php esc_html_e('The target URL where the backlink points to (defaults to your homepage or tool landing page).', 'relationship-headcanon-generator'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(__('Save Backlink Settings', 'relationship-headcanon-generator'), 'primary', 'rhg_save_settings'); ?>
                    </form>

                <?php elseif ($active_tab === 'prompts'): ?>
                    <h2><?php esc_html_e('Add New Custom Prompt', 'relationship-headcanon-generator'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Use {name1} and {name2} as dynamic placeholders for Character A and Character B.', 'relationship-headcanon-generator'); ?>
                    </p>

                    <form method="post" action="" style="background: #f8fafc; padding: 16px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 24px;">
                        <?php wp_nonce_field('rhg_prompt_nonce'); ?>
                        <div style="margin-bottom: 12px;">
                            <label for="prompt_category"><strong><?php esc_html_e('Category / Dynamic:', 'relationship-headcanon-generator'); ?></strong></label><br>
                            <select name="prompt_category" id="prompt_category">
                                <?php foreach ($categories as $cat_key => $cat_data): ?>
                                    <?php if ($cat_key === 'all') continue; ?>
                                    <option value="<?php echo esc_attr($cat_key); ?>">
                                        <?php echo esc_html($cat_data['icon'] . ' ' . $cat_data['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <label for="prompt_text"><strong><?php esc_html_e('Prompt Template:', 'relationship-headcanon-generator'); ?></strong></label><br>
                            <textarea name="prompt_text" id="prompt_text" rows="3" class="large-text" placeholder="<?php esc_attr_e('e.g., {name1} always leaves cute notes on the fridge for {name2} before leaving for work.', 'relationship-headcanon-generator'); ?>" required></textarea>
                        </div>
                        <input type="submit" name="rhg_add_prompt" class="button button-primary" value="<?php esc_attr_e('+ Add Prompt', 'relationship-headcanon-generator'); ?>">
                    </form>

                    <h2><?php printf(esc_html__('Custom Prompts (%d)', 'relationship-headcanon-generator'), $custom_count); ?></h2>
                    <?php if (empty($custom_prompts)): ?>
                        <p><em><?php esc_html_e('No custom prompts added yet. The generator is using 60 built-in default prompts.', 'relationship-headcanon-generator'); ?></em></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 150px;"><?php esc_html_e('Category', 'relationship-headcanon-generator'); ?></th>
                                    <th><?php esc_html_e('Prompt Text', 'relationship-headcanon-generator'); ?></th>
                                    <th style="width: 80px;"><?php esc_html_e('Action', 'relationship-headcanon-generator'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($custom_prompts as $cp): ?>
                                    <tr>
                                        <td>
                                            <span class="badge"><?php echo esc_html($categories[$cp['category']]['name'] ?? $cp['category']); ?></span>
                                        </td>
                                        <td><?php echo esc_html($cp['text']); ?></td>
                                        <td>
                                            <?php
                                            $delete_url = wp_nonce_url(
                                                admin_url('options-general.php?page=rhg-settings&tab=prompts&action=delete_prompt&prompt_id=' . urlencode($cp['id'])),
                                                'rhg_delete_prompt_' . $cp['id']
                                            );
                                            ?>
                                            <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this prompt?', 'relationship-headcanon-generator'); ?>')" style="color: #dc2626;">
                                                <?php esc_html_e('Delete', 'relationship-headcanon-generator'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <div style="margin-top: 24px; padding: 12px 16px; background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 4px;">
                        <strong>ℹ️ <?php esc_html_e('Built-in Library:', 'relationship-headcanon-generator'); ?></strong>
                        <?php printf(esc_html__('The plugin includes %d curated prompts in Fluff, Angst, Enemies to Lovers, Friends to Lovers, Domestic, Comedy, AU, and Romantic Tension.', 'relationship-headcanon-generator'), $defaults_count); ?>
                    </div>

                <?php elseif ($active_tab === 'ads'): ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('rhg_settings_nonce'); ?>
                        <h2><?php esc_html_e('Monetization & Ad Slots', 'relationship-headcanon-generator'); ?></h2>
                        <p class="description"><?php esc_html_e('Paste your Google AdSense code or custom banner HTML to display ads directly around the tool.', 'relationship-headcanon-generator'); ?></p>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="ad_slot_top"><?php esc_html_e('Top Ad Slot', 'relationship-headcanon-generator'); ?></label></th>
                                <td>
                                    <textarea name="ad_slot_top" id="ad_slot_top" rows="4" class="large-text code"><?php echo esc_textarea($settings['ad_slot_top'] ?? ''); ?></textarea>
                                    <p class="description"><?php esc_html_e('Appears above the generator card.', 'relationship-headcanon-generator'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="ad_slot_bottom"><?php esc_html_e('Bottom Ad Slot', 'relationship-headcanon-generator'); ?></label></th>
                                <td>
                                    <textarea name="ad_slot_bottom" id="ad_slot_bottom" rows="4" class="large-text code"><?php echo esc_textarea($settings['ad_slot_bottom'] ?? ''); ?></textarea>
                                    <p class="description"><?php esc_html_e('Appears below the generator card.', 'relationship-headcanon-generator'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(__('Save Ad Settings', 'relationship-headcanon-generator'), 'primary', 'rhg_save_settings'); ?>
                    </form>

                <?php elseif ($active_tab === 'docs'): ?>
                    <h2><?php esc_html_e('Shortcode Reference & Integration', 'relationship-headcanon-generator'); ?></h2>
                    <p><?php esc_html_e('Place this shortcode into any page, post, or widget area:', 'relationship-headcanon-generator'); ?></p>
                    <code style="font-size: 16px; padding: 8px 12px; background: #f1f5f9; display: inline-block; border-radius: 6px;">[relationship_headcanon_generator]</code>

                    <h3 style="margin-top: 24px;"><?php esc_html_e('Available Shortcode Attributes:', 'relationship-headcanon-generator'); ?></h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Attribute', 'relationship-headcanon-generator'); ?></th>
                                <th><?php esc_html_e('Options / Default', 'relationship-headcanon-generator'); ?></th>
                                <th><?php esc_html_e('Description', 'relationship-headcanon-generator'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>theme</code></td>
                                <td><code>auto</code> (default), <code>light</code>, <code>dark</code></td>
                                <td><?php esc_html_e('Set visual color scheme theme.', 'relationship-headcanon-generator'); ?></td>
                            </tr>
                            <tr>
                                <td><code>default_category</code></td>
                                <td><code>all</code>, <code>fluff</code>, <code>angst</code>, <code>enemies_to_lovers</code>, <code>domestic</code>, <code>au</code>, etc.</td>
                                <td><?php esc_html_e('Pre-select a specific trope or dynamic.', 'relationship-headcanon-generator'); ?></td>
                            </tr>
                            <tr>
                                <td><code>show_embed</code></td>
                                <td><code>yes</code> (default) / <code>no</code></td>
                                <td><?php esc_html_e('Show or hide the embed code button for backlinks.', 'relationship-headcanon-generator'); ?></td>
                            </tr>
                            <tr>
                                <td><code>title</code></td>
                                <td><code>Relationship Headcanon Generator</code></td>
                                <td><?php esc_html_e('Custom header title.', 'relationship-headcanon-generator'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>

            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            if ($('.rhg-color-picker').length) {
                $('.rhg-color-picker').wpColorPicker();
            }
        });
        </script>
        <?php
    }
}
