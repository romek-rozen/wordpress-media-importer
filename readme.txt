=== Wordpress External Media Importer ===
Contributors: romanrozen
Tags: images, import, external, download, media
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple plugin to find and import external media into your WordPress media library.

== Description ==

This plugin helps automate the process of migrating external media to your local library. It scans your content, finds external images and files, and allows you to import them with a single click, automatically updating the links in your posts.

**Key Features:**
*   Scans posts, pages, products, and any custom post types.
*   Allows you to specify which file extensions to look for (e.g., `jpg, png, webp, pdf`).
*   Finds media in post content (including Gutenberg blocks) and as featured images.
*   Displays a clear list of found media for you to review.
*   Lets you select which files to import.
*   Downloads the files, adds them to the media library, and updates the URLs in the content.
*   Processes imports one-by-one via AJAX to prevent server timeouts.

== Installation ==

1.  Upload the `external-media-downloader` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to the "External Media" page in your admin menu to start scanning.

== Changelog ==

= 1.0 =
* Initial release.
