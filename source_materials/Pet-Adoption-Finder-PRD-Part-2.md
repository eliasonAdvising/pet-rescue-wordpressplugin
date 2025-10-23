# Pet Adoption Finder - PRD Part 2: Remaining Phases

**Continuation of Pet-Adoption-Finder-PRD.md**

---

## Phase 5: Admin Interface (Continued)

### 5.2 Admin Settings Registration

**Objective:** Register all plugin settings with WordPress Settings API.

**File:** `includes/admin-settings.php`

**Implementation:**

```php
<?php
/**
 * File: admin-settings.php
 * Description: Settings registration and field rendering
 * 
 * @package PetAdoptionFinder
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register plugin settings
 */
add_action('admin_init', 'paf_register_settings');
function paf_register_settings() {
    // Register settings
    register_setting('paf_settings_group', 'paf_api_key', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ));
    
    register_setting('paf_settings_group', 'paf_api_endpoint', array(
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => 'https://api.rescuegroups.org/v5'
    ));
    
    register_setting('paf_settings_group', 'paf_cache_duration', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 5
    ));
    
    register_setting('paf_settings_group', 'paf_results_per_page', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 12
    ));
    
    register_setting('paf_settings_group', 'paf_enable_error_logging', array(
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => true
    ));
    
    // Add settings sections
    add_settings_section(
        'paf_api_section',
        'API Configuration',
        'paf_api_section_callback',
        'pet-adoption-finder'
    );
    
    add_settings_section(
        'paf_display_section',
        'Display Settings',
        'paf_display_section_callback',
        'pet-adoption-finder'
    );
    
    add_settings_section(
        'paf_advanced_section',
        'Advanced Settings',
        'paf_advanced_section_callback',
        'pet-adoption-finder'
    );
    
    // Add settings fields
    add_settings_field(
        'paf_api_key',
        'API Key',
        'paf_api_key_field_callback',
        'pet-adoption-finder',
        'paf_api_section'
    );
    
    add_settings_field(
        'paf_api_endpoint',
        'API Endpoint',
        'paf_api_endpoint_field_callback',
        'pet-adoption-finder',
        'paf_api_section'
    );
    
    add_settings_field(
        'paf_results_per_page',
        'Results Per Page',
        'paf_results_per_page_field_callback',
        'pet-adoption-finder',
        'paf_display_section'
    );
    
    add_settings_field(
        'paf_cache_duration',
        'Cache Duration (minutes)',
        'paf_cache_duration_field_callback',
        'pet-adoption-finder',
        'paf_advanced_section'
    );
    
    add_settings_field(
        'paf_enable_error_logging',
        'Enable Error Logging',
        'paf_enable_error_logging_field_callback',
        'pet-adoption-finder',
        'paf_advanced_section'
    );
}

// Section callbacks
function paf_api_section_callback() {
    echo '<p>Configure your RescueGroups.org API credentials. Get your API key from <a href="https://rescuegroups.org/services/adoptable-pet-data-api/" target="_blank">RescueGroups.org</a>.</p>';
}

function paf_display_section_callback() {
    echo '<p>Customize how pets are displayed on your website.</p>';
}

function paf_advanced_section_callback() {
    echo '<p>Advanced options for performance and debugging.</p>';
}

// Field callbacks
function paf_api_key_field_callback() {
    $value = get_option('paf_api_key', '');
    $show_key = isset($_GET['show_key']) && $_GET['show_key'] === '1';
    ?>
    <input type="<?php echo $show_key ? 'text' : 'password'; ?>" 
           name="paf_api_key" 
           id="paf_api_key"
           value="<?php echo esc_attr($value); ?>" 
           class="regular-text"
           placeholder="Enter your API key"
           autocomplete="off">
    
    <?php if (!empty($value)): ?>
        <p class="description">
            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
            API key is set. 
            <?php if (!$show_key): ?>
                <a href="?page=pet-adoption-finder&tab=settings&show_key=1">Show key</a>
            <?php else: ?>
                <a href="?page=pet-adoption-finder&tab=settings">Hide key</a>
            <?php endif; ?>
        </p>
    <?php else: ?>
        <p class="description">
            <span class="dashicons dashicons-warning" style="color: #f56e28;"></span>
            API key is required. 
            <a href="https://rescuegroups.org/services/adoptable-pet-data-api/" target="_blank">Get an API key</a>
        </p>
    <?php endif; ?>
    
    <?php if (!empty($value)): ?>
        <p>
            <button type="button" 
                    class="button" 
                    id="test-api-connection">
                <span class="dashicons dashicons-update"></span>
                Test API Connection
            </button>
            <span id="api-test-result"></span>
        </p>
    <?php endif; ?>
    <?php
}

function paf_api_endpoint_field_callback() {
    $value = get_option('paf_api_endpoint', 'https://api.rescuegroups.org/v5');
    ?>
    <input type="url" 
           name="paf_api_endpoint" 
           id="paf_api_endpoint"
           value="<?php echo esc_attr($value); ?>" 
           class="regular-text"
           placeholder="https://api.rescuegroups.org/v5">
    <p class="description">The base URL for the RescueGroups.org API. Default should work for most users.</p>
    <?php
}

function paf_results_per_page_field_callback() {
    $value = get_option('paf_results_per_page', 12);
    ?>
    <input type="number" 
           name="paf_results_per_page" 
           id="paf_results_per_page"
           value="<?php echo esc_attr($value); ?>" 
           min="1" 
           max="100" 
           step="1"
           class="small-text">
    <p class="description">Number of pets to show per page (1-100). Recommended: 12-20 for best performance.</p>
    <?php
}

function paf_cache_duration_field_callback() {
    $value = get_option('paf_cache_duration', 5);
    ?>
    <input type="number" 
           name="paf_cache_duration" 
           id="paf_cache_duration"
           value="<?php echo esc_attr($value); ?>" 
           min="0" 
           max="60" 
           step="1"
           class="small-text">
    <span class="description">minutes</span>
    <p class="description">
        How long to cache search results (0-60 minutes). 
        <strong>Note:</strong> Detail pages are NEVER cached to ensure availability is always current.
        <br>Recommended: 5 minutes for good balance between freshness and API load.
    </p>
    <?php
}

function paf_enable_error_logging_field_callback() {
    $value = get_option('paf_enable_error_logging', true);
    ?>
    <label>
        <input type="checkbox" 
               name="paf_enable_error_logging" 
               id="paf_enable_error_logging"
               value="1" 
               <?php checked($value, true); ?>>
        Enable detailed API error logging
    </label>
    <p class="description">
        Log all API errors for troubleshooting. 
        Logs can be viewed in the "API Error Log" tab.
        Disable this only if you're concerned about database storage.
    </p>
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
```

