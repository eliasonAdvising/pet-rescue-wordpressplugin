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
            const originalText = $button.html();

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
                    $button.prop('disabled', false).html(originalText);

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
            const originalText = $button.html();

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
                    $button.prop('disabled', false).html(originalText);

                    setTimeout(function() {
                        $result.fadeOut(function() {
                            $(this).html('').show();
                        });
                    }, 3000);
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
