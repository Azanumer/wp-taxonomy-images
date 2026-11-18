<?php
/**
 * Plugin Name:       WP Taxonomy Images
 * Plugin URI:        https://github.com/Azanumer/wp-taxonomy-images
 * Description:       Give categories, tags and custom taxonomies a featured image — media-picker on the term screen, template helpers, optional archive display.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Azan Umer
 * Author URI:        https://azanumer.cv
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-taxonomy-images
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WTI_VERSION', '1.0.0' );
define( 'WTI_META_KEY', 'wti_image_id' );
define( 'WTI_OPTION', 'wti_settings' );

/**
 * Taxonomies that get an image field. Filter to add or remove.
 *
 * @return string[]
 */
function wti_get_taxonomies() {
	$taxonomies = get_taxonomies( array( 'show_ui' => true ), 'names' );
	$taxonomies = array_values( (array) $taxonomies );

	/**
	 * Filters the taxonomies that get an image field.
	 *
	 * @param string[] $taxonomies Taxonomy slugs.
	 */
	return apply_filters( 'wti_taxonomies', $taxonomies );
}

/**
 * Default settings.
 *
 * @return array
 */
function wti_default_settings() {
	return array(
		'show_on_archive' => false, // Prepend the image to the archive description.
		'size'            => 'large',
	);
}

/**
 * Get settings merged over defaults.
 *
 * @return array
 */