**Testing Steps:**
1. Navigate to Settings tab
2. Verify all settings fields render correctly
3. Enter test values and save
4. Verify values persist after page reload
5. Test "Show/Hide key" functionality
6. Verify shortcode reference table displays

**Deliverable:** Settings page fully functional with all fields.

---

### 5.3 API Error Log Viewer

**Objective:** Create comprehensive error log viewer with filtering and export.

**File:** `includes/admin-menu.php` (add to existing file)

**Implementation:**

```php
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
                            <th>Details</th>
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
                                    <button type="button" 
                                            class="button button-small view-log-details" 
                                            data-log-id="<?php echo esc_attr($log->id); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                        View
                                    </button>
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
        
        <!-- Error Statistics -->
        <div class="paf-log-stats">
            <h2>Error Statistics (Last 7 Days)</h2>
            <?php
            global $wpdb;
            $stats = $wpdb->get_results(
                "SELECT error_type, COUNT(*) as count, response_code
                 FROM {$wpdb->prefix}paf_error_log 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY error_type, response_code 
                 ORDER BY count DESC
                 LIMIT 10"
            );
            ?>
            
            <?php if ($stats): ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Error Type</th>
                            <th>Response Code</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats as $stat): ?>
                            <tr>
                                <td>
                                    <span class="paf-error-badge paf-error-<?php echo esc_attr(strtolower($stat->error_type)); ?>">
                                        <?php echo esc_html($stat->error_type); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($stat->response_code): ?>
                                        <code><?php echo esc_html($stat->response_code); ?></code>
                                    <?php else: ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo esc_html($stat->count); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #46b450;">
                    <span class="dashicons dashicons-yes-alt"></span>
                    No errors in the last 7 days. Excellent!
                </p>
            <?php endif; ?>
        </div>
        
    </div>
    
    <!-- Log Details Modal (populated via AJAX) -->
    <div id="log-details-modal" style="display:none;">
        <div class="log-details-content">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
    <?php
}
```

**Testing Steps:**
1. Navigate to API Error Log tab
2. Verify empty state message if no errors
3. Generate test errors (invalid API key)
4. Verify errors appear in table
5. Test filtering by error type
6. Test filtering by date range
7. Test pagination
8. Test "View Details" button
9. Test bulk delete
10. Test "Clear All Logs"
11. Verify statistics display correctly

**Deliverable:** Comprehensive error log viewer with filtering, pagination, and export.

---

### 5.4 Documentation Tab

**Objective:** Provide comprehensive user documentation within the admin interface.

**File:** `includes/admin-menu.php` (add to existing file)

**Implementation:**

