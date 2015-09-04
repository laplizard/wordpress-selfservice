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
		if (ereg('--([^=]+)=?(.*)',$arg,$reg)) {
			$_ARG[$reg[1]] = $reg[2];
		} elseif(ereg('-([a-zA-Z0-9])',$arg,$reg)) {
			$_ARG[$reg[1]] = 'true';
		}
	}
	return $_ARG;
}

$args = arguments( $argv );

$debug = array_key_exists( 'debug', $args );

$WP_PATH = isset( $args['path'] ) ? $args['path'] : __DIR__;

if ( ! file_exists( $WP_PATH.'/wp-config.php' ) ) 
	die( 'No WordPress installation found at '.$WP_PATH.' (argument --path=...)'."\n" );
include( $WP_PATH.'/wp-config.php' );

if ( $debug ) {
	echo( 'Using WordPress installation at '.ABSPATH."\n" );
}

// my plugin.
include( dirname(__DIR__).'/plugins/selfservice/autoload.php' );

// TODO: minion_id?
$minion_id = '*';
$manager = new WPSSManager();
$response = $manager->get_pillar( $minion_id );

echo( json_encode( $response, JSON_FORCE_OBJECT | ($debug ? JSON_PRETTY_PRINT : 0) ) );

