<?php
/*
Plugin Name: KSAS Bulletin Board
Plugin URI: http://krieger2.jhu.edu/comm/web/plugins/bulletin-board
Description: Creates a custom post type for bulletins.  Link to http://siteurl/bulletinboard/*bbtype-slug* to display bulletins.  Bulletins do not display in homepage news feed. Plugin also creates a widget to display bulletins in sidebars.  Use in conjunction with Post Expirator plugin if you want bulletins to automatically expire/archive/delete.
Version: 2.1
Author: Cara Peckens
Author URI: mailto:cpeckens@jhu.edu
License: GPL2
*/

// registration code for bulletinboard post type
	function register_bulletinboard_posttype() {
		$labels = array(
			'name' 				=> _x( 'Bulletins', 'post type general name' ),
			'singular_name'		=> _x( 'Bulletin', 'post type singular name' ),
			'add_new' 			=> _x( 'Add New', 'Bulletin'),
			'add_new_item' 		=> __( 'Add New Bulletin '),
			'edit_item' 		=> __( 'Edit Bulletin '),
			'new_item' 			=> __( 'New Bulletin '),
			'view_item' 		=> __( 'View Bulletin '),
			'search_items' 		=> __( 'Search Bulletins '),
			'not_found' 		=>  __( 'No Bulletin found' ),
			'not_found_in_trash'=> __( 'No Bulletins found in Trash' ),
			'parent_item_colon' => ''
		);

		$taxonomies = array('bbtype');

		$supports = array('title','editor','thumbnail','excerpt','revisions');

		$post_type_args = array(
			'labels' 			=> $labels,
			'singular_label' 	=> __('Bulletin'),
			'public' 			=> true,
			'show_ui' 			=> true,
			'publicly_queryable'=> true,
			'query_var'			=> true,
			'capability_type'   => 'bulletin',
			'capabilities' => array(
				'publish_posts' => 'publish_bulletins',
				'edit_posts' => 'edit_bulletins',
				'edit_others_posts' => 'edit_others_bulletins',
				'delete_posts' => 'delete_bulletins',
				'delete_others_posts' => 'delete_others_bulletins',
				'read_private_posts' => 'read_private_bulletins',
				'edit_post' => 'edit_bulletin',
				'delete_post' => 'delete_bulletin',
				'read_post' => 'read_bulletin',),
			'has_archive' 		=> true,
			'hierarchical' 		=> false,
			'rewrite' 			=> array('slug' => 'bulletin-board', 'with_front' => false ),
			'supports' 			=> $supports,
			'menu_position' 	=> 5,
			'taxonomies'		=> $taxonomies
		 );
		 register_post_type('bulletinboard',$post_type_args);
	}
	add_action('init', 'register_bulletinboard_posttype');
// registration code for bbtype taxonomy
function register_bbtype_tax() {
	$labels = array(
		'name' 					=> _x( 'Bulletin Types', 'taxonomy general name' ),
		'singular_name' 		=> _x( 'Bulletin Type', 'taxonomy singular name' ),
		'add_new' 				=> _x( 'Add New Bulletin Type', 'Bulletin Type'),
		'add_new_item' 			=> __( 'Add New Bulletin Type' ),
		'edit_item' 			=> __( 'Edit Bulletin Type' ),
		'new_item' 				=> __( 'New Bulletin Type' ),
		'view_item' 			=> __( 'View Bulletin Type' ),
		'search_items' 			=> __( 'Search Bulletin Types' ),
		'not_found' 			=> __( 'No Bulletin Type found' ),
		'not_found_in_trash' 	=> __( 'No Bulletin Type found in Trash' ),
	);

	$pages = array('bulletinboard');

	$args = array(
		'labels' 			=> $labels,
		'singular_label' 	=> __('Bulletin Type'),
		'public' 			=> true,
		'show_ui' 			=> true,
		'hierarchical' 		=> true,
		'show_tagcloud' 	=> false,
		'show_in_nav_menus' => false,
		'rewrite' 			=> array('slug' => 'bbtype', 'with_front' => false ),
	 );
	register_taxonomy('bbtype', $pages, $args);
}
add_action('init', 'register_bbtype_tax');

