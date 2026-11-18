# WP Taxonomy Images

Give categories, tags and custom taxonomies a featured image — pick it from the Media Library right on the term screen, then show it on archive pages or in your theme.

## Why

Posts have featured images, but terms don't. A category archive with a header image (a "Travel" banner, a product-category photo) looks far more finished than a bare title — and without a plugin you end up hard-coding image URLs in template files. This plugin stores the image as term meta, so it survives theme changes and works with any taxonomy.

## Features

- **Media-picker field** — a "Choose image" button on every category, tag and custom-taxonomy add/edit screen (filter `wti_taxonomies` to change which taxonomies get one).
- **Template helpers** — `wti_get_term_image_id()`, `wti_get_term_image_url( $term, $size )`, and `wti_the_term_image()` print the image for any term; all accept a term ID, a term object, or nothing (current queried term).
- **Optional archive display** — enable "Show on archive pages" in Settings and the image is prepended to the archive description at your chosen size.
- **Safe storage** — the saved value is validated as a real attachment; deleting the image clears the field instead of leaving a broken reference.

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate it from **Plugins**.
3. Edit any category or tag and choose an image; optionally enable archive display under **Settings → Taxonomy Images**.

## Template example

```php
<?php if ( is_category() ) : ?>
    <div class="category-hero">
        <?php wti_the_term_image( null, 'large' ); ?>
        <h1><?php single_cat_title(); ?></h1>
    </div>
<?php endif; ?>
```

## Uninstall

Deleting the plugin removes its settings and all term image assignments.
