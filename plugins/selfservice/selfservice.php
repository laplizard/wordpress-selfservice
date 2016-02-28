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

// Steve add class for radio buttons in WP UI:
require_once plugin_dir_path( __FILE__ ) . '/includes/taxonomy-single-term/class.taxonomy-single-term.php';

// Steve add: add template names here
$template_names = array("googleAnalytics", "project_1_setup", "project_2_setup", "project_3_setup");

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

// ################################################  Steve additions ###################################################  */

// QUESTION: can template be retrospectively updated for a WP website instance? New template selected? As with other title, description etc..
// Or, once it has been selected in 'Add New' website, just hide template taxonomy from user?


function add__website_templates_taxonomy() {

global $template_names;

	register_taxonomy('website_templates', 'wpss_site', array(
		// Hierarchical taxonomy (like categories)
		'hierarchical' => true,
		// This array of options controls the labels displayed in the WordPress Admin UI
		'labels' => array(
			'name' => _x( 'Website Template', 'taxonomy general name' ),
			'singular_name' => _x( 'Website-Template', 'taxonomy singular name' ),
			'search_items' =>  __( 'Search Website-Templates' ),
			'all_items' => __( 'All Website-Templates' ),
			'parent_item' => __( 'Parent Website-Template' ),
			'parent_item_colon' => __( 'Parent Website-Template:' ),
			'edit_item' => __( 'Edit Website-Template' ),
			'update_item' => __( 'Update Website-Template' ),
			'add_new_item' => __( 'Add New Website-Template' ),
			'new_item_name' => __( 'New RWebsite-Template Name' ),
			'menu_name' => __( 'Website Templates' ),
		),

		// Control the slugs used for this taxonomy
		'rewrite' => array(
			'slug' => 'console', // This controls the base slug that will display before each term
			'with_front' => false, // Don't display the category base before "/locations/"
			'hierarchical' => true // This will allow URL's like "/locations/boston/cambridge/"
		),
	));


// convert to meta box to radio buttons
$custom_tax_mb = new Taxonomy_Single_Term( 'website_templates', array( 'wpss_site' ));
// Makes a selection required.
$custom_tax_mb->set( 'force_selection', true );

	
// if term 'vanilla' doesn't already exist in website_templates taxonomy... add it (then, it doesn't matter if found in templates dir or not)
$term = term_exists('vanilla', 'website_templates');
if ($term == 0 || $term == null) {
wp_insert_term('vanilla',  'website_templates');
}

// Steve: next is where we need to change it to get template names from Salt files in templates dir

 foreach($template_names as $template_name){
 
 // check if template filename already exists as a term in website_templates taxonomy
$term = term_exists($template_name, 'website_templates');
 
 // if term doesn't already exist in website_templates taxonomy
if ($term == 0 || $term == null) {

// insert new template name to taxonomy
wp_insert_term( $template_name,  'website_templates');
 
 } // close if term doesn't exist.
  
 } // close all template names

 
 
 
 }
 
add_action( 'init', 'add__website_templates_taxonomy', 0 );


function debug_halt ($data)
{
echo '<pre>';
print($data);
echo '</pre>';
exit;
}
