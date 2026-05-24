# ACF Post Object Enrichment

Automatically attach additional ACF field values to every `WP_Post` object returned by an ACF **Post Object** or **Relationship** field — no extra `get_field()` calls needed in your templates.

## How it works

The plugin adds an **"Attach additional fields"** setting to the ACF field editor for Post Object and Relationship fields. Select which ACF fields you want pre-loaded, and those values will be available as properties directly on every `WP_Post` object the field returns.

```php
// Without this plugin
$posts = get_field( 'featured_articles' );
foreach ( $posts as $post ) {
    $hero = get_field( 'hero_image', $post->ID );
    $date = get_field( 'publish_date', $post->ID );
}

// With this plugin (after selecting hero_image + publish_date in the field settings)
$posts = get_field( 'featured_articles' );
foreach ( $posts as $post ) {
    $hero = $post->hero_image;
    $date = $post->publish_date;
}
```

## Requirements

- WordPress 5.9+
- PHP 8.1+
- [Advanced Custom Fields](https://www.advancedcustomfields.com/) (free or Pro)

## Installation

1. Download or clone this repository into your `wp-content/plugins/` directory.
2. Activate **ACF Post Object Enrichment** from the WordPress Plugins screen.

Auto-updates are delivered via GitHub — the plugin will appear in your WordPress updates dashboard whenever a new release is published.

## Usage

1. Open any ACF field group and edit a **Post Object** or **Relationship** field.
2. Scroll to the **"Attach additional fields"** setting.
3. Click **+ Add field** and select the fields you want attached. The picker shows all ACF fields registered for the post types the field targets.
4. Save the field group.

From this point on, `get_field()` (or `the_field()`) for that field will return `WP_Post` objects with the selected fields already populated as properties.

> **Note:** The enrichment setting is hidden when the Post Object field's **Return Format** is set to *Post ID*, since enrichment only applies to object returns.

## Notes

- Values are resolved using `get_field()`, so they respect ACF's formatting — images return arrays, relationships return arrays of `WP_Post` objects, etc.
- The picker auto-detects which post types the field targets and scopes the field list accordingly. It falls back to all available ACF fields when no post type filter is set.
- Field names are stored as a comma-separated string internally; you can also type them directly if preferred.

## License

GPL-2.0-or-later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

## Author

Built by [SnippetNest](https://snippetnest.com). Plugin detail page: [snippetnest.com/snippet/extend-acf-post-object-relationship-fields-custom-data](https://snippetnest.com/snippet/extend-acf-post-object-relationship-fields-custom-data/).
