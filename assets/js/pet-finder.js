/**
 * File: pet-finder.js
 * Description: Frontend functionality for pet search
 */

(function($) {
    'use strict';

    // Pet Search Grid Controller
    const PetSearchGrid = {
        currentPage: 1,
        loading: false,
        hasMore: true,
        filters: {},
        petType: 'dogs',

        init: function() {
            this.setupFilterTabs();
            this.setupFilterForm();
            this.setupInfiniteScroll();
            this.setupLocationValidation();
            this.loadInitialPets();
        },

        setupFilterTabs: function() {
            const self = this;

            $('.paf-filter-tab').on('click', function() {
                const $tab = $(this);
                const type = $tab.data('type');

                // Update active state
                $('.paf-filter-tab').removeClass('active').attr('aria-selected', 'false');
                $tab.addClass('active').attr('aria-selected', 'true');

                // Update pet type
                self.petType = type;
                $('#paf-pet-type').val(type);

                // Enable/disable breed filter
                if (type === 'either') {
                    $('#paf-breed').prop('disabled', true).val('');
                    $('#paf-breed').siblings('.paf-helper-text').text('Select Dogs or Cats to enable breed filtering');
                } else {
                    self.loadBreeds(type);
                }

                // Reload results
                self.currentPage = 1;
                self.hasMore = true;
                self.applyFilters(true);
            });
        },

        loadBreeds: function(petType) {
            const $breedSelect = $('#paf-breed');
            const $helperText = $breedSelect.siblings('.paf-helper-text');

            $helperText.text('Loading breeds...');
            $breedSelect.prop('disabled', true).html('<option value="">Loading...</option>');

            $.ajax({
                url: petFinderAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'paf_load_breeds',
                    nonce: petFinderAjax.nonce,
                    pet_type: petType
                },
                success: function(response) {
                    if (response.success) {
                        let options = '<option value="">Any Breed</option>';
                        response.data.breeds.forEach(function(breed) {
                            options += '<option value="' + breed + '">' + breed + '</option>';
                        });
                        $breedSelect.html(options).prop('disabled', false);
                        $helperText.text('Select a specific breed or leave as "Any Breed"');
                    } else {
                        $breedSelect.html('<option value="">Any Breed</option>').prop('disabled', false);
                        $helperText.text('Unable to load breeds');
                    }
                },
                error: function() {
                    $breedSelect.html('<option value="">Any Breed</option>').prop('disabled', false);
                    $helperText.text('Unable to load breeds');
                }
            });
        },

        setupFilterForm: function() {
            const self = this;
            let filterTimeout;

            // Handle form submission
            $('#paf-filter-form').on('submit', function(e) {
                e.preventDefault();
                self.applyFilters(true);
            });

            // Handle filter changes (debounced)
            $('#paf-filter-form select, #paf-filter-form input[type="text"]').on('change input', function() {
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(function() {
                    self.applyFilters(true);
                }, 500);
            });

            // Clear filters button
            $('#paf-clear-filters').on('click', function() {
                $('#paf-filter-form')[0].reset();
                $('.paf-input-icon-success').hide();
                self.filters = {};
                self.applyFilters(true);
            });
        },

        setupLocationValidation: function() {
            $('#paf-location').on('input', function() {
                const $input = $(this);
                const value = $input.val();
                const $icon = $input.siblings('.paf-input-icon-success');

                // Validate ZIP code (5 digits)
                if (/^\d{5}$/.test(value)) {
                    $icon.show();
                } else {
                    $icon.hide();
                }
            });
        },

        setupInfiniteScroll: function() {
            const self = this;

            $(window).on('scroll', function() {
                if (self.shouldLoadMore()) {
                    self.loadMorePets();
                }
            });
        },

        shouldLoadMore: function() {
            if (this.loading || !this.hasMore) {
                return false;
            }

            const scrollPosition = $(window).scrollTop() + $(window).height();
            const triggerPoint = $(document).height() - 500;

            return scrollPosition >= triggerPoint;
        },

        getFilters: function() {
            return {
                location: $('#paf-location').val(),
                distance: $('#paf-distance').val(),
                breed: $('#paf-breed').val(),
                age: $('#paf-age').val(),
                sex: $('#paf-sex').val(),
                size: $('#paf-size').val()
            };
        },

        applyFilters: function(replace) {
            this.filters = this.getFilters();
            this.currentPage = 1;
            this.hasMore = true;
            this.loadPets(replace);
        },

        loadInitialPets: function() {
            this.loadPets(true);
        },

        loadMorePets: function() {
            if (!this.loading && this.hasMore) {
                this.currentPage++;
                this.loadPets(false);
            }
        },

        loadPets: function(replace) {
            const self = this;

            self.loading = true;

            if (replace) {
                $('#pet-results-grid').html('<div class="paf-loading-initial"><div class="paf-spinner"></div><p>Loading pets...</p></div>');
                $('#no-results, #end-message').hide();
            } else {
                $('#loading-spinner').show();
            }

            $.ajax({
                url: petFinderAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'paf_filter_pets',
                    nonce: petFinderAjax.nonce,
                    pet_type: self.petType,
                    filters: self.filters,
                    page: self.currentPage
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;

                        if (replace) {
                            $('#pet-results-grid').html(data.html);
                        } else {
                            $('#pet-results-grid').append(data.html);
                        }

                        // Update pagination state
                        self.hasMore = data.has_more;

                        // Show end message if no more results
                        if (!self.hasMore && data.total > 0) {
                            $('#end-message').show();
                        } else {
                            $('#end-message').hide();
                        }

                        // Show no results message if empty
                        if (data.total === 0) {
                            $('#no-results').show();
                            $('#pet-results-grid').hide();
                        } else {
                            $('#no-results').hide();
                            $('#pet-results-grid').show();
                        }
                    } else {
                        console.error('Error loading pets:', response.data);
                        self.showError(response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    self.showError('Unable to load pets. Please check your internet connection and try again.');
                },
                complete: function() {
                    self.loading = false;
                    $('#loading-spinner').hide();
                }
            });
        },

        showError: function(message) {
            const errorHtml = '<div class="paf-notice paf-notice-error"><p>' + message + '</p></div>';
            $('#pet-results-grid').html(errorHtml);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if search grid exists
        if ($('#pet-results-grid').length > 0) {
            PetSearchGrid.init();
        }
    });

})(jQuery);