```php
/**
 * Render README/Documentation tab
 */
function paf_render_readme_tab() {
    ?>
    <div class="paf-readme">
        
        <div class="paf-readme-hero">
            <h1>🐾 Pet Adoption Finder</h1>
            <p class="paf-readme-tagline">Help pets find their forever homes with a powerful, easy-to-use adoption search plugin.</p>
        </div>
        
        <div class="paf-readme-section">
            <h2>📖 Getting Started</h2>
            <p>Welcome to Pet Adoption Finder! This plugin integrates with RescueGroups.org to display adoptable pets on your WordPress site.</p>
            
            <h3>Quick Setup (3 Steps)</h3>
            <ol class="paf-setup-steps">
                <li>
                    <strong>Get an API Key</strong><br>
                    Visit <a href="https://rescuegroups.org/services/adoptable-pet-data-api/" target="_blank">RescueGroups.org API</a> 
                    and request your free API key. It typically takes 1-2 business days to receive approval.
                </li>
                <li>
                    <strong>Configure Settings</strong><br>
                    Go to the <a href="?page=pet-adoption-finder&tab=settings">Settings tab</a> and enter your API key. 
                    Click "Test API Connection" to verify it works.
                </li>
                <li>
                    <strong>Add Shortcodes</strong><br>
                    Create pages with the shortcodes below to display pets on your site.
                </li>
            </ol>
        </div>
        
        <div class="paf-readme-section">
            <h2>🔧 Shortcode Reference</h2>
            
            <div class="paf-shortcode-card">
                <h3>Filter Box - <code>[pet_filter_box]</code></h3>
                <p>Displays the red sidebar with filtering options (Dogs/Cats/Either, Location, Breed, Age, Sex, Size).</p>
                <pre><code>[pet_filter_box]</code></pre>
                <p><strong>Best Used:</strong> In a sidebar widget or on the search page before the results grid.</p>
            </div>
            
            <div class="paf-shortcode-card">
                <h3>Search Results Grid - <code>[pet_search_grid]</code></h3>
                <p>Displays the grid of pet cards with infinite scroll functionality.</p>
                <pre><code>[pet_search_grid]</code></pre>
                <p><strong>Best Used:</strong> In the main content area of your search/browse page.</p>
            </div>
            
            <div class="paf-shortcode-card">
                <h3>Pet Detail Page - <code>[pet_detail]</code></h3>
                <p>Displays the full detail page for an individual pet with photos, description, and adoption info.</p>
                <pre><code>[pet_detail]</code></pre>
                <p><strong>Best Used:</strong> Create a dedicated page called "Pet Detail" and add this shortcode. The plugin handles URL routing automatically.</p>
            </div>
            
            <h3>Example Page Setup</h3>
            <div class="paf-example-setup">
                <div class="paf-example-page">
                    <h4>📄 Page 1: "Find a Pet"</h4>
                    <p>URL: <code>/find-a-pet/</code></p>
                    <pre><code>[pet_filter_box]
[pet_search_grid]</code></pre>
                </div>
                
                <div class="paf-example-page">
                    <h4>📄 Page 2: "Pet Detail"</h4>
                    <p>URL: <code>/pet-detail/</code></p>
                    <pre><code>[pet_detail]</code></pre>
                    <p class="description">Individual pet URLs will automatically work: <code>/pet/12345/</code></p>
                </div>
            </div>
            
            <div class="paf-notice paf-notice-info">
                <p>
                    <strong>Important:</strong> After creating your pages, go to 
                    <strong>Settings > Permalinks</strong> and click "Save Changes" to flush rewrite rules. 
                    This ensures pet detail URLs work correctly.
                </p>
            </div>
        </div>
        
        <div class="paf-readme-section">
            <h2>🔗 URL Structure</h2>
            <p>Pet detail pages use clean, shareable URLs:</p>
            <ul>
                <li>Format: <code>yoursite.com/pet/12345/</code></li>
                <li>Each pet gets a unique ID from the RescueGroups API</li>
                <li>URLs are SEO-friendly and shareable on social media</li>
                <li>Detail pages <strong>always show real-time data</strong> (no caching)</li>
            </ul>
        </div>
        
        <div class="paf-readme-section">
            <h2>⚡ Performance & Caching</h2>
            <p>The plugin uses smart caching to balance performance and data accuracy:</p>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Component</th>
                        <th>Cache Duration</th>
                        <th>Why?</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Search Results</strong></td>
                        <td>5 minutes (configurable)</td>
                        <td>Balance between freshness and API load</td>
                    </tr>
                    <tr>
                        <td><strong>Detail Pages</strong></td>
                        <td><span style="color: #dc3232;">Never cached</span></td>
                        <td>Always show current availability status</td>
                    </tr>
                    <tr>
                        <td><strong>Filter Options</strong></td>
                        <td>1 hour</td>
                        <td>Breed/size lists rarely change</td>
                    </tr>
                </tbody>
            </table>
            
            <p><strong>Why no caching on detail pages?</strong> Pets can be adopted quickly. By always fetching fresh data, users never see outdated "available" status for already-adopted pets.</p>
        </div>
        
        <div class="paf-readme-section">
            <h2>🐾 Data Freshness</h2>
            <p>When a pet becomes unavailable (adopted, pending, or removed), the plugin:</p>
            <ul>
                <li>✅ Detects the status change on the detail page</li>
                <li>✅ Shows celebration message if adopted</li>
                <li>✅ Suggests similar available pets</li>
                <li>✅ Provides clear next steps for users</li>
            </ul>
            <p>This ensures visitors always see accurate information and never inquire about unavailable pets.</p>
        </div>
        
        <div class="paf-readme-section">
            <h2>🎨 Styling & Customization</h2>
            <p>The plugin includes modern, minimalistic styling that works with most themes:</p>
            <ul>
                <li><strong>Primary color:</strong> Red (#CC0000) for filter sidebar</li>
                <li><strong>Accent color:</strong> Blue (#0080FF) for active states</li>
                <li>Fully responsive design</li>
                <li>Accessible (WCAG 2.1 AA compliant)</li>
            </ul>
            <p><strong>Custom Styling:</strong> All plugin classes are prefixed with <code>paf-</code> for easy CSS customization. 
            Add your custom styles to your theme's CSS file.</p>
        </div>
        
        <div class="paf-readme-section">
            <h2>🔍 Troubleshooting</h2>
            
            <h3>No pets are showing</h3>
            <ul>
                <li>Check that your API key is entered correctly in the <a href="?page=pet-adoption-finder&tab=settings">Settings tab</a></li>
                <li>Use the "Test API Connection" button to verify connectivity</li>
                <li>Check the <a href="?page=pet-adoption-finder&tab=api-log">API Error Log tab</a> for specific errors</li>
                <li>Verify your API key has the correct permissions with RescueGroups.org</li>
            </ul>
            
            <h3>Pet detail page shows "Pet not found"</h3>
            <ul>
                <li>The pet may have been adopted (this is normal and expected!)</li>
                <li>The API may have removed the listing</li>
                <li>Similar pets will be suggested automatically</li>
                <li>This is actually a <strong>good thing</strong> - it means pets are finding homes!</li>
            </ul>
            
            <h3>Filters not working</h3>
            <ul>
                <li>Ensure JavaScript is enabled in the browser</li>
                <li>Check browser console for JavaScript errors (F12 > Console)</li>
                <li>Clear your browser cache and site cache</li>
                <li>Check for JavaScript conflicts with other plugins (try disabling other plugins temporarily)</li>
            </ul>
            
            <h3>Slow loading</h3>
            <ul>
                <li>Increase cache duration in <a href="?page=pet-adoption-finder&tab=settings">Settings</a> (try 10-15 minutes)</li>
                <li>Reduce "Results Per Page" to 10-12</li>
                <li>Consider using a caching plugin (WP Rocket, W3 Total Cache)</li>
                <li>Check API response times in the Error Log</li>
                <li>Contact your hosting provider about server performance</li>
            </ul>
            
            <h3>Detail page URLs not working (404 errors)</h3>
            <ul>
                <li>Go to <strong>Settings > Permalinks</strong> and click "Save Changes"</li>
                <li>This flushes WordPress rewrite rules</li>
                <li>If still not working, try deactivating and reactivating the plugin</li>
            </ul>
        </div>
        
        <div class="paf-readme-section">
            <h2>📊 API Error Log</h2>
            <p>The <a href="?page=pet-adoption-finder&tab=api-log">API Error Log tab</a> provides detailed information about API issues:</p>
            <ul>
                <li>View all API errors with timestamps</li>
                <li>Filter by error type (Connection, Timeout, Rate Limit, etc.)</li>
                <li>Filter by date range (Today, Last 7 days, Last 30 days)</li>
                <li>Export logs to CSV for analysis</li>
                <li>Clear logs individually or all at once</li>
                <li>View statistics for the last 7 days</li>
            </ul>
            <p><strong>Tip:</strong> Regular review of error logs helps identify API issues early.</p>
        </div>
        
        <div class="paf-readme-section">
            <h2>🔒 Security</h2>
            <ul>
                <li>API keys are stored securely in WordPress options (not in files)</li>
                <li>All user inputs are sanitized and validated</li>
                <li>AJAX requests use WordPress nonces for security</li>
                <li>No sensitive data is exposed to frontend JavaScript</li>
                <li>Database queries use prepared statements</li>
                <li>Regular security audits recommended</li>
            </ul>
        </div>
        
        <div class="paf-readme-section">
            <h2>♿ Accessibility</h2>
            <p>This plugin follows WCAG 2.1 AA standards:</p>
            <ul>
                <li>✅ Keyboard navigation support</li>
                <li>✅ Screen reader friendly with ARIA labels</li>
                <li>✅ Proper heading hierarchy</li>
                <li>✅ Color contrast compliance</li>
                <li>✅ Focus indicators on all interactive elements</li>
                <li>✅ Alt text for all images</li>
            </ul>
        </div>
        
        <div class="paf-readme-section">
            <h2>📱 Mobile Support</h2>
            <p>Fully responsive design works on:</p>
            <ul>
                <li>Mobile phones (iOS 14+, Android 10+)</li>
                <li>Tablets (iPad, Android tablets)</li>
                <li>Desktop computers</li>
                <li>Touch-friendly interface for mobile users</li>
            </ul>
        </div>
        
        <div class="paf-readme-section">
            <h2>🆘 Support Resources</h2>
            <ul>
                <li><strong>Plugin Documentation:</strong> This page! Bookmark it for future reference.</li>
                <li><strong>API Documentation:</strong> <a href="https://rescuegroups.org/services/adoptable-pet-data-api/" target="_blank">RescueGroups.org API Docs</a></li>
                <li><strong>WordPress Forums:</strong> Search for similar issues or ask questions</li>
                <li><strong>Error Logs:</strong> Always check the <a href="?page=pet-adoption-finder&tab=api-log">API Error Log</a> first</li>
            </ul>
        </div>
        
        <div class="paf-readme-section">
            <h2>📋 System Requirements</h2>
            <ul>
                <li><strong>WordPress:</strong> 5.8 or higher</li>
                <li><strong>PHP:</strong> 7.4 or higher</li>
                <li><strong>MySQL:</strong> 5.6 or higher</li>
                <li><strong>HTTPS:</strong> Recommended (required for some API features)</li>
                <li><strong>cURL:</strong> PHP extension must be enabled</li>
                <li><strong>JSON:</strong> PHP extension must be enabled</li>
            </ul>
        </div>
        
        <div class="paf-readme-section">
            <h2>🔄 Regular Maintenance</h2>
            <p><strong>Recommended Tasks:</strong></p>
            <ul>
                <li><strong>Weekly:</strong> Review API Error Log for issues</li>
                <li><strong>Monthly:</strong> Clear old error logs (keep last 30 days)</li>
                <li><strong>Quarterly:</strong> Test all shortcodes and functionality</li>
                <li><strong>Annually:</strong> Review and update API key if needed</li>
            </ul>
        </div>
        
        <div class="paf-readme-section">
            <h2>📄 Version Information</h2>
            <ul>
                <li><strong>Plugin Version:</strong> <?php echo PAF_VERSION; ?></li>
                <li><strong>API Version:</strong> RescueGroups.org v5</li>
                <li><strong>Last Updated:</strong> <?php echo date('F j, Y'); ?></li>
            </ul>
        </div>
        
        <hr>
        
        <div class="paf-readme-footer">
            <h2>Thank you for using Pet Adoption Finder! 🐾</h2>
            <p>You're helping pets find their forever homes. Every adoption starts with a search.</p>
            <p><strong>Made with ❤️ for animal lovers everywhere.</strong></p>
        </div>
        
    </div>
    
    <style>
    /* Documentation styles included in admin.css */
    </style>
    <?php
}
```

