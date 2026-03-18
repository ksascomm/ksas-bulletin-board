<?php
/**
 * KSAS Bulletin Board
 *
 * @package     KSAS_Bulletin_Board
 * @author      KSAS Communications
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: KSAS Bulletin Board
 * Plugin URI:  https://github.com/ksascomm/ksas-bulletin-board
 * Description: Creates a custom post type for bulletins.
 * Version:     5.0
 */

/**
 * 1. AUTOMATIC EXPIRATION LOGIC
 * Runs daily to move bulletins older than 9 months to draft.
 */

/**
 * Schedules the daily expiration check if it isn't already set.
 *
 * @return void
 */
function ksas_bulletin_schedule_expiration() {
	if ( ! wp_next_scheduled( 'ksas_bulletin_daily_expire_event' ) ) {
		wp_schedule_event( time(), 'daily', 'ksas_bulletin_daily_expire_event' );
	}
}
add_action( 'wp', 'ksas_bulletin_schedule_expiration' );

/**
 * The callback function that finds and drafts old bulletins.
 *
 * @return void
 */
function ksas_bulletin_expire_old_posts() {
	$args = array(
		'post_type'      => 'bulletinboard',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'date_query'     => array(
			array(
				'column' => 'post_date',
				'before' => '9 months ago',
			),
		),
	);

	$old_bulletins = get_posts( $args );

	foreach ( $old_bulletins as $bulletin ) {
		wp_update_post(
			array(
				'ID'          => $bulletin->ID,
				'post_status' => 'draft',
			)
		);
	}
}
add_action( 'ksas_bulletin_daily_expire_event', 'ksas_bulletin_expire_old_posts' );

/**
 * 2. POST TYPE & TAXONOMY REGISTRATION
 */

/**
 * Registers the 'bulletinboard' post type and 'bbtype' taxonomy.
 *
 * @return void
 */
function ksas_bulletin_register_core() {
	// Post Type.
	register_post_type(
		'bulletinboard',
		array(
			'labels'          => array( 'name' => __( 'Bulletins', 'ksas-bulletin' ) ),
			'public'          => true,
			'show_ui'         => true,
			'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'show_in_rest'    => true,
			'has_archive'     => true,
			'menu_position'   => 5,
			'rewrite'         => array(
				'slug'       => 'bulletin-board',
				'with_front' => false,
			),
			'capability_type' => 'post', // Simplifies permission management.
		)
	);

	// Taxonomy.
	register_taxonomy(
		'bbtype',
		array( 'bulletinboard' ),
		array(
			'hierarchical' => true,
			'labels'       => array( 'name' => __( 'Bulletin Types', 'ksas-bulletin' ) ),
			'public'       => true,
			'show_in_rest' => true,
			'rewrite'      => array(
				'slug'       => 'bbtype',
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'ksas_bulletin_register_core' );

/**
 * 3. ADMIN UI (Columns & Late Escaping)
 */

/**
 * Renders data for custom admin columns with secure late escaping.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 * @return void
 */
function ksas_bulletin_render_columns( $column, $post_id ) {
	if ( 'type' === $column ) {
		$terms = get_the_terms( $post_id, 'bbtype' );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$links = array();
			foreach ( $terms as $term ) {
				$links[] = sprintf(
					'<a href="%s">%s</a>',
					esc_url(
						add_query_arg(
							array(
								'post_type' => 'bulletinboard',
								'bbtype'    => $term->slug,
							),
							'edit.php'
						)
					),
					esc_html( $term->name )
				);
			}
			echo wp_kses_post( implode( ', ', $links ) );
		} else {
			esc_html_e( 'No Type Assigned', 'ksas-bulletin' );
		}
	}
}
add_action( 'manage_bulletinboard_posts_custom_column', 'ksas_bulletin_render_columns', 10, 2 );

/**
 * 4. Register ACF Fields for Bulletins.
 *
 * @return void
 */
function ksas_bulletin_add_acf_fields() {
	if ( function_exists( 'acf_add_local_field_group' ) ) :

		acf_add_local_field_group(
			array(
				'key'                   => 'group_bulletin_details',
				'title'                 => __( 'Bulletin Details', 'ksas-bulletin' ),
				'fields'                => array(
					array(
						'key'            => 'field_bulletin_deadline',
						'label'          => __( 'Bulletin Deadline', 'ksas-bulletin' ),
						'name'           => 'bulletin_deadline',
						'type'           => 'date_picker',
						'instructions'   => __( 'Select the deadline for this bulletin.', 'ksas-bulletin' ),
						'required'       => 1,
						'display_format' => 'F j, Y',
						'return_format'  => 'F j, Y',
						'first_day'      => 1,
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'bulletinboard',
						),
					),
				),
				'menu_order'            => 0,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'active'                => true,
			)
		);

	endif;
}
add_action( 'acf/init', 'ksas_bulletin_add_acf_fields' );
