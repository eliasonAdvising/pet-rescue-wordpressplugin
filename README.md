# Pet Adoption Finder - WordPress Plugin

## 🐾 Overview

Pet Adoption Finder is a WordPress plugin that integrates with the RescueGroups.org API to display adoptable dogs and cats on your website. It features an advanced filtering system, infinite scroll search results, and shareable detail pages with real-time availability status.

## ✨ Features

- **Advanced Filtering**: Location, breed, age, sex, and size filters
- **Responsive Design**: Works beautifully on desktop, tablet, and mobile
- **Real-time Data**: Pet detail pages always fetch fresh availability status
- **Shareable URLs**: Clean, SEO-friendly URLs for each pet (`/pet/12345/`)
- **Infinite Scroll**: Smooth browsing experience with automatic content loading
- **Photo Galleries**: Multiple images for each pet with thumbnail navigation
- **Admin Interface**: Comprehensive settings and error logging
- **Error Tracking**: Built-in error logging with filtering and CSV export
- **Accessibility**: WCAG 2.1 AA compliant
- **Performance**: Smart caching strategy to balance freshness and API load

## 📋 Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- Valid RescueGroups.org API key
- cURL PHP extension
- JSON PHP extension
- HTTPS recommended

## 🚀 Installation & Upload to WordPress

### Step 1: Download or Clone the Repository

If you haven't already, download this repository or clone it:

```bash
git clone https://github.com/ianeliason/pet-rescue-wordpressplugin.git
```

### Step 2: Create the Plugin ZIP File

Navigate to the plugin directory and create a ZIP file:

```bash
cd pet-rescue-wordpressplugin
zip -r pet-adoption-finder.zip . -x "*.git*" -x "*source_materials*" -x "*.DS_Store" -x "*.md" -x "README.md"
```

**Or manually:**
1. Select all plugin files EXCEPT:
   - `.git/` folder
   - `source_materials/` folder
   - `.gitignore`
   - This `README.md` (the installation guide)
2. Right-click and create a ZIP archive
3. Name it `pet-adoption-finder.zip`

**Files to include in ZIP:**
```
pet-adoption-finder/
├── assets/
│   ├── css/
│   │   ├── pet-finder.css
│   │   └── admin.css
│   ├── js/
│   │   ├── pet-finder.js
│   │   └── admin.js
│   └── images/
│       ├── placeholder-pet.svg
│       └── placeholder-pet.png
├── includes/
│   ├── admin-ajax.php
│   ├── admin-menu.php
│   ├── admin-settings.php
│   ├── ajax-handlers.php
│   ├── api-handler.php
│   ├── error-logger.php
│   ├── meta-tags.php
│   ├── rewrite-rules.php
│   └── shortcodes.php
├── templates/
│   ├── detail-page.php
│   ├── filter-box.php
│   └── pet-not-found.php
├── pet-adoption-finder.php
└── readme.txt
```

### Step 3: Upload to WordPress

1. **Log into your WordPress admin panel** on your Railway-hosted site
2. Navigate to **Plugins → Add New**
3. Click **Upload Plugin** at the top of the page
4. Click **Choose File** and select `pet-adoption-finder.zip`
5. Click **Install Now**
6. Wait for the upload and installation to complete
7. Click **Activate Plugin**

### Step 4: Configure the Plugin

1. After activation, go to **Settings → Pet Adoption Finder**
2. Enter your **RescueGroups.org API Key**
   - Don't have one? Get it here: https://rescuegroups.org/services/adoptable-pet-data-api/
3. Leave the **API Endpoint** as default: `https://api.rescuegroups.org/v5`
4. Set **Results Per Page**: `12` (recommended)
5. Set **Cache Duration**: `5` minutes (recommended)
6. Enable **Error Logging**: ✅ Checked
7. Click **Save Settings**
8. Click **Test API Connection** to verify everything works

### Step 5: Create Pages

#### Page 1: Pet Search Page

1. Go to **Pages → Add New**
2. Title: `Find a Pet` (or your preferred name)
3. In the content area, add these shortcodes:

```
[pet_filter_box]
[pet_search_grid]
```

4. Publish the page
5. Note the URL (e.g., `yoursite.com/find-a-pet/`)

#### Page 2: Pet Detail Page

1. Go to **Pages → Add New**
2. Title: `Pet Detail`
3. In the content area, add this shortcode:

