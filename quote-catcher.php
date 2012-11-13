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

/* Make array of posts that have custom field 'qc_quote'. ( */
$args = array(
	'numberposts' => -1,
	'meta_key' => 'qc_quote',
	'orderby' => 'rand'
);

$qc_posts_with_quote = get_posts( $args );

function quote_catcher() {

	global $qc_posts_with_quote;
	
	/* Get a random key from the array.*/
	$qc_post_id = array_rand( $qc_posts_with_quote );
	
	$id = $qc_posts_with_quote[$qc_post_id]->ID;
	$author_id = $qc_posts_with_quote[$qc_post_id]->post_author;
	$title = $qc_posts_with_quote[$qc_post_id]->post_title;
	$url = get_permalink( $id );

	/* Get custom-field value for: quote, and any other CF values
	for replacing default: Author, Title */
	$qc_quote = get_post_meta( $id, 'qc_quote', true );
	$qc_quoteby = get_post_meta( $id, 'qc_quoteby', true);
	$qc_quotetitle = get_post_meta( $id, 'qc_quotetitle', true);

	$quote_catcher_array = array( $id, $author_id, $title, $url, $qc_quote, $qc_quoteby, $qc_quotetitle ); 

	return $quote_catcher_array;
}

function quote_catcher_html() {
	/* Get array with post and quote-related custom fields */
	$quote_catcher_array = quote_catcher();

	$id = $quote_catcher_array[0];
	$author_id = $quote_catcher_array[1];
	$title = $quote_catcher_array[2];
	$url = $quote_catcher_array[3];
	$qc_quote = $quote_catcher_array[4];
	$qc_quoteby = $quote_catcher_array[5];
	$qc_quotetitle = $quote_catcher_array[6];

	$quote_catcher_html = '';

	return $quote_catcher_html;
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
 * QuoteCatcher Widget class.
 * This class handles the widget's settings, form, display, and update.
 *
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

		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Display the widget title if one was input (before and after defined by themes). */
		/* If show title was selected, display the widget title. */
		if ( $title )
			echo $before_title . $title . $after_title;
/*
		if ( function_exists( 'quote_catcher' ) ) {
			$quote_arr = quote_catcher();
    		print_r( $quote_arr );
 		}
*/

		if ( function_exists( 'quote_catcher' ) ) {
		
		
			$quote_catcher_array = quote_catcher();

			$id = $quote_catcher_array[0];
			$author_id = $quote_catcher_array[1];
			$title = $quote_catcher_array[2];
			$url = $quote_catcher_array[3];
			$quote = $quote_catcher_array[4];
			$quoteby = $quote_catcher_array[5];
			$quotetitle = $quote_catcher_array[6];

			$author_html = '<a href="' . get_author_posts_url( $author_id ) . '" title="Author Posts">' . get_the_author_meta( 'display_name', $author_id ) . '</a>';
			$author = ( ! $quoteby ) ? $author_html : $quoteby;		  
			$title = ( ! $quotetitle ) ? $title : $quotetitle;
?>
<blockquote class="pullquote quote-catcher" style="width:252px;font-size:17px;line-height: 30px;color:#000;border-top:2px #ccc dotted; border-bottom:2px #ccc dotted;" cite="<?php echo $url; ?>">
	<span style="font-size:50px;font-style:normal;font-family:serif; position: relative; top: 18px; line-height: 18px;">&ldquo;</span><?php echo wptexturize( $quote ); ?><span style="font-size:50px;font-style:normal;font-family:serif; position: relative; top: 18px; line-height: 18px;">&rdquo;</span>
	<div style="font-size:0.9em; font-style:normal;">&mdash;<?php echo $author ?>, <b>"<a href="<?php echo $url; ?>" rel="bookmark" title="Permanent Link to <?php echo $title; ?>"><?php echo $title; ?></a>"</b><?php echo $a ?></div> 
</blockquote>
<p><?php print_r( $quote_catcher_array ); ?></p>
<p class="clear"><?php echo get_num_queries(); ?> queries, <?php timer_stop(1); ?> seconds, <?php echo round(memory_get_peak_usage() / 1024 / 1024, 3); ?> MB Peak Memory Used</p>
<?php
		}

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

		return $instance;
	}

	/**
	 * Displays the widget settings controls on the widget panel.
	 * Make use of the get_field_id() and get_field_name() function
	 * when creating your form elements. This handles the confusing stuff.
	 */
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'title' => __('', 'example') );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Widget Title: Text Input -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>

	<?php
	}
}