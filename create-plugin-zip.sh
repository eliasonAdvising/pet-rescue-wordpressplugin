#!/bin/bash

# Pet Adoption Finder - Plugin ZIP Creation Script
# This script creates a clean ZIP file ready for WordPress upload

echo "🐾 Creating Pet Adoption Finder plugin ZIP file..."

# Remove old ZIP if it exists
if [ -f "pet-adoption-finder.zip" ]; then
    rm pet-adoption-finder.zip
    echo "✓ Removed old ZIP file"
fi

# Create the ZIP file, excluding unnecessary files
zip -r pet-adoption-finder.zip . \
    -x "*.git*" \
    -x "*source_materials*" \
    -x "*.DS_Store" \
    -x "*.md" \
    -x "create-plugin-zip.sh" \
    -x "*.png" \
    -q

echo "✓ ZIP file created successfully!"
echo ""
echo "📦 File: pet-adoption-finder.zip"
echo "📏 Size: $(du -h pet-adoption-finder.zip | cut -f1)"
echo ""
echo "🚀 Next steps:"
echo "1. Log into your WordPress admin panel"
echo "2. Go to Plugins → Add New → Upload Plugin"
echo "3. Choose 'pet-adoption-finder.zip'"
echo "4. Click 'Install Now' then 'Activate'"
echo "5. Configure your API key in Settings → Pet Adoption Finder"
echo ""
echo "📖 See README.md for detailed installation instructions"
echo ""
echo "✨ Done! Your plugin is ready to upload."