```
[pet_detail]
```

4. Publish the page

### Step 6: Flush Permalinks

**IMPORTANT:** This step is required for pet detail URLs to work correctly.

1. Go to **Settings → Permalinks**
2. Don't change anything, just click **Save Changes**
3. This flushes WordPress rewrite rules

### Step 7: Test the Plugin

1. Visit your "Find a Pet" page
2. Try the filters (Dogs/Cats/Either tabs)
3. Enter a location (ZIP code) and click Apply
4. Browse the search results
5. Click on a pet card to view the detail page
6. Verify the URL format is: `yoursite.com/pet/12345/`
7. Test the share buttons (Copy Link, Email)

## 🎨 Customization

### Custom CSS

All plugin classes are prefixed with `paf-`. Add custom styles to your theme's `style.css`:

```css
/* Example: Change primary color */
.paf-filter-sidebar {
    background-color: #your-color;
}

/* Example: Customize pet cards */
.paf-pet-card {
    border: 2px solid #your-color;
}
```

### Modify Results Per Page

Use the settings page or add to your theme's `functions.php`:

```php
add_filter('paf_results_per_page', function($per_page) {
    return 20; // Show 20 pets per page
});
```

### Modify Cache Duration

```php
add_filter('paf_cache_duration', function($duration) {
    return 10 * MINUTE_IN_SECONDS; // 10 minutes
});
```

## 🔧 Troubleshooting

### No Pets Showing

1. Check API key in Settings → Pet Adoption Finder
2. Click "Test API Connection"
3. Check **API Error Log** tab for specific errors
4. Verify API key permissions with RescueGroups.org

### Detail Page Shows 404

1. Go to **Settings → Permalinks**
2. Click **Save Changes** (no need to change anything)
3. Try accessing the pet page again
4. If still not working, deactivate and reactivate the plugin

### Filters Not Working

1. Open browser console (F12 → Console tab)
2. Look for JavaScript errors
3. Check for conflicts with other plugins
4. Try disabling other plugins temporarily

### Slow Loading

1. Increase cache duration in settings (try 10-15 minutes)
2. Reduce results per page (try 10-12)
3. Check API Error Log for timeout errors
4. Contact your hosting provider about server performance

## 📊 Admin Features

### Settings Tab
- Configure API credentials
- Set cache duration and results per page
- Test API connection
- View shortcode reference
- Manage cache

### API Error Log Tab
- View all API errors with timestamps
- Filter by error type (Connection, Timeout, Auth, etc.)
- Filter by date range (Today, Last 7 days, Last 30 days)
- Export logs to CSV
- Clear logs individually or all at once
- View error statistics

### Help & Documentation Tab
- Getting started guide
- Troubleshooting tips
- Support resources

## 🔐 Security

- All user inputs are sanitized
- All outputs are escaped
- Nonces used for all forms and AJAX requests
- Database queries use prepared statements
- No direct file access
- API keys stored securely in WordPress options

## 📝 Changelog

### Version 1.0.0 (Current)
- Initial release
- Filter box with Dogs/Cats/Either tabs
- Infinite scroll search results
- Shareable pet detail URLs
- Photo galleries
- Admin interface with settings
- Error logging system
- Mobile responsive
- SEO optimized
- Accessibility compliant

## 🆘 Support

- **Issues**: https://github.com/ianeliason/pet-rescue-wordpressplugin/issues
- **API Documentation**: https://rescuegroups.org/services/adoptable-pet-data-api/
- **WordPress Codex**: https://codex.wordpress.org/

## 📄 License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## 👤 Author

Ian Eliason
- GitHub: [@ianeliason](https://github.com/ianeliason)

## 🙏 Credits

- **RescueGroups.org** for providing the pet adoption API
- **WordPress** community for excellent documentation
- All the animal shelters and rescues working to find pets their forever homes

---

## Quick Reference Commands

### Create ZIP for Upload
```bash
zip -r pet-adoption-finder.zip . -x "*.git*" -x "*source_materials*" -x "*.DS_Store" -x "*.md"
```

### Check File Permissions (if needed)
```bash
chmod -R 755 pet-adoption-finder/
```

### View Plugin Files
```bash
find . -type f -name "*.php" -o -name "*.css" -o -name "*.js"
```

---

**Made with ❤️ for animal lovers everywhere. Every adoption starts with a search.**
