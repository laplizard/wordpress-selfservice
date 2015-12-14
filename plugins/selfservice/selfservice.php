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
	
/*	
	$data = array(
		'instance' => $postid,
		'status' => $post->post_status,
		'type' => 'wordpress',
	);
*/	

/* ###################### Steve bit: start ###############  */

$currentTemplateName = wpss_get_selected_template_for_new_website($postid);

$pluginsResponseArray = wpss_parse_active_plugin_names_from_template($currentTemplateName);

	$data = array(
		'instance' => $postid,
		'status' => $post->post_status,
		'template' => $currentTemplateName,
		'plugins' => $pluginsResponseArray,
		'theme'  => 'TO DO',
		'widgets'  => 'TO DO',
	);
	
	
// TO TEST WORKING UP TO HERE: uncomment next line... then go to WP admin console, make changes to ticked template and then click 'UPDATE'
// ... will display name of first plugin installed for the currently ticked template. 

//debug_halt($data['plugins'][0]);

/* ###################### Steve bit: end ###############  */
	
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

// TO DO: make current selection interface 'single select' / radion buttons - so, can't choose more than one template.
// TO: if user doesn't select a template, plugin assumes 'vanilla' - need to update taxonomy tick status to reflect this (acually think already done?).
// QUESTION: can template be retrospectively updated for a WP website instance? New template selected? As with other title, description etc..
// Or, once it has been selected in 'Add New' website, just hide template taxonomy from user?

// Set path to templates dir
//$root = realpath($_SERVER["DOCUMENT_ROOT"]);
//$pathToTemplates = './*.*';
//$pathToTemplates = $root.'/srv/wordpress-selfservice/templates/*.*';
//$pathToTemplates = '../../../../../../../srv/wordpress-selfservice/templates/*.*';
//$pathToTemplates = '../../../../../../../srv/wordpress-selfservice/templates/*.json';
$pathToTemplates = '../../../../../../../srv/wordpress-selfservice/templates/';
$fileExtensionForTemplates = '*.json';

/*
Above path ownership and permissions are set as follows:
chown -R www-data:www-data /srv/wordpress-selfservice/templates
chmod 744 /srv/wordpress-selfservice/templates
chmod 644 /srv/wordpress-selfservice/templates/*.*
*/ 

function add__website_templates_taxonomy() {

global $pathToTemplates, $fileExtensionForTemplates;

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

// if term 'vanilla' doesn't already exist in website_templates taxonomy... add it (then, it doesn't matter if found in templates dir or not)
$term = term_exists('vanilla', 'website_templates');
if ($term == 0 || $term == null) {
wp_insert_term('vanilla',  'website_templates');
}

// get all filenames in templates dir
foreach(glob($pathToTemplates.$fileExtensionForTemplates) as $filename){

// strip file extension from discovered template filename
$path_parts = pathinfo($filename);

// check if template filename already exists as a term in website_templates taxonomy
$term = term_exists($path_parts['filename'], 'website_templates');

// if term doesn't already exist in website_templates taxonomy
if ($term == 0 || $term == null) {

// insert new template name to taxonomy
wp_insert_term($path_parts['filename'],  'website_templates');
 
 } // close if term doesn't exist.

 }	// close all filenames in templates dir
}

add_action( 'init', 'add__website_templates_taxonomy', 0 );



function wpss_get_selected_template_for_new_website($postID) {

//  get the term (template) associated with this website
$terms = get_the_terms( $postID, 'website_templates' );

// 'if no terms assigned to this website, add add term 'vanilla' for this website
if ( !$terms)
{
//debug_halt("no terms");

// need the id for the term 'vanilla' by name
$templateTerm = get_term_by('name', 'vanilla', 'website_templates');

// add 'vanilla' template to this website
wp_set_post_terms( $postID, $templateTerm->term_id, 'website_templates');

// get the terms again, now we've added 'vanilla'
$terms = get_the_terms( $postID, 'website_templates' );

}
				
// check again for terms - should be 'vanilla' if not set in WP				
if ( $terms && ! is_wp_error( $terms ) ) 
{

	$selected_template_names = array();

	// should only be one term...but...
	foreach ( $terms as $term ) {
		$selected_template_names[] = $term->name;
	}
}

//debug_halt($selected_template_names[0]);

// In case something has gone awry...
if(!$selected_template_names[0]) { $selected_template_names[0]='error'; }	

//debug_halt("It's now: ".$selected_template_names[0]);

return $selected_template_names[0];	

}


// parse the json template looking for 'active-plugins'

function wpss_parse_active_plugin_names_from_template ( $template_name ) {

global $pathToTemplates;

$json = json_decode(file_get_contents($pathToTemplates.$template_name.".json"), true);

$response = $json['options']['active_plugins'];

// Explode into string array, using quotation mark as delimiter
$responseArray = explode("\"", $response);

// Cull elements zero and last
$unwantedZeroElement = array_shift($responseArray);
$unwantedLastElement = array_pop($responseArray);

// Unset even elements in array 
foreach($responseArray as $key => $value) if($key&1) unset($responseArray[$key]);

// Renumber array element keys to get rid of those that were unset
$responseArray = array_values($responseArray);

// return array of plugin names for this template
return $responseArray;

}





function debug_halt ($data)
{
echo '<pre>';
print($data);
echo '</pre>';
exit;
}
