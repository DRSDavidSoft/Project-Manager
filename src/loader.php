<?php
	
	# Define some required paths
	$ROOT_DIR = realpath( dirname(__FILE__) );
	if ( empty($ROOT_DIR) ) die ("Required files not found?");

	# Where is dADroid.php located
	$core = $ROOT_DIR . "/core/*.php";
	defined ('DEBUG') or define ('DEBUG', 1);
	
	# Make sure we're in the right directory
	chdir(dirname(realpath(__FILE__)));
	
	// TODO: also load subdirectories
	foreach ( array_map('realpath', glob($core)) as $node ) {
		if ( empty($node) || is_dir($node) ) continue;
		else require_once $node;
	}
	
	if ( !defined('APP_NAME') ) {
		@header ("Content-Type: text/plain");
		die ("Could not start the application.");
	}
	
?>