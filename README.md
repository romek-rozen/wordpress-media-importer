# Wordpress External Media Importer

**Authors:** Roman Rozenberger & Cline
**Version:** 1.0

A simple WordPress plugin that helps to automate the process of migrating external media to the local media library.

## Main Features

- **Content Scanning:** Scans selected post types (posts, pages, products, custom post types) for external media.
- **Filter by Extension:** Allows you to define which file types to search for (e.g., `jpg, png, webp, pdf`).
- **Media Detection:** Finds media links embedded in content (in Gutenberg blocks, classic `<img>` and `<a>` tags) and as featured images.
- **Interactive Import:** Displays a list of found media and allows the user to select which ones to import.
- **Automatic Link Replacement:** After importing a file into the media library, the plugin automatically updates the post content, replacing the old, external URL with the new, local one.
- **Background Processing:** The import is handled via AJAX requests for each file individually, which prevents server timeout errors and provides real-time progress feedback.

## How to Use

1.  Install and activate the plugin.
2.  Navigate to the new admin menu item: **External Media**.
3.  In the **File Extensions** field, ensure all the extensions you are looking for are listed.
4.  In the **Content Types** section, check the post types you want to scan.
5.  Click **Scan Now**.
6.  After a moment, a results table will appear below. Check the boxes next to the media you want to import.
7.  Click **Import Selected Media** and watch the progress.

## Workflow Diagram

The following diagram illustrates the plugin's workflow.

```mermaid
sequenceDiagram
    participant User
    participant Browser (Admin Panel)
    participant Server (WordPress PHP)
    participant DB as Database

    %% Step 1: Scanning
    User->>Browser: 1. Selects post types and clicks "Scan Now"
    Browser->>Server: 2. Sends AJAX request (action: eid_scan_content)
    Server->>DB: 3. WP_Query: Fetches posts of selected types
    DB-->>Server: 4. Returns list of posts
    loop For each post
        Server->>Server: 5. Scans `post_content` and `post_thumbnail` for external URLs
    end
    Server-->>Browser: 6. Returns JSON response with list of found media (or empty)
    Browser->>User: 7. Displays results table

    %% Step 2: Importing
    User->>Browser: 8. Checks boxes and clicks "Import Selected Media"
    Browser->>Browser: 9. Creates a queue of files to import
    loop For each file in queue
        Browser->>Server: 10. Sends AJAX request (action: eid_import_media_item, file URL)
        Server->>Server: 11. Downloads file from external URL
        Server->>DB: 12. Saves file to Media Library and creates new attachment
        DB-->>Server: 13. Returns new attachment ID and its local URL
        Server->>DB: 14. Updates `post_content` replacing old URL with new one
        Server-->>Browser: 15. Returns JSON response (success/error)
        Browser->>User: 16. Updates status in the table (e.g., "Imported")
    end
```

## License

This plugin is licensed under the GPLv2 or later. See the [LICENSE](LICENSE) file for more details.
