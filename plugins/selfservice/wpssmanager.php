<?php
/**
 * WPSSManager
 * @package wordpress-selfservice
 * @author Chris Greenhalgh
 */

if ( ! defined( 'ABSPATH' ) ) {
	throw new Exception( 'Access error' );
}

require_once( ABSPATH.'/wp-includes/query.php' );

define( WPSS_EXPORT_VERSION, '1.0' );

/**
 * Wordpress selfservice manager class
 */
class WPSSManager
{
	/**
	 * Get current websites target state
	 * @return map of maps
	 */
	public function get_websites() {
		$websites = array();
		$qargs = array(
			'post_type' => 'wpss_site',
			'post_status' => 'any',
		);
		$query = new WP_Query( $qargs );
		while ( $query->have_posts() ) {
			$post = $query->next_post();
			$id = strval( $post->ID );
			$website = array(
				'id' => $id,
				'status' => $post->post_status,
				'title' => $post->post_title,
				'description' => $post->post_content,
				'modified' => $post->post_modified_gmt,
				'created' => $post->post_date_gmt,
				'name' => $post->post_name, // Slug.
				'errors' => array(),
			);
			$author = get_userdata( $post->post_author );
			if ( false === $author ) {
				$website['errors'][] = 'Could not find author: '.$post->post_author;
			} else {
				$website['author_login'] = $author->user_login;
				$website['author_email'] = $author->user_email;
				$website['author_password'] = $author->user_pass;
				$website['author_id'] = $author->ID;
				$website['author_display_name'] = $author->display_name;
				$website['author_registered'] = $author->user_registered;
			}

			$websites[ $id ] = $website;
		}

		$response = array(
			'_version' => WPSS_EXPORT_VERSION,
		);
		$response['websites'] = $websites;
		return $response;
	}
	/**
	 * Get information about current website(s) as pillar data
	 * suitable for specified minion.
	 * TODO: which minion?!
	 * @param string $minion_id id of minion to return pillar data for.
	 * @return pillar data structure
	 */
	function get_pillar( $minion_id ) {
	
	//debug_halt("here!");
	
		$qargs = array(
			'post_type' => 'wpss_site',
			'post_status' => 'any',
		);
		$query = new WP_Query( $qargs );
		$apachesites = array();
		$wpsssites = array();
		while ( $query->have_posts() ) {
			$post = $query->next_post();
			$id = strval( $post->ID );
			// TODO: servername?
			$servername = '127.0.0.1';
			// TODO real port vs external?
			$port = ':8080';
			// TODO: type option?
			$type = 'wordpress';
			$locations = array();
			if ( array_key_exists( $servername, $apachesites ) ) {
				$locations = $apachesites[ $servername ]['locations'];
			}
			$urlpath = '/'.$post->post_name;
			// TODO: duplicates/clashes??
			$directory = '/srv/selfservice/'.$id;
			if ( 'publish' === $post->post_status ) {
				$available = true;
				$defaultMessage = 'Available';
			} else {
				$available = false;
				$defaultMessage = 'Site not available ('.$post->post_status.')';
			}
			$author = get_userdata( $post->post_author );
			if ( false === $author ) {
				if ( $available ) {
					$defaultMessage = 'Site not available (could not find author '.$post->post_author.')';
				}
				$available = false;
			}

			$locations[ $urlpath ] = array(
				'DocumentRoot' => $directory,
				'available' => $available,
				'defaultMessage' => $defaultMessage,
				'id' => $id,
			);
			$apachesites[ $servername ] = array(
				'locations' => $locations,
			);
			$url = 'http://'.$servername.$port.$urlpath;
			$wpsssites[ $directory ] = array(
				'id' => $id,
				'type' => $type,
				'admin_password_hash' => $author->user_pass,
				'title' => $post->post_title,
				'description' => $post->post_content,
				'url' => $url,
				'status' => $post->post_status,
				
				'template' => wpss_get_selected_template_for_website($id),
			);
		}
		$pillar = array(
			'apache' => array(
				'sites' => $apachesites,
			),
			'selfservice' => array(
				'sites' => $wpsssites,
			),
		);
		return $pillar;
	}
}




function wpss_get_selected_template_for_website($postID) {

//debug_halt ("getting selected template for posts with type wpss_site" );

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

return $selected_template_names[0];	

}



function debug_halt ($data)
{
echo '<pre>';
print($data);
echo '</pre>';
exit;
}