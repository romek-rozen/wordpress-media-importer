#!/bin/bash

# Exit on any error
set -e

# --- Configuration ---
# Define plugin slug from the main plugin file name
PLUGIN_SLUG="external-media-importer"
PLUGIN_FILE="${PLUGIN_SLUG}.php"

ZIP_FILE="${PLUGIN_SLUG}.zip"

# Define paths
# The directory where the script is located
PROJECT_ROOT=$(pwd)
# A temporary directory for building the zip
BUILD_PATH="/tmp/${PLUGIN_SLUG}"

# --- Script ---
echo "Starting build process for ${PLUGIN_SLUG}..."

# 1. Clean up previous build files
echo "Cleaning up previous build files..."
rm -f "${PROJECT_ROOT}/${PLUGIN_SLUG}.*.zip"
rm -rf "$BUILD_PATH"
mkdir -p "$BUILD_PATH"

# 2. Copy plugin files to the build directory
echo "Copying plugin files to build directory: ${BUILD_PATH}"
rsync -av --progress . "$BUILD_PATH/" \
--exclude ".git" \
--exclude ".github" \
--exclude "assets" \
--exclude "node_modules" \
--exclude "src" \
--exclude ".gitignore" \
--exclude "PLAN.md" \
--exclude "WORDPRESS_DEVELOPER.md" \
--exclude "Plugin_Developer_FAQ.md" \
--exclude "*.zip" \
--exclude "build.sh" \
--exclude ".*" \
--exclude ".DS_store" \
--exclude "/admin/.DS_store"

# Copy assets separately to a subdirectory within the build
mkdir -p "${BUILD_PATH}/assets"
rsync -av --progress ./assets/ "${BUILD_PATH}/assets/"

# 3. Create the zip file
echo "Creating zip file: ${ZIP_FILE}"
cd "$BUILD_PATH"
# Remove any .DS_Store files before zipping
find . -name ".DS_Store" -delete
zip -r "${PROJECT_ROOT}/${ZIP_FILE}" .

# 4. Clean up the build directory
echo "Cleaning up build directory..."
rm -rf "$BUILD_PATH"

echo "--------------------------------------------------"
echo "Build complete!"
echo "Zip file created at: ${PROJECT_ROOT}/${ZIP_FILE}"
echo "--------------------------------------------------"
