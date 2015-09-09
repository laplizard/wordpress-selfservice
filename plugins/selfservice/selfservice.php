<?php
/**
Plugin Name: WordPress SelfService
Description: Links to a suitably configured SaltStack installation to allow users to create and manage their own dedicated WordPress instances.
Version:     0.2
Author:      Chris Greenhalgh
Author URI:  http://www.cs.nott.ac.uk/~cmg
License:     AGPLv3 or later
License URI: http://www.gnu.org/licenses/agpl-3.0.en.html
@package wpss
 */

/*
Copyright (c) 2015, The University of Nottingham
*/

defined( 'ABSPATH' ) or die( 'This is a plugin' );

add_action( 'init', 'wpss_create_post_types' );
/**
 * Register the app post type.
 */
function wpss_create_post_types() {
	register_post_type( 'wpss_site',
		array(
			'labels' => array(
				'name' => __( 'Website' ),
				'singular_name' => __( 'Website' ),
				'add_new_item' => __( 'Add New Website' ),
				'edit_item' => __( 'Edit Website' ),
				'new_item' => __( 'New Website' ),
				'view_item' => __( 'View Website Info' ),
				'search_items' => __( 'Search Websites' ),
				'not_found' => __( 'No Website found' ),
				'not_found_in_trash' => __( 'No Website found in Trash' ),
				'all_items' => __( 'All Websites' ),
			),
			'description' => __( 'WordPress SelfService Website' ),
			'public' => true,
			'has_archive' => true,
			'supports' => array( 'title', 'editor', 'author', 'revisions', 'comments', 'thumbnail' ),
			'menu_icon' => 'dashicons-palmtree',
		)
	);
}

/**
 * Handle site post delete (from trash - meta should still exist)
 * @param int $postid Post id.
 */
function wpss_on_before_delete_post( $postid ) {
	$post = get_post( $post_id );
	if ( 'wpss_site' != $post->post_type ) {
		return;
	}
	// TODO: event?
}
add_action( 'before_delete_post', 'wpss_on_before_delete_post' );

/**
 * Handle site post delete (any cause - meta gone)
 * @param int $postid Post id.
 */
function wpss_on_delete_post( $postid ) {
	$post = get_post( $postid );
	if ( null == $post ) {
		return;
	}
	if ( 'wpss_site' != $post->post_type ) {
		return;
	}
	$data = array(
		'instance' => $postid,
		'status' => 'deleted',
	);
	wpss_send_event( $data );
}
add_action( 'delete_post', 'wpss_on_delete_post' );

/**
 * Handle site post add or update
 * @param int $postid Post id.
 */
function wpss_on_save_post( $postid ) {
	// Note: post_type asserted through specific action.
	$post = get_post( $postid );
	$data = array(
		'instance' => $postid,
		'status' => $post->post_status,
		'type' => 'wordpress',
	);
	wpss_send_event( $data );
}
/**
 * Send event to salt master.
 * @param Array $data Event data.
 */
function wpss_send_event( $data ) {
	$output = array();
	$res = 0;
	exec( 'sudo /usr/bin/salt-call event.send selfservice/www \''.json_encode( $data, JSON_HEX_APOS ).'\'', $output, $res );
	if ( 0 != $res ) {
		error_log( 'selfservice event.send failed (exit code '.$res.'): '.$output );
	}
}
add_action( 'save_post_wpss_site', 'wpss_on_save_post' );
