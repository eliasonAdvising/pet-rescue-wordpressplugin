=== Pet Adoption Finder ===
Contributors: ianeliason
Tags: pets, adoption, rescue, animals, dogs, cats
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display adoptable pets from RescueGroups.org with advanced filtering and shareable detail pages.

== Description ==

Pet Adoption Finder integrates with RescueGroups.org API to display adoptable dogs and cats on your WordPress website. Features include:

* Advanced filter box (location, breed, age, sex, size)
* Responsive search results grid with infinite scroll
* Detailed pet pages with photo galleries
* Real-time availability status (detail pages always fetch fresh data)
* Shareable pet URLs (yoursite.com/pet/12345/)
* Comprehensive admin interface
* Detailed error logging
* SEO-friendly and accessible
* Mobile responsive design

Perfect for animal shelters, rescue organizations, and animal welfare websites.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/pet-adoption-finder`, or install through WordPress admin
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Get your API key from [RescueGroups.org](https://rescuegroups.org/services/adoptable-pet-data-api/)
4. Navigate to Settings > Pet Adoption Finder and enter your API key
5. Click "Test API Connection" to verify it works
6. Create a new page and add the shortcodes:
   `[pet_filter_box]`
   `[pet_search_grid]`
7. Create another page for pet details and add:
   `[pet_detail]`
8. Go to Settings > Permalinks and click "Save Changes" to flush rewrite rules
9. Publish the pages and start helping pets find homes!

== Frequently Asked Questions ==

= Do I need a RescueGroups.org API key? =

Yes, you need a free API key from RescueGroups.org to use this plugin. Visit their website to request one.

= How often is pet data updated? =

Search results are cached for 5 minutes by default (configurable in settings). Pet detail pages ALWAYS fetch fresh data to ensure availability status is current.

= Can I customize the styling? =

Yes, all plugin elements use the `paf-` CSS prefix. You can override styles in your theme's CSS file.

= Why does a pet show "No Longer Available"? =

This means the pet has been adopted (great news!) or the listing was removed. This is normal and ensures visitors never see outdated information.

= Is this plugin free? =

Yes, the plugin is completely free. You'll need a free RescueGroups.org API key.

= Does it work on mobile? =

Yes! The plugin is fully responsive and works beautifully on phones, tablets, and desktops.

= What happens if the API is down? =

The plugin logs all API errors in the admin interface. Users will see a friendly error message prompting them to try again later.

== Screenshots ==

1. Filter box with location, breed, age, sex, and size filters
2. Responsive search results grid with pet cards
3. Detailed pet page with photo gallery
4. Admin settings interface with API configuration
5. API error log with filtering options

== Changelog ==

= 1.0.0 =
* Initial release
* Filter box shortcode with Dogs/Cats/Either tabs
* Search grid shortcode with infinite scroll
* Pet detail pages with shareable URLs
* Admin interface with comprehensive settings
* Error logging system with export functionality
* Full mobile responsiveness
* SEO meta tags for social sharing
* WCAG 2.1 AA accessibility support

== Upgrade Notice ==

= 1.0.0 =
Initial release of Pet Adoption Finder.

== Additional Information ==

**Support:** For questions and support, visit the [GitHub repository](https://github.com/ianeliason/pet-rescue-wordpressplugin)

**API Documentation:** [RescueGroups.org API v5 Documentation](https://rescuegroups.org/services/adoptable-pet-data-api/)

**Requirements:**
* WordPress 5.8 or higher
* PHP 7.4 or higher
* Valid RescueGroups.org API key
* cURL PHP extension
* JSON PHP extension

**Caching Strategy:**
* Search results: Cached for 5 minutes (configurable)
* Detail pages: NEVER cached (always fresh)
* Breed lists: Cached for 24 hours

This ensures the best balance between performance and data accuracy.