function wti_get_settings() {
	$saved = get_option( WTI_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return array_merge( wti_default_settings(), $saved );
}

/* -------------------------------------------------------------------------
 * Template helpers
 * ------------------------------------------------------------------------- */

/**
 * Get the image attachment ID for a term.
 *
 * @param int|WP_Term|null $term Term ID, term object, or null for the queried term.
 * @return int Attachment ID, or 0 when no image is set.
 */
function wti_get_term_image_id( $term = null ) {
	if ( null === $term ) {
		$term = get_queried_object();
	}
	if ( $term instanceof WP_Term ) {
		$term_id = $term->term_id;
	} else {
		$term_id = (int) $term;
	}
	if ( $term_id <= 0 ) {
		return 0;
	}
	$image_id = (int) get_term_meta( $term_id, WTI_META_KEY, true );
	if ( $image_id > 0 && 'attachment' === get_post_type( $image_id ) ) {
		return $image_id;
	}
	return 0;
}

/**
 * Get the image URL for a term.
 *
 * @param int|WP_Term|null $term Term ID, term object, or null for the queried term.
 * @param string           $size Image size name. Default 'large'.
 * @return string Image URL, or '' when no image is set.
 */
function wti_get_term_image_url( $term = null, $size = 'large' ) {
	$image_id = wti_get_term_image_id( $term );
	if ( 0 === $image_id ) {
		return '';
	}
	$url = wp_get_attachment_image_url( $image_id, $size );
	return $url ? $url : '';
}

/**
 * Echo an <img> tag for a term image. Prints nothing when no image is set.
 *
 * @param int|WP_Term|null $term Term ID, term object, or null for the queried term.
 * @param string           $size Image size name. Default 'large'.
 * @param array            $attr Extra attributes for wp_get_attachment_image().
 */
function wti_the_term_image( $term = null, $size = 'large', $attr = array() ) {
	$image_id = wti_get_term_image_id( $term );
	if ( 0 === $image_id ) {
		return;
	}
	$attr = array_merge(
		array( 'class' => 'wti-term-image' ),
		(array) $attr
	);
	echo wp_get_attachment_image( $image_id, $size, false, $attr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/* -------------------------------------------------------------------------
 * Optional archive display
 * ------------------------------------------------------------------------- */

/**
 * Prepend the term image to the archive description when enabled.
 *
 * @param string $description Archive description HTML.
 * @return string
 */
function wti_maybe_show_on_archive( $description ) {
	$settings = wti_get_settings();
	if ( empty( $settings['show_on_archive'] ) ) {
		return $description;
	}
	if ( ! is_category() && ! is_tag() && ! is_tax() ) {
		return $description;
	}
	$image_id = wti_get_term_image_id();
	if ( 0 === $image_id ) {
		return $description;
	}
	$image = wp_get_attachment_image(
		$image_id,
		$settings['size'],
		false,
		array( 'class' => 'wti-term-image wti-archive-image' )
	);
	return $image . $description;
}
add_filter( 'get_the_archive_description', 'wti_maybe_show_on_archive' );

/* -------------------------------------------------------------------------
 * Admin: term add/edit screens
 * ------------------------------------------------------------------------- */

/**
 * Render the image field on the add-term form.
 *
 * @param string $taxonomy Taxonomy slug.
 */
function wti_add_term_fields( $taxonomy ) {
	?>
	<div class="form-field term-image-wrap">
		<label for="wti-image-id"><?php esc_html_e( 'Image', 'wp-taxonomy-images' ); ?></label>
		<?php wti_render_picker( 0 ); ?>
		<p><?php esc_html_e( 'Featured image for this term. Shown on the archive page when enabled, and available in templates via wti_get_term_image_url().', 'wp-taxonomy-images' ); ?></p>
	</div>
	<?php
}

/**
 * Render the image field on the edit-term form.
 *
 * @param WP_Term $term Term being edited.
 */
function wti_edit_term_fields( $term ) {
	$image_id = wti_get_term_image_id( $term );
	?>
	<tr class="form-field term-image-wrap">
		<th scope="row">
			<label for="wti-image-id"><?php esc_html_e( 'Image', 'wp-taxonomy-images' ); ?></label>
		</th>
		<td>
			<?php wti_render_picker( $image_id ); ?>
			<p class="description"><?php esc_html_e( 'Featured image for this term. Shown on the archive page when enabled, and available in templates via wti_get_term_image_url().', 'wp-taxonomy-images' ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * Render the media-picker controls (preview + select/remove buttons + hidden field).
 *
 * @param int $image_id Currently selected attachment ID.
 */
function wti_render_picker( $image_id ) {
	$image_id  = (int) $image_id;
	$preview   = $image_id > 0 ? wp_get_attachment_image( $image_id, 'thumbnail' ) : '';
	$has_image = $image_id > 0;
	?>
	<div class="wti-picker">
		<div class="wti-preview"><?php echo $preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		<input type="hidden" id="wti-image-id" name="wti_image_id" value="<?php echo esc_attr( $image_id ); ?>">
		<p>
			<button type="button" class="button wti-select-image"><?php esc_html_e( 'Choose image', 'wp-taxonomy-images' ); ?></button>
			<button type="button" class="button wti-remove-image" <?php echo $has_image ? '' : 'style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'wp-taxonomy-images' ); ?></button>
		</p>
	</div>
	<?php
}

/**
 * Enqueue the media library + picker script on term screens.
 *
 * @param string $hook Current admin page hook.
 */
function wti_enqueue_term_assets( $hook ) {
	if ( 'term.php' !== $hook && 'edit-tags.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->base, array( 'edit-tags', 'term' ), true ) ) {
		return;
	}
	if ( ! in_array( $screen->taxonomy, wti_get_taxonomies(), true ) ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script(
		'wti-term-picker',
		plugins_url( 'assets/js/wti-picker.js', __FILE__ ),
		array( 'jquery' ),
		WTI_VERSION,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'wti_enqueue_term_assets' );

/**
 * Save the image field when a term is created or edited.
 *
 * @param int $term_id Term ID.
 */
function wti_save_term_image( $term_id ) {
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	if ( ! isset( $_POST['wti_image_id'] ) ) {
		return;
	}
	$image_id = (int) $_POST['wti_image_id']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $image_id > 0 && 'attachment' === get_post_type( $image_id ) ) {
		update_term_meta( $term_id, WTI_META_KEY, $image_id );
	} else {
		delete_term_meta( $term_id, WTI_META_KEY );
	}
}

/**
 * Hook the field + save handler for every image-enabled taxonomy.
 */
function wti_register_term_hooks() {
	foreach ( wti_get_taxonomies() as $taxonomy ) {
		add_action( "{$taxonomy}_add_form_fields", 'wti_add_term_fields' );
		add_action( "{$taxonomy}_edit_form_fields", 'wti_edit_term_fields' );
		add_action( "created_{$taxonomy}", 'wti_save_term_image' );
		add_action( "edited_{$taxonomy}", 'wti_save_term_image' );
	}
}
add_action( 'init', 'wti_register_term_hooks' );

/* -------------------------------------------------------------------------
 * Settings
 * ------------------------------------------------------------------------- */

/**
 * Register the settings page under Settings.
 */
function wti_register_settings_page() {
	add_options_page(
		__( 'Taxonomy Images', 'wp-taxonomy-images' ),
		__( 'Taxonomy Images', 'wp-taxonomy-images' ),
		'manage_options',
		'wp-taxonomy-images',
		'wti_render_settings_page'
	);
}
add_action( 'admin_menu', 'wti_register_settings_page' );

/**
 * Register the settings with the Settings API.
 */
function wti_register_settings() {
	register_setting(
		'wti_settings_group',
		WTI_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'wti_sanitize_settings',
			'default'           => wti_default_settings(),
		)
	);
}
add_action( 'admin_init', 'wti_register_settings' );

/**
 * Sanitize settings input.
 *
 * @param array $input Raw submitted values.
 * @return array
 */
function wti_sanitize_settings( $input ) {
	$clean = wti_default_settings();
	if ( ! is_array( $input ) ) {
		return $clean;
	}
	$clean['show_on_archive'] = ! empty( $input['show_on_archive'] );
	if ( ! empty( $input['size'] ) ) {
		$size = sanitize_key( $input['size'] );
		if ( in_array( $size, get_intermediate_image_sizes(), true ) || in_array( $size, array( 'full', 'thumbnail', 'medium', 'large' ), true ) ) {
			$clean['size'] = $size;
		}
	}
	return $clean;
}

/**
 * Render the settings page.
 */
function wti_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$settings = wti_get_settings();
	$sizes    = array_unique( array_merge( array( 'thumbnail', 'medium', 'large', 'full' ), get_intermediate_image_sizes() ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Taxonomy Images', 'wp-taxonomy-images' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'wti_settings_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Show on archive pages', 'wp-taxonomy-images' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( WTI_OPTION ); ?>[show_on_archive]" value="1" <?php checked( $settings['show_on_archive'] ); ?>>
							<?php esc_html_e( 'Display the term image above the archive description', 'wp-taxonomy-images' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wti-size"><?php esc_html_e( 'Archive image size', 'wp-taxonomy-images' ); ?></label>
					</th>
					<td>
						<select id="wti-size" name="<?php echo esc_attr( WTI_OPTION ); ?>[size]">
							<?php foreach ( $sizes as $size ) : ?>
								<option value="<?php echo esc_attr( $size ); ?>" <?php selected( $settings['size'], $size ); ?>><?php echo esc_html( $size ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<h2><?php esc_html_e( 'Using images in templates', 'wp-taxonomy-images' ); ?></h2>
		<p><code>wti_get_term_image_url()</code> — <?php esc_html_e( 'URL of the current term\'s image, or empty when none is set.', 'wp-taxonomy-images' ); ?></p>
		<p><code>wti_the_term_image()</code> — <?php esc_html_e( 'prints the <img> tag for the current term\'s image.', 'wp-taxonomy-images' ); ?></p>
		<p><?php esc_html_e( 'Both accept a term ID or term object as the first argument.', 'wp-taxonomy-images' ); ?></p>
	</div>
	<?php
}