function check_bbtype_terms(){

        // see if we already have populated any terms
    $term = get_terms( 'bbtype', array( 'hide_empty' => false ) );

    // if no terms then lets add our terms
    if( empty( $term ) ){
        $terms = define_bbtype_terms();
        foreach( $terms as $term ){
            if( !term_exists( $term['name'], 'bbtype' ) ){
                wp_insert_term( $term['name'], 'bbtype', array( 'slug' => $term['slug'] ) );
            }
        }
    }
}

add_action( 'init', 'check_bbtype_terms' );

function define_bbtype_terms(){

$terms = array(
		'0' => array( 'name' => 'undergraduate','slug' => 'undergrad-bb'),
		'1' => array( 'name' => 'graduate','slug' => 'graduate-bb'),
		);

    return $terms;
}


//CREATE COLUMNS IN ADMIN

add_filter( 'manage_edit-bulletinboard_columns', 'my_bulletinboard_columns' ) ;

function my_bulletinboard_columns( $columns ) {

	$columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => __( 'Name' ),
		'type' => __( 'Type' ),
		'date' => __( 'Date' ),
	);

	return $columns;
}

add_action( 'manage_bulletinboard_posts_custom_column', 'my_manage_bulletinboard_columns', 10, 2 );

function my_manage_bulletinboard_columns( $column, $post_id ) {
	global $post;

	switch( $column ) {

		/* If displaying the 'role' column. */
		case 'type' :

			/* Get the roles for the post. */
			$terms = get_the_terms( $post_id, 'bbtype' );

			/* If terms were found. */
			if ( !empty( $terms ) ) {

				$out = array();

				/* Loop through each term, linking to the 'edit posts' page for the specific term. */
				foreach ( $terms as $term ) {
					$out[] = sprintf( '<a href="%s">%s</a>',
						esc_url( add_query_arg( array( 'post_type' => $post->post_type, 'bbtype' => $term->slug ), 'edit.php' ) ),
						esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'bbtype', 'display' ) )
					);
				}

				/* Join the terms, separating them with a comma. */
				echo join( ', ', $out );
			}

			/* If no terms were found, output a default message. */
			else {
				_e( 'No Type Assigned' );
			}

			break;

		/* Just break out of the switch statement for everything else. */
		default :
			break;
	}
}

// CREATE FILTERS WITH CUSTOM TAXONOMIES


function bulletinboard_add_taxonomy_filters() {
	global $typenow;

	// An array of all the taxonomyies you want to display. Use the taxonomy name or slug
	$taxonomies = array('bbtype', 'filter');

	// must set this to the post type you want the filter(s) displayed on
	if ( $typenow == 'bulletinboard' ) {

		foreach ( $taxonomies as $tax_slug ) {
			$current_tax_slug = isset( $_GET[$tax_slug] ) ? $_GET[$tax_slug] : false;
			$tax_obj = get_taxonomy( $tax_slug );
			$tax_name = $tax_obj->labels->name;
			$terms = get_terms($tax_slug);
			if ( count( $terms ) > 0) {
				echo "<select name='$tax_slug' id='$tax_slug' class='postform'>";
				echo "<option value=''>$tax_name</option>";
				foreach ( $terms as $term ) {
					echo '<option value=' . $term->slug, $current_tax_slug == $term->slug ? ' selected="selected"' : '','>' . $term->name .' (' . $term->count .')</option>';
				}
				echo "</select>";
			}
		}
	}
}

add_action( 'restrict_manage_posts', 'bulletinboard_add_taxonomy_filters' );