**Testing Steps:**
1. Navigate to Documentation tab
2. Verify all sections render correctly
3. Test all internal links (to other tabs)
4. Test external links open in new tabs
5. Verify code examples display correctly
6. Check mobile responsiveness of documentation

**Deliverable:** Comprehensive documentation accessible within WordPress admin.

---

### 5.5 Admin AJAX Handlers

**Objective:** Handle admin-specific AJAX requests (test connection, clear cache, etc.).

**File:** `includes/admin-ajax.php`

**Implementation:**

```php
<?php
/**
 * File: admin-ajax.php
 * Description: Admin AJAX request handlers
 * 
 * @package PetAdoptionFinder
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test API connection
 */
add_action('wp_ajax_paf_test_api_connection', 'paf_test_api_connection');
function paf_test_api_connection() {
    check_ajax_referer('paf_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    
    $api_key = get_option('paf_api_key');
    
    if (empty($api_key)) {
        wp_send_json_error(array('message' => 'API key is not set'));
    }
    
    // Test connection with a simple search
    $response = paf_api_get('/public/animals/search/available/dogs/', array(
        'limit' => 1
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array(
            'message' => $response->get_error_message()
        ));
    }
    
    wp_send_json_success(array(
        'message' => 'API connection successful! Your API key is working correctly.'
    ));
}

/**
 * Clear all cache
 */
add_action('wp_ajax_paf_clear_cache', 'paf_clear_cache');
function paf_clear_cache() {
    check_ajax_referer('paf_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error();
    }
    
    global $wpdb;
    
    // Delete all pet-related transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_pet_%' 
         OR option_name LIKE '_transient_timeout_pet_%'"
    );
    
    wp_send_json_success(array(
        'message' => 'All cache cleared successfully!'
    ));
}

/**
 * Get log details for modal
 */
add_action('wp_ajax_paf_get_log_details', 'paf_get_log_details');
function paf_get_log_details() {
    check_ajax_referer('paf_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error();
    }
    
    $log_id = isset($_POST['log_id']) ? absint($_POST['log_id']) : 0;
    
    if (!$log_id) {
        wp_send_json_error();
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'paf_error_log';
    
    $log = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $log_id
    ), ARRAY_A);
    
    if (!$log) {
        wp_send_json_error();
    }
    
    // Format request data for display
    $request_data = json_decode($log['request_data'], true);
    $log['request_data'] = wp_json_encode($request_data, JSON_PRETTY_PRINT);
    
    wp_send_json_success($log);
}

/**
 * Export logs to CSV
 */
add_action('wp_ajax_paf_export_logs', 'paf_export_logs');
function paf_export_logs() {
    check_ajax_referer('paf_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'paf_error_log';
    
    $logs = $wpdb->get_results(
        "SELECT * FROM $table_name ORDER BY created_at DESC",
        ARRAY_A
    );
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="pet-adoption-finder-error-log-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, array(
        'ID', 
        'Date/Time', 
        'Error Type', 
        'Error Message', 
        'Response Code',
        'User ID', 
        'IP Address',
        'User Agent'
    ));
    
    // CSV rows
    foreach ($logs as $log) {
        fputcsv($output, array(
            $log['id'],
            $log['created_at'],
            $log['error_type'],
            $log['error_message'],
            $log['response_code'],
            $log['user_id'],
            $log['ip_address'],
            substr($log['user_agent'], 0, 100) // Truncate long user agents
        ));
    }
    
    fclose($output);
    exit;
}
```

