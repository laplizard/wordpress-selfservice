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
}