/*************Bulletin Board Widget*****************/
class Bulletin_Board_Widget extends WP_Widget {
	/* Define Widget */
	public function __construct() {
		$widget_options = array( 'classname' => 'ksas_bulletin', 'description' => __('Displays bulletin board entries based on category', 'ksas_bulletin') );
		$control_options = array( 'width' => 300, 'height' => 350, 'id_base' => 'ksas_bulletin-widget' );
		parent::__construct( 'ksas_bulletin-widget', __('Bulletin Board', 'ksas_bulletin'), $widget_options, $control_options );
	}

	/* Update/Save the widget settings. */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$new_instance = wp_parse_args((array) $new_instance, array( 'title' => ''));
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['category_choice'] = $new_instance['category_choice'];
		$instance['quantity'] = $new_instance['quantity'];
		return $instance;
	}

	/* Widget Options */
	public function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'title' => __('Bulletin Board', 'ksas_bulletin'), 'quantity' => __('3', 'ksas_bulletin'), 'category_choice' => '1' );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>

		<!-- Number of Stories: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'quantity' ); ?>"><?php _e('Number of stories to display:', 'ksas_recent'); ?></label>
			<input id="<?php echo $this->get_field_id( 'quantity' ); ?>" name="<?php echo $this->get_field_name( 'quantity' ); ?>" value="<?php echo $instance['quantity']; ?>" style="width:100%;" />
		</p>
		<!-- Choose Bulletin Type: Select Box -->
		<p>
			<label for="<?php echo $this->get_field_id( 'category_choice' ); ?>"><?php _e('Choose Category:', 'ksas_bulletin'); ?></label>
			<select id="<?php echo $this->get_field_id( 'category_choice' ); ?>" name="<?php echo $this->get_field_name( 'category_choice' ); ?>" class="widefat" style="width:100%;">
			<?php global $wpdb;
				$categories = get_categories(array(
								'orderby'                  => 'name',
								'order'                    => 'ASC',
								'hide_empty'               => 1,
								'taxonomy' => 'bbtype'));
		    foreach($categories as $category){
		    	$category_choice = $category->slug;
		        $category_title = $category->name; ?>
		       <option value="<?php echo $category_choice; ?>" <?php if ( $category_choice == $instance['category_choice'] ) echo 'selected="selected"'; ?>><?php echo $category_title; ?></option>
		    <?php } ?>
			</select>
		</p>
	<?php }	

	/* Widget Display */
	public function widget( $args, $instance ) {
		extract($args);
		/* Our variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'] );
		$category_choice = $instance['category_choice'];
		$quantity = $instance['quantity'];
		echo $before_widget;

		/* Display the widget title if one was input (before and after defined by themes). */
		if ( $title )
			echo $before_title . $title . $after_title;
			$bulletin_board_query = new WP_Query(array(
			"post_type" => "bulletinboard",
			"bbtype" => $category_choice,
			"post_status" => "publish",
			"posts_per_page" => $quantity)); ?>

			<?php if ( $bulletin_board_query->have_posts() ) : while ($bulletin_board_query->have_posts()) : $bulletin_board_query->the_post(); ?>
			<article aria-label="<?php the_title(); ?>" class="row" role="article">
				<div class="small-12 columns">
				<h4><?php the_time('F j, Y'); ?></h4>
				<h5><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h5>
					<p><?php echo wp_trim_words( get_the_excerpt(), 10, '...' ); ?></p>
				</div>
			</article>
	<?php endwhile; ?>
		<article aria-label="Bulletins Archive">
			<p><a href="<?php echo home_url('/bbtype/'); echo $category_choice;?>">View more Bulletins <span class="fa fa-chevron-circle-right" aria-hidden="true"></span></a></p>
		</article>
	
	<?php endif; echo $after_widget;

	}

}

	//Register bulletin board widget
	add_action('widgets_init', 'ksas_register_bulletin_widgets');
	function ksas_register_bulletin_widgets() {
		register_widget('Bulletin_Board_Widget');
	}

?>