**Testing Steps:**
1. Test "Test API Connection" button with valid key
2. Test with invalid key - should show error
3. Test "Clear All Cache" button
4. Verify cache is actually cleared (check database)
5. Test "View Details" on error log entry
6. Test CSV export - download and verify contents

**Deliverable:** All admin AJAX endpoints functional.

---

### 5.6 Admin JavaScript

**Objective:** Handle admin interface interactions.

**File:** `assets/js/admin.js`

**Implementation:**

```javascript
/**
 * File: admin.js
 * Description: Admin interface JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Test API Connection
        $('#test-api-connection').on('click', function() {
            const $button = $(this);
            const $result = $('#api-test-result');
            const originalText = $button.text();
            
            $button.prop('disabled', true)
                   .html('<span class="dashicons dashicons-update spin"></span> Testing...');
            $result.html('<span class="spinner is-active" style="float:none;margin:0 5px;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'paf_test_api_connection',
                    nonce: pafAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<span style="color:#46b450;"><span class="dashicons dashicons-yes-alt"></span> ' + response.data.message + '</span>');
                    } else {
                        $result.html('<span style="color:#dc3232;"><span class="dashicons dashicons-dismiss"></span> ' + response.data.message + '</span>');
                    }
                },
                error: function(xhr, status, error) {
                    $result.html('<span style="color:#dc3232;"><span class="dashicons dashicons-dismiss"></span> Connection failed: ' + error + '</span>');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                    
                    // Clear result after 5 seconds
                    setTimeout(function() {
                        $result.fadeOut(function() {
                            $(this).html('').show();
                        });
                    }, 5000);
                }
            });
        });
        
        // Clear All Cache
        $('#clear-all-cache').on('click', function() {
            if (!confirm('Are you sure you want to clear all cached data? This will temporarily increase API requests.')) {
                return;
            }
            
            const $button = $(this);
            const $result = $('#cache-clear-result');
            const originalText = $button.text();
            
            $button.prop('disabled', true)
                   .html('<span class="dashicons dashicons-update spin"></span> Clearing...');
            $result.html('<span class="spinner is-active" style="float:none;margin:0 5px;"></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'paf_clear_cache',
                    nonce: pafAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $result.html('<span style="color:#46b450;"><span class="dashicons dashicons-yes-alt"></span> ' + response.data.message + '</span>');
                    } else {
                        $result.html('<span style="color:#dc3232;"><span class="dashicons dashicons-dismiss"></span> Failed to clear cache</span>');
                    }
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                    
                    setTimeout(function() {
                        $result.fadeOut(function() {
                            $(this).html('').show();
                        });
                    }, 3000);
                }
            });
        });
        
        // View Log Details
        $(document).on('click', '.view-log-details', function() {
            const logId = $(this).data('log-id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'paf_get_log_details',
                    log_id: logId,
                    nonce: pafAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        let content = '<div class="paf-log-details">';
                        content += '<h2>Error Log Details</h2>';
                        content += '<table class="widefat">';
                        content += '<tr><th>Date/Time:</th><td>' + data.created_at + '</td></tr>';
                        content += '<tr><th>Error Type:</th><td><span class="paf-error-badge paf-error-' + data.error_type.toLowerCase() + '">' + data.error_type + '</span></td></tr>';
                        content += '<tr><th>Message:</th><td>' + data.error_message + '</td></tr>';
                        
                        if (data.response_code) {
                            content += '<tr><th>Response Code:</th><td><code>' + data.response_code + '</code></td></tr>';
                        }
                        
                        if (data.user_id) {
                            content += '<tr><th>User ID:</th><td>' + data.user_id + '</td></tr>';
                        }
                        
                        content += '<tr><th>IP Address:</th><td>' + data.ip_address + '</td></tr>';
                        content += '<tr><th>User Agent:</th><td style="word-break:break-all;"><small>' + data.user_agent + '</small></td></tr>';
                        content += '</table>';
                        
                        content += '<h3 style="margin-top:20px;">Request Data</h3>';
                        content += '<pre style="background:#f5f5f5;padding:15px;overflow:auto;max-height:300px;border:1px solid #ddd;border-radius:4px;"><code>' + data.request_data + '</code></pre>';
                        
                        content += '</div>';
                        
                        $('#log-details-modal .log-details-content').html(content);
                        
                        $('#log-details-modal').dialog({
                            modal: true,
                            width: 700,
                            maxHeight: 600,
                            title: 'Error Log Details - ID: ' + logId,
                            buttons: {
                                Close: function() {
                                    $(this).dialog('close');
                                }
                            }
                        });
                    }
                }
            });
        });
        
        // Select All Logs
        $('#select-all-logs').on('change', function() {
            $('input[name="log_ids[]"]').prop('checked', $(this).prop('checked'));
        });
        
        // Apply Filters (Error Log)
        $('#apply-filters').on('click', function() {
            const errorType = $('#error-type-filter').val();
            const dateFilter = $('#date-filter').val();
            let url = window.location.href.split('?')[0] + '?page=pet-adoption-finder&tab=api-log';
            
            if (errorType) {
                url += '&error_type=' + encodeURIComponent(errorType);
            }
            if (dateFilter) {
                url += '&date_filter=' + encodeURIComponent(dateFilter);
            }
            
            window.location.href = url;
        });
        
        // Export Logs
        $('#export-logs').on('click', function() {
            window.location.href = ajaxurl + '?action=paf_export_logs&nonce=' + pafAdmin.nonce;
        });
        
        // Add spinning animation for dashicons
        const style = document.createElement('style');
        style.textContent = `
            @keyframes paf-spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            .dashicons.spin {
                animation: paf-spin 1s linear infinite;
            }
        `;
        document.head.appendChild(style);
        
    });
    
})(jQuery);
```

