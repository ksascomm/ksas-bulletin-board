<?php
/*
Plugin Name: KSAS Bulletin Board
Plugin URI: http://krieger2.jhu.edu/comm/web/plugins/bulletin-board
Description: Creates a custom post type for bulletins.  Link to http://siteurl/archive-bulletinboard.php to display bulletins.  Bulletins do not display in homepage news feed. Plugin also creates a widget to display bulletins in sidebars.  Use in conjunction with Post Expirator plugin if you want bulletins to automatically expire/archive/delete.
Version: 1.0
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
		
		$taxonomies = array('category');
		
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
			'rewrite' 			=> array('slug' => 'bulletin_board', 'with_front' => false ),
			'supports' 			=> $supports,
			'menu_position' 	=> 5,
			'taxonomies'		=> $taxonomies
		 );
		 register_post_type('bulletinboard',$post_type_args);
	}
	add_action('init', 'register_bulletinboard_posttype');

//Register bulletin board widget
add_action('widgets_init', 'ksas_register_bulletin_widgets');
	function ksas_register_bulletin_widgets() {
		register_widget('Bulletin_Board_Widget');
	}

// Define bulletin board widget
class Bulletin_Board_Widget extends WP_Widget {

	function Bulletin_Board_Widget() {
		$widget_options = array( 'classname' => 'ksas_bulletin', 'description' => __('Displays bulletin board entries based on category', 'ksas_bulletin') );
		$control_options = array( 'width' => 300, 'height' => 350, 'id_base' => 'ksas_bulletin-widget' );
		$this->WP_Widget( 'ksas_bulletin-widget', __('Bulletin Board', 'ksas_bulletin'), $widget_options, $control_options );
	}

	function widget( $args, $instance ) {
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
			"cat" => $category_choice,
			"post_status" => "publish",
			"posts_per_page" => $quantity)); ?>
			
			<?php while ($bulletin_board_query->have_posts()) : $bulletin_board_query->the_post(); ?>
			<article>
				<a href="<?php the_permalink(); ?>">
					<h6><?php the_date(); ?></h6>
					<p><b><?php the_title(); ?></b></br>
					<?php echo get_the_excerpt(); ?></p>
				</a>
			</article>
	<?php endwhile; ?>
	<p align="right"><a href="<?php echo home_url('/category/'); echo $category_choice;?>?post_type=bulletinboard">View more<span class="icon-arrow-right"></span></a></p>
</div>


<?php echo $after_widget;

	}

	/* Widget Options */
	function form( $instance ) {

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
		<!-- Choose Profile Type: Select Box -->
		<p>
			<label for="<?php echo $this->get_field_id( 'category_choice' ); ?>"><?php _e('Choose Category:', 'ksas_bulletin'); ?></label> 
			<select id="<?php echo $this->get_field_id( 'category_choice' ); ?>" name="<?php echo $this->get_field_name( 'category_choice' ); ?>" class="widefat" style="width:100%;">
			<?php global $wpdb;
				$categories = get_categories(array(
								'orderby'                  => 'name',
								'order'                    => 'ASC',
								'hide_empty'               => 1,
								'taxonomy' => 'category'));
		    foreach($categories as $category){
		    	$category_choice = $category->slug;
		        $category_title = $category->name; ?>
		       <option value="<?php echo $category_choice; ?>" <?php if ( $category_choice == $instance['category_choice'] ) echo 'selected="selected"'; ?>><?php echo $category_title; ?></option>
		    <?php } ?>
			</select>
		</p>
	<?php
	}
}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$new_instance = wp_parse_args((array) $new_instance, array( 'title' => ''));
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['category_choice'] = $new_instance['category_choice'];
		$instance['quantity'] = $new_instance['quantity'];
		return $instance;
	}

?>