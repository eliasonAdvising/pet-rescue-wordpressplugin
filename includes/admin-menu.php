<?php
/**
 * File: admin-menu.php
 * Description: WordPress admin menu registration
 *
 * @package PetAdoptionFinder
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register admin menu
 */
add_action('admin_menu', 'paf_register_admin_menu');
function paf_register_admin_menu() {
    add_options_page(
        'Pet Adoption Finder Settings',  // Page title
        'Pet Adoption Finder',           // Menu title
        'manage_options',                // Capability
        'pet-adoption-finder',           // Menu slug
        'paf_render_settings_page'       // Callback function
    );
}

/**
 * Render main settings page
 */
function paf_render_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Get active tab
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';

    ?>
    <div class="wrap paf-admin-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <!-- Tab Navigation -->
        <nav class="nav-tab-wrapper paf-tab-wrapper">
            <a href="?page=pet-adoption-finder&tab=settings"
               class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-admin-generic"></span>
                Settings
            </a>
            <a href="?page=pet-adoption-finder&tab=api-log"
               class="nav-tab <?php echo $active_tab === 'api-log' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-list-view"></span>
                API Error Log
            </a>
            <a href="?page=pet-adoption-finder&tab=help"
               class="nav-tab <?php echo $active_tab === 'help' ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-book-alt"></span>
                Help & Documentation
            </a>
        </nav>

        <!-- Tab Content -->
        <div class="paf-tab-content">
            <?php
            switch ($active_tab) {
                case 'api-log':
                    paf_render_api_log_tab();
                    break;
                case 'help':
                    paf_render_help_tab();
                    break;
                case 'settings':
                default:
                    paf_render_settings_tab();
                    break;
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Render settings tab content
 */
function paf_render_settings_tab() {
    // Show success message if settings were saved
    if (isset($_GET['settings-updated'])) {
        add_settings_error(
            'paf_messages',
            'paf_message',
            'Settings saved successfully.',
            'success'
        );
    }

    settings_errors('paf_messages');
    ?>

    <form method="post" action="options.php" class="paf-settings-form">
        <?php
        settings_fields('paf_settings_group');
        do_settings_sections('pet-adoption-finder');
        ?>

        <p class="submit">
            <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
        </p>
    </form>

    <hr>

    <!-- Shortcode Reference -->
    <div class="paf-shortcode-reference">
        <h2>Shortcode Reference</h2>
        <p>Use these shortcodes to display pet adoption features on your site:</p>

        <table class="widefat">
            <thead>
                <tr>
                    <th>Shortcode</th>
                    <th>Description</th>
                    <th>Usage Example</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[pet_filter_box]</code></td>
                    <td>Display the red filter sidebar</td>
                    <td>Add to sidebar or page content</td>
                </tr>
                <tr>
                    <td><code>[pet_search_grid]</code></td>
                    <td>Display the search results grid with infinite scroll</td>
                    <td>Add to main content area</td>
                </tr>
                <tr>
                    <td><code>[pet_detail]</code></td>
                    <td>Display pet detail page (use on dedicated page)</td>
                    <td>Create a page called "Pet Detail" and add this shortcode</td>
                </tr>
            </tbody>
        </table>

        <h3>Quick Setup Guide</h3>
        <ol>
            <li>Create a page called "Find a Pet" (URL: /find-a-pet/)</li>
            <li>Add both <code>[pet_filter_box]</code> and <code>[pet_search_grid]</code> to this page</li>
            <li>Create another page called "Pet Detail" (URL: /pet-detail/)</li>
            <li>Add <code>[pet_detail]</code> to this page</li>
            <li>Go to Settings > Permalinks and click "Save Changes" to flush rewrite rules</li>
            <li>Done! Pet detail pages will automatically use the URL format: yoursite.com/pet/12345/</li>
        </ol>
    </div>

    <hr>

    <!-- Cache Management -->
    <div class="paf-cache-management">
        <h2>Cache Management</h2>
        <p>Clear cached API responses to force fresh data retrieval. This can help if you're seeing outdated information.</p>

        <button type="button"
                class="button"
                id="clear-all-cache">
            <span class="dashicons dashicons-trash"></span>
            Clear All Cache
        </button>
        <span id="cache-clear-result"></span>

        <p class="description">
            <strong>Note:</strong> Clearing cache will temporarily increase API requests until new data is cached.
        </p>
    </div>
    <?php
}

/**
 * Render API Error Log tab
 */
function paf_render_api_log_tab() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'paf_error_log';

    // Handle bulk delete
    if (isset($_POST['action']) && $_POST['action'] === 'delete_selected' && isset($_POST['log_ids'])) {
        check_admin_referer('paf_delete_logs');

        $log_ids = array_map('absint', $_POST['log_ids']);
        paf_delete_error_logs($log_ids);

        echo '<div class="notice notice-success is-dismissible"><p>Selected logs deleted successfully.</p></div>';
    }

    // Handle clear all
    if (isset($_POST['action']) && $_POST['action'] === 'clear_all_logs') {
        check_admin_referer('paf_clear_logs');
        paf_clear_all_error_logs();
        echo '<div class="notice notice-success is-dismissible"><p>All logs cleared successfully.</p></div>';
    }

    // Get filters
    $error_type_filter = isset($_GET['error_type']) ? sanitize_text_field($_GET['error_type']) : '';
    $date_filter = isset($_GET['date_filter']) ? sanitize_text_field($_GET['date_filter']) : '';

    // Pagination
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    // Get logs
    $args = array(
        'error_type' => $error_type_filter,
        'date_filter' => $date_filter,
        'limit' => $per_page,
        'offset' => $offset
    );

    $logs = paf_get_error_logs($args);
    $total_items = paf_get_error_logs_count($args);
    $total_pages = ceil($total_items / $per_page);

    ?>
    <div class="paf-api-log">

        <!-- Filters and Actions -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="error_type" id="error-type-filter">
                    <option value="">All Error Types</option>
                    <option value="API_CONNECTION" <?php selected($error_type_filter, 'API_CONNECTION'); ?>>Connection Error</option>
                    <option value="API_RESPONSE" <?php selected($error_type_filter, 'API_RESPONSE'); ?>>Response Error</option>
                    <option value="API_TIMEOUT" <?php selected($error_type_filter, 'API_TIMEOUT'); ?>>Timeout</option>
                    <option value="API_RATE_LIMIT" <?php selected($error_type_filter, 'API_RATE_LIMIT'); ?>>Rate Limit</option>
                    <option value="API_AUTH" <?php selected($error_type_filter, 'API_AUTH'); ?>>Authentication</option>
                    <option value="API_NOT_FOUND" <?php selected($error_type_filter, 'API_NOT_FOUND'); ?>>Not Found (404)</option>
                </select>

                <select name="date_filter" id="date-filter">
                    <option value="">All Time</option>
                    <option value="today" <?php selected($date_filter, 'today'); ?>>Today</option>
                    <option value="week" <?php selected($date_filter, 'week'); ?>>Last 7 Days</option>
                    <option value="month" <?php selected($date_filter, 'month'); ?>>Last 30 Days</option>
                </select>

                <button type="button" class="button" id="apply-filters">Apply Filters</button>

                <button type="button" class="button" id="export-logs">
                    <span class="dashicons dashicons-download"></span>
                    Export CSV
                </button>
            </div>

            <div class="alignright">
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('paf_clear_logs'); ?>
                    <input type="hidden" name="action" value="clear_all_logs">
                    <button type="submit"
                            class="button"
                            onclick="return confirm('Are you sure you want to clear all logs? This cannot be undone.');">
                        <span class="dashicons dashicons-trash"></span>
                        Clear All Logs
                    </button>
                </form>
            </div>
        </div>

        <?php if (empty($logs)): ?>
            <div class="notice notice-info">
                <p>
                    <span class="dashicons dashicons-info"></span>
                    No API errors logged.
                    <?php if ($error_type_filter || $date_filter): ?>
                        Try different filters.
                    <?php else: ?>
                        Great job! Your API integration is working smoothly.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>

            <!-- Error Log Table -->
            <form method="post">
                <?php wp_nonce_field('paf_delete_logs'); ?>
                <input type="hidden" name="action" value="delete_selected">

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" id="select-all-logs">
                            </td>
                            <th>Time</th>
                            <th>Error Type</th>
                            <th>Message</th>
                            <th>Response Code</th>
                            <th>User/IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <th class="check-column">
                                    <input type="checkbox"
                                           name="log_ids[]"
                                           value="<?php echo esc_attr($log->id); ?>">
                                </th>
                                <td>
                                    <strong>
                                        <?php echo esc_html(
                                            human_time_diff(
                                                strtotime($log->created_at),
                                                current_time('timestamp')
                                            ) . ' ago'
                                        ); ?>
                                    </strong>
                                    <br>
                                    <small style="color: #666;">
                                        <?php echo esc_html(
                                            date('M j, Y g:i a', strtotime($log->created_at))
                                        ); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="paf-error-badge paf-error-<?php echo esc_attr(strtolower($log->error_type)); ?>">
                                        <?php echo esc_html(str_replace('API_', '', $log->error_type)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo esc_html(wp_trim_words($log->error_message, 15)); ?>
                                </td>
                                <td>
                                    <?php if ($log->response_code): ?>
                                        <code><?php echo esc_html($log->response_code); ?></code>
                                    <?php else: ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log->user_id): ?>
                                        User ID: <?php echo esc_html($log->user_id); ?><br>
                                    <?php endif; ?>
                                    <small style="color: #666;">
                                        <?php echo esc_html($log->ip_address); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="tablenav bottom">
                    <div class="alignleft actions">
                        <button type="submit" class="button">Delete Selected</button>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="tablenav-pages">
                            <span class="displaying-num">
                                <?php printf('%s items', number_format_i18n($total_items)); ?>
                            </span>
                            <?php
                            $page_links = paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $total_pages,
                                'current' => $current_page,
                                'type' => 'plain'
                            ));

                            if ($page_links) {
                                echo '<span class="pagination-links">' . $page_links . '</span>';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </form>

        <?php endif; ?>

    </div>
    <?php
}