**Testing Steps:**
1. Test all button interactions
2. Verify loading states appear correctly
3. Test modal dialogs
4. Test error handling (disconnect internet, try API test)
5. Verify success/error messages display and fade out

**Deliverable:** Complete admin interface with all interactions working.

---

## Phase 6: SEO & Social Meta Tags

### 6.1 Meta Tags Implementation

**Objective:** Add SEO-friendly and social media sharing meta tags to pet detail pages.

**File:** `includes/meta-tags.php`

**Implementation:**

```php
<?php
/**
 * File: meta-tags.php
 * Description: SEO and social sharing meta tags
 * 
 * @package PetAdoptionFinder
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add meta tags to pet detail pages
 */
add_action('wp_head', 'paf_add_detail_meta_tags');
function paf_add_detail_meta_tags() {
    $pet_id = get_query_var('pet_id');
    
    if (empty($pet_id)) {
        return;
    }
    
    // Get pet data
    $response = paf_get_pet_by_id($pet_id, true);
    
    if (is_wp_error($response) || $response === null) {
        return;
    }
    
    $pet = isset($response['data']) ? $response['data'] : null;
    $included = isset($response['included']) ? $response['included'] : array();
    
    if (empty($pet)) {
        return;
    }
    
    $attributes = $pet['attributes'];
    $name = isset($attributes['name']) ? esc_attr($attributes['name']) : 'Adoptable Pet';
    $description = isset($attributes['descriptionText']) ? esc_attr(wp_trim_words($attributes['descriptionText'], 30)) : '';
    $image_url = paf_get_primary_image($pet, $included, 'large');
    $detail_url = paf_get_pet_detail_url($pet_id);
    $breed = paf_get_breed_names($pet, $included);
    
    // Build description
    $meta_description = $name;
    if ($breed) {
        $meta_description .= ' - ' . $breed;
    }
    if ($description) {
        $meta_description .= '. ' . $description;
    }
    
    ?>
    <!-- Pet Adoption Finder Meta Tags -->
    
    <!-- General Meta -->
    <meta name="description" content="<?php echo esc_attr($meta_description); ?>">
    <meta name="keywords" content="pet adoption, adopt <?php echo esc_attr(strtolower($name)); ?>, <?php echo esc_attr($breed); ?>, adoptable pets">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>">
    <meta property="og:title" content="<?php echo esc_attr($name); ?> - Available for Adoption">
    <meta property="og:description" content="<?php echo esc_attr($meta_description); ?>">
    <meta property="og:image" content="<?php echo esc_url($image_url); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="<?php echo esc_url($detail_url); ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr($name); ?> - Available for Adoption">
    <meta name="twitter:description" content="<?php echo esc_attr($meta_description); ?>">
    <meta name="twitter:image" content="<?php echo esc_url($image_url); ?>">
    
    <!-- Pinterest -->
    <meta property="og:image:alt" content="<?php echo esc_attr($name); ?> - Adoptable Pet">
    
    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Product",
        "name": "<?php echo esc_js($name); ?>",
        "description": "<?php echo esc_js($meta_description); ?>",
        "image": "<?php echo esc_url($image_url); ?>",
        "url": "<?php echo esc_url($detail_url); ?>",
        "offers": {
            "@type": "Offer",
            "availability": "https://schema.org/InStock",
            "price": "0",
            "priceCurrency": "USD"
        }
    }
    </script>
    
    <!-- End Pet Adoption Finder Meta Tags -->
    <?php
}

/**
 * Filter page title for pet detail pages
 */
add_filter('pre_get_document_title', 'paf_filter_page_title', 99);
function paf_filter_page_title($title) {
    $pet_id = get_query_var('pet_id');
    
    if (empty($pet_id)) {
        return $title;
    }
    
    // Get pet data (use cached version for performance)
    $response = paf_get_pet_by_id($pet_id, false);
    
    if (is_wp_error($response) || $response === null) {
        return $title;
    }
    
    $pet = isset($response['data']) ? $response['data'] : null;
    
    if (empty($pet)) {
        return $title;
    }
    
    $name = isset($pet['attributes']['name']) ? esc_html($pet['attributes']['name']) : 'Adoptable Pet';
    
    return $name . ' - Available for Adoption | ' . get_bloginfo('name');
}
```

**Testing Steps:**
1. Access pet detail page
2. View page source and verify meta tags present
3. Use Facebook Sharing Debugger: https://developers.facebook.com/tools/debug/
4. Use Twitter Card Validator: https://cards-dev.twitter.com/validator
5. Test sharing on Facebook, Twitter
6. Verify correct image, title, description appear in previews

**Deliverable:** SEO and social meta tags working on detail pages.

---

## Phase 7: Styling & Design System

### 7.1 Main Plugin CSS

**Objective:** Implement complete design system with all component styles.

**File:** `assets/css/pet-finder.css`

**Implementation:**

