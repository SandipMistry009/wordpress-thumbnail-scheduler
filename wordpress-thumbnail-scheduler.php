<?php

/**
 * Plugin Name:       WordPress Thumbnail Scheduler
 * Plugin URI:        https://sandipmistry.com
 * Description:       Schedule the WordPress Images and Media for future display like scheduule post.
 * Version:           1.0.0
 * Author:            Sandip Mistry
 * Author URI:        https://sandipmistry.com
 * Text Domain:       wordpress-thumbnail-scheduler
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'WORDPRESS_THUMBNAIL_SCHEDULER_VERSION', '1.0.0' );


add_action('load-post.php', 'setup_attachment_fields');

function setup_attachment_fields() {
  $scr = get_current_screen();
  if ( $scr->post_type === 'attachment' ) {
    add_filter("attachment_fields_to_edit", "add_date_to_attachments", null, 2);
  }
}

function add_date_to_attachments( $form_fields, $post = NULL ) {    
  printf('<label for="content"><strong>%s</strong></label>', __('Date'));
  touch_time( 1, 1, 0, 1 );
  return $form_fields;
}

add_filter("attachment_fields_to_save", "save_date_to_attachments");

function save_date_to_attachments( $post ) {
  foreach ( array('mm', 'jj', 'aa', 'hh', 'mn') as $f ) {
    $$f = (int) filter_input(INPUT_POST, $f, FILTER_SANITIZE_NUMBER_INT);
  }
  if ( ! checkdate ( $mm, $jj, $aa ) ) return; // bad data, man
  if ( ($hh < 0 || $hh > 24) ) $hh = 0;
  if ( ($mn < 0 || $mn > 60) ) $mn = 0;
  $ts = mktime($hh, $mn, 0, $mm, $jj, $aa);
  $date = date( 'Y-m-d H:i:s', $ts );
  $date_gmt = date( 'Y-m-d H:i:s', ( $ts + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) );
  $modified = current_time( 'mysql');
  $modified_gmt = current_time( 'mysql', true);
  global $wpdb;
  $data = array(
    'post_date' => $date,
    'post_date_gmt' => $date_gmt,
    'post_modified' => $modified,
    'post_modified_gmt' => $modified_gmt,
  );
   $wpdb->update( $wpdb->posts, $data, array( 'ID' => $post['ID'] ), '%s', '%d' );
}

add_filter( 'posts_where', 'not_future_attachment', 9999, 2 );

function not_future_attachment( $where, $query ) {
  if ( is_admin() ) return $where;
  if ( $query->get('post_type') === 'attachment' ) {
    global $wpdb;
    $where .= $wpdb->prepare(
      " AND ($wpdb->posts.post_date <= %s)", current_time( 'mysql')
    );
  }
  return $where;
}

function post_thumbnail_html_function( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
    
  global $wpdb;
  $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts WHERE ID = ".$post_thumbnail_id." AND post_date <= '".current_time( 'mysql')."'", OBJECT );
  
  if(empty($results)){
    return $new_html;
  }
  else{
    return $html;
  }  
}
add_action( 'post_thumbnail_html', 'post_thumbnail_html_function', 10, 5 );



