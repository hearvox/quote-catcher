<?php
/*
Plugin Name: Quote Catcher
Plugin URI: http://transom.org/
Description: Retrieves and displays quotes from posts (stored in custom field).
Version: 0.1
Author: Barrett Golding
Author URI: http://transom.org/
License: GPL2
*/

function quote_catcher() {
	/* Get a random post with a quote in Custom Field */
	$args = array(
		'numberposts' => 1,
		'meta_key' => 'qc_quote',
		'orderby' => 'rand'
	);
	$qc_posts = get_posts( $args );

	/* Set posts default variables: ID, Author, Title, URL */
	$id = $qc_posts[0]->ID;
	$author_id = $qc_posts[0]->post_author;
	$title = $qc_posts[0]->post_title;
	$url = get_permalink( $id );

	/* Get custom-field value for: quote, and any other CF values
	for replacing default: Author, Title */
	$qc_quote = get_post_meta( $id, 'qc_quote', true );
	$qc_quoteby = get_post_meta($id, 'qc_quoteby', true);
	$qc_quotetitle = get_post_meta($id, 'qc_quotetitle', true);

	$quote_catcher_array = array( $id, $author_id, $title, $url, $qc_quote, $qc_quoteby, $qc_quotetitle ); 

	$author_html = '<a href="' . get_author_posts_url( $author_id ) . '" title="Author Posts">' . get_the_author_meta( 'display_name', $author_id ) . '</a>';
	$author = ( ! $quoteby ) ? $author_html : $quoteby;		  
	$title = ( ! $quotetitle ) ? get_the_title( $id ) : $quotetitle;

	return $quote_catcher_array;
}

/* Add box in Edit Post panel for entering quote values */
/* Fire our meta box setup function on the post editor screen. */
add_action( 'load-post.php', 'quote_catcher_box_setup' );
add_action( 'load-post-new.php', 'quote_catcher_box_setup' );

/* Meta box setup function. */
function quote_catcher_box_setup() {

	/* Add meta boxes on the 'add_meta_boxes' hook. */
	add_action( 'add_meta_boxes', 'quote_catcher_box' );

	/* Save post meta on the 'save_post' hook. */
	add_action( 'save_post', 'quote_catcher_box_save', 10, 2 );
}

/* Create a meta box to be displayed on the post editor screen. */
function quote_catcher_box() {
    add_meta_box( 
        'quote_catcher_box',
        esc_html__( 'Quote Catcher', 'example' ),
        'quote_catcher_box_content',
        'post',
        'side',
        'low'
    );
}

/* Display the post meta box. */
function quote_catcher_box_content( $object, $box ) { ?>
	<?php wp_nonce_field( plugin_basename( __FILE__ ), 'quote_catcher_box_content_nonce' ); ?>
	<p><label for="qc_quote"><strong>Quote</strong></label><br />
	<textarea id="qc_quote" name="qc_quote" cols="33" rows="10" tabindex="1"><?php echo esc_attr( get_post_meta( $object->ID, 'qc_quote', true ) ); ?></textarea></p>
	<hr />
	<p class="howto">Quote is attributed to this post's Author and Title. To change either use these fields:<p>
	<label for="qc_quoteby"><strong>Author</strong></label><br />
	<input type="text" id="qc_quoteby" name="qc_quoteby" placeholder="change quote author" size="31" tabindex="2" value="<?php echo esc_attr( get_post_meta( $object->ID, 'qc_quoteby', true ) ); ?>" /><br />
	<label for="qc_quotetitle"><strong>Title</strong></label><br />
	<input type="text" id="qc_quotetitle" name="qc_quotetitle" placeholder="change quote title" size="31" tabindex="3" value="<?php echo esc_attr( get_post_meta( $object->ID, 'qc_quotetitle', true ) ); ?>" />
<?php }