```css
/**
 * File: pet-finder.css
 * Description: Main plugin styles
 * Version: 1.0.0
 */

/* ============================================
   CSS Variables (Design Tokens)
   ============================================ */

:root {
    /* Primary Colors */
    --paf-primary: #CC0000;
    --paf-primary-hover: #B30000;
    --paf-primary-dark: #990000;
    
    /* Accent Colors */
    --paf-accent: #0080FF;
    --paf-accent-hover: #0066CC;
    --paf-accent-light: #E6F2FF;
    
    /* Success Colors */
    --paf-success: #22C55E;
    --paf-success-hover: #16A34A;
    
    /* Grayscale */
    --paf-white: #FFFFFF;
    --paf-gray-50: #F9FAFB;
    --paf-gray-100: #F3F4F6;
    --paf-gray-200: #E5E7EB;
    --paf-gray-300: #D1D5DB;
    --paf-gray-400: #9CA3AF;
    --paf-gray-500: #6B7280;
    --paf-gray-600: #4B5563;
    --paf-gray-700: #374151;
    --paf-gray-800: #1F2937;
    --paf-gray-900: #111827;
    
    /* Semantic Colors */
    --paf-error: #EF4444;
    --paf-warning: #F59E0B;
    --paf-info: #3B82F6;
    
    /* Typography */
    --paf-font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    --paf-font-mono: ui-monospace, SFMono-Regular, "SF Mono", Monaco, Consolas, monospace;
    
    /* Font Sizes */
    --paf-text-xs: 0.75rem;
    --paf-text-sm: 0.875rem;
    --paf-text-base: 1rem;
    --paf-text-lg: 1.125rem;
    --paf-text-xl: 1.25rem;
    --paf-text-2xl: 1.5rem;
    --paf-text-3xl: 1.875rem;
    --paf-text-4xl: 2.25rem;
    
    /* Font Weights */
    --paf-font-normal: 400;
    --paf-font-medium: 500;
    --paf-font-semibold: 600;
    --paf-font-bold: 700;
    
    /* Line Heights */
    --paf-leading-tight: 1.25;
    --paf-leading-normal: 1.5;
    --paf-leading-relaxed: 1.625;
    
    /* Spacing */
    --paf-space-1: 0.25rem;
    --paf-space-2: 0.5rem;
    --paf-space-3: 0.75rem;
    --paf-space-4: 1rem;
    --paf-space-5: 1.25rem;
    --paf-space-6: 1.5rem;
    --paf-space-8: 2rem;
    --paf-space-10: 2.5rem;
    --paf-space-12: 3rem;
    --paf-space-16: 4rem;
    
    /* Border Radius */
    --paf-radius-sm: 0.125rem;
    --paf-radius-base: 0.25rem;
    --paf-radius-md: 0.375rem;
    --paf-radius-lg: 0.5rem;
    --paf-radius-xl: 0.75rem;
    --paf-radius-2xl: 1rem;
    --paf-radius-full: 9999px;
    
    /* Shadows */
    --paf-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --paf-shadow-base: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --paf-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --paf-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --paf-shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* ============================================
   Reset & Base Styles
   ============================================ */

.paf-filter-sidebar,
.paf-filter-sidebar *,
.paf-results-grid,
.paf-results-grid *,
.paf-detail-page,
.paf-detail-page * {
    box-sizing: border-box;
}

/* ============================================
   Filter Sidebar
   ============================================ */

.paf-filter-sidebar {
    background-color: var(--paf-primary);
    padding: var(--paf-space-6);
    border-radius: var(--paf-radius-lg);
    box-shadow: var(--paf-shadow-md);
    max-width: 280px;
    width: 100%;
    font-family: var(--paf-font-sans);
}

/* Filter Tabs */
.paf-filter-tabs {
    display: flex;
    background-color: var(--paf-white);
    border-radius: var(--paf-radius-base);
    padding: var(--paf-space-1);
    margin-bottom: var(--paf-space-6);
    gap: var(--paf-space-1);
}

.paf-filter-tab {
    flex: 1;
    padding: var(--paf-space-2) var(--paf-space-4);
    border: none;
    background: transparent;
    border-radius: var(--paf-radius-base);
    font-size: var(--paf-text-sm);
    font-weight: var(--paf-font-semibold);
    color: var(--paf-gray-700);
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
    font-family: var(--paf-font-sans);
}

.paf-filter-tab:hover {
    background-color: var(--paf-gray-50);
    color: var(--paf-gray-900);
}

.paf-filter-tab.active {
    background-color: var(--paf-accent);
    color: var(--paf-white);
    box-shadow: var(--paf-shadow-sm);
}

.paf-filter-tab:focus {
    outline: 2px solid var(--paf-accent-light);
    outline-offset: 2px;
}

/* Filter Groups */
.paf-filter-group {
    margin-bottom: var(--paf-space-5);
}

.paf-filter-label {
    display: block;
    font-size: var(--paf-text-sm);
    font-weight: var(--paf-font-bold);
    color: var(--paf-white);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: var(--paf-space-2);
    font-family: var(--paf-font-sans);
}

/* Text Input */
.paf-input-wrapper {
    position: relative;
}

.paf-text-input {
    width: 100%;
    padding: var(--paf-space-3) var(--paf-space-4);
    padding-right: var(--paf-space-10);
    font-size: var(--paf-text-base);
    font-family: var(--paf-font-sans);
    color: var(--paf-gray-800);
    background-color: var(--paf-white);
    border: 2px solid var(--paf-white);
    border-radius: var(--paf-radius-base);
    transition: all 0.2s ease;
}

.paf-text-input::placeholder {
    color: var(--paf-gray-400);
}

.paf-text-input:hover {
    border-color: var(--paf-gray-200);
}

.paf-text-input:focus {
    outline: none;
    border-color: var(--paf-accent);
    box-shadow: 0 0 0 3px var(--paf-accent-light);
}

.paf-input-icon {
    position: absolute;
    right: var(--paf-space-3);
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    color: var(--paf-success);
    pointer-events: none;
}

/* Select Dropdown */
.paf-select-wrapper {
    position: relative;
}

.paf-select {
    width: 100%;
    padding: var(--paf-space-3) var(--paf-space-10) var(--paf-space-3) var(--paf-space-4);
    font-size: var(--paf-text-base);
    font-family: var(--paf-font-sans);
    color: var(--paf-gray-700);
    background-color: var(--paf-white);
    border: 2px solid var(--paf-white);
    border-radius: var(--paf-radius-base);
    appearance: none;
    cursor: pointer;
    transition: all 0.2s ease;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right var(--paf-space-3) center;
    background-size: 20px;
}

.paf-select:hover {
    border-color: var(--paf-gray-200);
}

.paf-select:focus {
    outline: none;
    border-color: var(--paf-accent);
    box-shadow: 0 0 0 3px var(--paf-accent-light);
}

.paf-select:disabled {
    background-color: var(--paf-gray-100);
    color: var(--paf-gray-400);
    cursor: not-allowed;
    opacity: 0.6;
}

/* Helper Text */
.paf-helper-text {
    display: block;
    font-size: var(--paf-text-sm);
    color: var(--paf-white);
    margin-top: var(--paf-space-2);
    opacity: 0.9;
    font-family: var(--paf-font-sans);
}

/* Filter Actions */
.paf-filter-actions {
    display: flex;
    gap: var(--paf-space-3);
    margin-top: var(--paf-space-6);
    padding-top: var(--paf-space-6);
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.paf-button-clear {
    flex: 1;
    padding: var(--paf-space-3) var(--paf-space-4);
    font-size: var(--paf-text-sm);
    font-weight: var(--paf-font-semibold);
    color: var(--paf-white);
    background-color: transparent;
    border: 2px solid var(--paf-white);
    border-radius: var(--paf-radius-base);
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: var(--paf-font-sans);
}

.paf-button-clear:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.paf-button-clear:active {
    transform: scale(0.98);
}

.paf-button-apply {
    flex: 1;
    padding: var(--paf-space-3) var(--paf-space-4);
    font-size: var(--paf-text-sm);
    font-weight: var(--paf-font-semibold);
    color: var(--paf-primary);
    background-color: var(--paf-white);
    border: 2px solid var(--paf-white);
    border-radius: var(--paf-radius-base);
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: var(--paf-font-sans);
}

.paf-button-apply:hover {
    background-color: var(--paf-gray-50);
}

.paf-button-apply:active {
    transform: scale(0.98);
}

/* ============================================
   Search Results Grid
   ============================================ */

.paf-results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: var(--paf-space-6);
    padding: var(--paf-space-6);
}

@media (max-width: 640px) {
    .paf-results-grid {
        grid-template-columns: 1fr;
        gap: var(--paf-space-4);
        padding: var(--paf-space-4);
    }
}

@media (min-width: 1280px) {
    .paf-results-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

/* Pet Card */
.paf-pet-card {
    background-color: var(--paf-white);
    border-radius: var(--paf-radius-lg);
    overflow: hidden;
    box-shadow: var(--paf-shadow-base);
    transition: all 0.3s ease;
    cursor: pointer;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.paf-pet-card:hover {
    box-shadow: var(--paf-shadow-lg);
    transform: translateY(-4px);
}

.paf-pet-card:focus-within {
    outline: 2px solid var(--paf-accent);
    outline-offset: 2px;
}

.paf-pet-card-link {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    height: 100%;
}

/* Pet Image */
.paf-pet-image-wrapper {
    position: relative;
    width: 100%;
    padding-top: 75%;
    background-color: var(--paf-gray-100);
    overflow: hidden;
}

.paf-pet-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.paf-pet-card:hover .paf-pet-image {
    transform: scale(1.05);
}

/* Status Badge */
.paf-pet-status-badge {
    position: absolute;
    top: var(--paf-space-3);
    right: var(--paf-space-3);
    padding: var(--paf-space-1) var(--paf-space-3);
    background-color: var(--paf-success);
    color: var(--paf-white);
    font-size: var(--paf-text-xs);
    font-weight: var(--paf-font-semibold);
    text-transform: uppercase;
    border-radius: var(--paf-radius-full);
    box-shadow: var(--paf-shadow-md);
}

.paf-pet-status-badge.pending {
    background-color: var(--paf-warning);
}

/* Pet Content */
.paf-pet-content {
    padding: var(--paf-space-4);
    flex: 1;
    display: flex;
    flex-direction: column;
}

.paf-pet-name {
    font-size: var(--paf-text-xl);
    font-weight: var(--paf-font-bold);
    color: var(--paf-gray-900);
    margin: 0 0 var(--paf-space-2) 0;
    line-height: var(--paf-leading-tight);
    font-family: var(--paf-font-sans);
}

.paf-pet-breed {
    font-size: var(--paf-text-sm);
    color: var(--paf-gray-600);
    margin: 0 0 var(--paf-space-3) 0;
    font-family: var(--paf-font-sans);
}

/* Pet Meta */
.paf-pet-meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--paf-space-3);
    margin-top: auto;
    padding-top: var(--paf-space-3);
    border-top: 1px solid var(--paf-gray-200);
}

.paf-pet-meta-item {
    display: flex;
    align-items: center;
    gap: var(--paf-space-1);
    font-size: var(--paf-text-xs);
    color: var(--paf-gray-600);
    font-family: var(--paf-font-sans);
}

.paf-pet-meta-icon {
    width: 14px;
    height: 14px;
    color: var(--paf-gray-400);
}

/* ============================================
   Loading States
   ============================================ */

.paf-loading-initial,
.paf-loading-more {
    text-align: center;
    padding: var(--paf-space-8);
}

.paf-spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid var(--paf-gray-200);
    border-top-color: var(--paf-accent);
    border-radius: 50%;
    animation: paf-spin 0.8s linear infinite;
}

@keyframes paf-spin {
    to { transform: rotate(360deg); }
}

/* ============================================
   Empty States
   ============================================ */

.paf-empty-state {
    text-align: center;
    padding: var(--paf-space-16) var(--paf-space-6);
}

.paf-empty-icon {
    font-size: 4rem;
    margin-bottom: var(--paf-space-4);
}

.paf-empty-state h3 {
    font-size: var(--paf-text-2xl);
    font-weight: var(--paf-font-semibold);
    color: var(--paf-gray-900);
    margin-bottom: var(--paf-space-2);
}

.paf-empty-state p {
    font-size: var(--paf-text-base);
    color: var(--paf-gray-600);
}

/* ============================================
   Notices
   ============================================ */

.paf-notice {
    padding: var(--paf-space-4);
    border-radius: var(--paf-radius-md);
    margin-bottom: var(--paf-space-4);
}

.paf-notice-error {
    background-color: #FEF2F2;
    border: 2px solid var(--paf-error);
    color: #991B1B;
}

.paf-notice-warning {
    background-color: #FFFBEB;
    border: 2px solid var(--paf-warning);
    color: #92400E;
}

.paf-notice-info {
    background-color: #EFF6FF;
    border: 2px solid var(--paf-info);
    color: #1E40AF;
}

/* ============================================
   Responsive Adjustments
   ============================================ */

@media (max-width: 768px) {
    .paf-filter-sidebar {
        max-width: 100%;
        margin-bottom: var(--paf-space-6);
    }
}

/* Continue in Part 3 with detail page styles... */
```

**Testing Steps:**
1. Load filter box - verify red background, white inputs
2. Test tab active states (blue accent color)
3. Verify hover states on all interactive elements
4. Test responsive behavior (resize browser)
5. Verify loading spinner animates
6. Check empty states display correctly

**Deliverable:** Complete design system CSS for filter box and search grid.

---

**Note:** The PRD continues in Part 3 with:
- Remaining CSS (detail pages, buttons, etc.)
- Admin CSS
- Phase 8: Testing procedures
- Phase 9: Deployment
- Appendices with quick reference guides

Would you like me to create Part 3?
