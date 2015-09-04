#!/usr/bin/env php
<?php
/** 
 * Dump currently configured websites.
 * @package wordpress-selfservice
 */

/** 
 * parse arguments - from http://php.net/manual/en/features.commandline.php
 * @return array of args
 */
function arguments($argv) {
	$_ARG = array();
	foreach ($argv as $arg) {
		if (ereg('--([^=]+)=(.*)',$arg,$reg)) {
			$_ARG[$reg[1]] = $reg[2];
		} elseif(ereg('-([a-zA-Z0-9])',$arg,$reg)) {
			$_ARG[$reg[1]] = 'true';
		}
	}
	return $_ARG;
}

$args = arguments( $argv );

$WP_PATH = isset( $args['path'] ) ? $args['path'] : __DIR__;

if ( ! file_exists( $WP_PATH.'/wp-config.php' ) ) 
	die( 'No WordPress installation found at '.$WP_PATH.' (argument --path=...)'."\n" );
include( $WP_PATH.'/wp-config.php' );

echo( 'Using WordPress installation at '.ABSPATH."\n" );

// my plugin.
include( dirname(__DIR__).'/plugins/selfservice/autoload.php' );

$manager = new WPSSManager();
$response = $manager->get_websites();
echo( 'Found '.count( $response['websites'] ).' websites'."\n");

echo( json_encode( $response, JSON_PRETTY_PRINT|JSON_FORCE_OBJECT ) );

