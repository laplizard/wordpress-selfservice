<?php
/**
Plugin Name: WordPress SelfService
Description: Links to a suitably configured SaltStack installation to allow users to create and manage their own dedicated WordPress instances.
Version:     0.1
Author:      Chris Greenhalgh
Author URI:  http://www.cs.nott.ac.uk/~cmg
License:     AGPLv3 or later
License URI: http://www.gnu.org/licenses/agpl-3.0.en.html
*/
/*
Copyright (c) 2015, The University of Nottingham
*/
defined( 'ABSPATH' ) or die( 'This is a plugin' );

add_action( 'init', 'wpss_create_post_types' );
//Register the app post type
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
                'all_items' => __( 'All Websites' )
            ),
            'description' => __( 'WordPress SelfService Website' ),
            'public' => true,
            'has_archive' => true,
            'supports' => array( 'title', 'editor', 'author', 'revisions', 'comments', 'thumbnail' ),
	    'menu_icon' => 'dashicons-palmtree',
        )
    );
}