/**
 * Render help/documentation tab
 */
function paf_render_help_tab() {
    ?>
    <div class="paf-help-page">
        <h2>Pet Adoption Finder - Help & Documentation</h2>

        <div class="paf-help-section">
            <h3>Getting Started</h3>
            <ol>
                <li>Enter your RescueGroups.org API key in the Settings tab</li>
                <li>Click "Test API Connection" to verify it works</li>
                <li>Create pages with the shortcodes listed in the Settings tab</li>
                <li>Go to Settings > Permalinks and click "Save Changes"</li>
            </ol>
        </div>

        <div class="paf-help-section">
            <h3>Troubleshooting</h3>

            <h4>No pets are showing</h4>
            <ul>
                <li>Check that your API key is entered correctly</li>
                <li>Use the "Test API Connection" button</li>
                <li>Check the API Error Log tab for specific errors</li>
                <li>Verify your API key has correct permissions with RescueGroups.org</li>
            </ul>

            <h4>Pet detail URLs not working (404 errors)</h4>
            <ul>
                <li>Go to Settings > Permalinks and click "Save Changes"</li>
                <li>This flushes WordPress rewrite rules</li>
                <li>If still not working, try deactivating and reactivating the plugin</li>
            </ul>

            <h4>Slow loading</h4>
            <ul>
                <li>Increase cache duration in Settings (try 10-15 minutes)</li>
                <li>Reduce "Results Per Page" to 10-12</li>
                <li>Contact your hosting provider about server performance</li>
            </ul>
        </div>

        <div class="paf-help-section">
            <h3>Support</h3>
            <ul>
                <li><strong>API Documentation:</strong> <a href="https://rescuegroups.org/services/adoptable-pet-data-api/" target="_blank">RescueGroups.org API Docs</a></li>
                <li><strong>Plugin Repository:</strong> <a href="https://github.com/ianeliason/pet-rescue-wordpressplugin" target="_blank">GitHub</a></li>
            </ul>
        </div>
    </div>
    <?php
}