/* Save the meta box's post metadata. */
function quote_catcher_box_save( $post_id, $post ) {

	// Verify the nonce before proceeding.
	if ( !isset( $_POST['quote_catcher_box_content_nonce'] ) || !wp_verify_nonce( $_POST['quote_catcher_box_content_nonce'], plugin_basename( __FILE__ ) ) )
		return $post_id;

	// Get the post type object.
	$post_type = get_post_type_object( $post->post_type );

	// Check if the current user has permission to edit the post.
	if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
		return $post_id;

/* - */

	// Multiple custom field keys to save, update, or delete.
	$meta_key_arr = array( 'qc_quote','qc_quoteby','qc_quotetitle' ); 

	foreach ( $meta_key_arr as $meta_key ) {
		
		// Get the posted data.
		$new_meta_value = ( isset( $_POST[$meta_key] ) ? $_POST[$meta_key] : '' );
		
		// Get the meta value of the custom field key.
 		$meta_value = get_post_meta( $post_id, $meta_key, true );
 		
 		// If a new meta value was added and there was no previous value, add it.
 		//_l('registering '.$meta_key." as $new_meta_value old is $meta_value for id $post_id");
 		if ( $new_meta_value != '' && '' == $meta_value )
 			add_post_meta( $post_id, $meta_key, $new_meta_value, true );
 		
 		// If the new meta value does not match the old value, update it.
 		elseif ( $new_meta_value != '' && $new_meta_value != $meta_value )
 			update_post_meta( $post_id, $meta_key, $new_meta_value );
		
		// If there is no new meta value but an old value exists, delete it.
 		elseif ( '' == $new_meta_value && $meta_value != '' )
 			delete_post_meta( $post_id, $meta_key, $meta_value );
	}
 
}


/**
 * Add function to widgets_init that'll load our widget.
 * @since 0.1
 */
add_action( 'widgets_init', 'quote_catcher_load_widgets' );

/**
 * Register our widget.
 * 'Example_Widget' is the widget class used below.
 *
 * @since 0.1
 */
function quote_catcher_load_widgets() {
	register_widget( 'QuoteCatcher_Widget' );
}

/**
 * Example Widget class.
 * This class handles everything that needs to be handled with the widget:
 * the settings, form, display, and update.  Nice!
 *
 * @since 0.1
 */
class QuoteCatcher_Widget extends WP_Widget {

	/**
	 * Widget setup.
	 */
	function QuoteCatcher_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'quote-catcher', 'description' => __('Widget to display a random quote from a post.', 'example') );

		/* Widget control settings. */
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'quote-catcher-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'quote-catcher-widget', __('Quote Catcher', 'example'), $widget_ops, $control_ops );
	}

	/**
	 * How to display the widget on the screen.
	 */
	function widget( $args, $instance ) {
		extract( $args );

		/* Our variables from the widget settings. */
		$title = apply_filters('widget_title', $instance['title'] );
		$show_title = isset( $instance['show_title'] ) ? $instance['show_title'] : false;

		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Display the widget title if one was input (before and after defined by themes). */
		/* If show title was selected, display the widget title. */
		if ( $title && $show_title )
			echo $before_title . $title . $after_title;

		/* After widget (defined by themes). */
		echo $after_widget;
	}

	/**
	 * Update the widget settings.
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags for title and name to remove HTML (important for text inputs). */
		$instance['title'] = strip_tags( $new_instance['title'] );

		/* No need to strip tags for show_title. */
		$instance['show_title'] = $new_instance['show_title'];

		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'title' => __('', 'example'), 'show_title' => true );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>

		<!-- Show Title? Checkbox -->
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show_title'], true ); ?> id="<?php echo $this->get_field_id( 'show_title' ); ?>" name="<?php echo $this->get_field_name( 'show_title' ); ?>" /> 
			<label for="<?php echo $this->get_field_id( 'show_title' ); ?>"><?php _e('Display Title?', 'example'); ?></label>
		</p>

	<?php
	}
}
