<?php

	/**
	 * File: Request.php
	 * Author: David@Refoua.me
	 * Version: 0.4
	 */
	
	// AUTHOR'S NOTE:
	//    I really apologize about this mess!
	//    This file will be re-written in a 
	//    beautiful, OOP, well-commented and
	//    nicely formatted version in the future.
	 
	if ( empty($modules) ) return;
	else {

	define('__version__', 'API:0.4-alpha');
	chdir(dirname(__FILE__));
	
	function utf8ize($d) {
		if (is_array($d)) 
			foreach ($d as $k => $v) 
				$d[$k] = utf8ize($v);

		 else if(is_object($d))
			foreach ($d as $k => $v) 
				$d->$k = utf8ize($v);
				
		 else if(is_int($d)||is_bool($d))
			return ($d);

		 else if(is_string($d))
			return utf8_encode($d);

		return $d;
	}
	
	$findLoc = strlen(trim(@$_SERVER['PATH_INFO'])) == 0 ? FALSE : strpos( trim(urldecode($_SERVER['REQUEST_URI'])), trim(urldecode($_SERVER['PATH_INFO'])) );
	$dir = substr( trim($_SERVER['REQUEST_URI']), 0, $findLoc !== FALSE ? $findLoc : strlen($_SERVER['REQUEST_URI']) );
	if ( !empty($dir) ) $dir = rtrim($dir, '/') . '/';
	define('WWW_DIR', $dir);
	
	$url = trim(@$_SERVER['REQUEST_URI']);
	$uri = trim(@$_SERVER['PATH_INFO']);
	$server = trim(@$_SERVER['SERVER_NAME']); //$_SERVER['HTTP_HOST'];
	$request = array_merge( parse_url($url), parse_url($uri) );
	//$parameters = $_REQUEST;
	$keys = [];
	
	$parameters = array_merge( $_GET, $_POST ); // Do not use $_REQUEST because it contains cookies
	//var_dump($parameters); exit;
	
	$auth = ['username'=>&$_SERVER['PHP_AUTH_USER'], 'password'=>&$_SERVER['PHP_AUTH_PW']];
	if (!empty($auth['username'])) $parameters['username'] = $auth['username'];
	
	global $username, $password, $modules;
	
	if (empty($modules)) $modules = [];
	
	$argNames = explode(':', 'arg:term:info_hash:id:example');
	$modules = array_merge($modules, [
		// Make sure all module names are small-letters
		
	]);
	
	$content = [];
	list($success, $message, $code) = array(false, 'Unspecified error.', 500);
	
	$path = array_filter(explode( '/', trim($request['path'], '/') )); $module = $modules; $callpath = '';
	foreach ( $path as $node ) {
		//$node = preg_replace('|\.\w+$|iU', '', trim($node)); //Removes extensions (e.g. replaces result.json, image.png to result, image)
		if ( empty($node) ) continue; else
		if ( is_callable($module) ) $keys []= $node; else
		if ( empty($module[strtolower($node)]) ) break; else {
			$module = $module[strtolower($node)];
			$callpath .= '/' . $node;
		}
	}
	
	function getArguments( $func ) {
		return array_map( function( $parameter ) { return $parameter->name; },
			(new ReflectionFunction($func))->getParameters() );
	}
	
	//$argNames = getArguments($module);
	//$argNames = !empty($module->parameter) ? array_map(function($node){ return preg_replace('|^\$+|', '', $node); }, $module->parameter) : array();
	
	$i = 0;
	foreach ( $argNames as $node ) if ( !empty($keys[$i]) ) $parameters[$node] = urldecode($keys[$i++]);
	//foreach ( $_REQUEST as $name=>$node ) { $parameters [/*strtolower*/($name)] = $node; }
	//$_REQUEST = array_merge($_REQUEST, $parameters);
	
	if ( empty($callpath) ) $callpath = implode('/', $path);
	$callpath = trim( $callpath, '/' );
	
	$currentpath = ltrim( $request['path'], '/' ) . ( empty($request['query']) ? ( substr($uri, -1) === '?' ? '?' : '' ) : '?' . $request['query'] );
	$actualpath = $callpath . ( empty($keys) ? '/' : '/' . implode('/', $keys) )
		. ( empty($_GET) ? '' : '?' . trim(preg_replace('@\b\=(?:\&|$)@iU', '&', http_build_query ($_GET)), '&') );
		
	$redirect = '';
		
	if ( !empty($currentpath) && $currentpath != $actualpath ) {
		$redirect = $actualpath;
	}

	if ( !is_callable($module) || !empty($redirect) ) list($success, $message, $code) = array(false, 'Module not found.', 404);
	else {
		@list($success, $message, $content) = array_pad((array)$module($parameters), 3, null);
	}
	
	//+d($dir, $redirect, $currentpath, $actualpath );
		
	if ( !empty($redirect) ) {
		header ( 'Content-Type: text/plain' );
		header ( "Location: $dir$redirect", true, 301 );
		exit( "This API has moved to: \n$dir$redirect" );
	}
	
	$success = !empty($success);
	if ( $success == true ) $code = 200;
	else if ( $code < 400 ) $code = 400;
	
	$result = [
		'version' => __version__,
		'request' => array_filter([ $callpath, $parameters ]),
		'response' => [
			$success, $message
		]
	
	];
	
	if ( !empty($content) ) $result['response'] []= $content;
	
	$headers = array (
		// Allow Content to be Loaded from Anywhere
		'Access-Control-Allow-Origin: *',
		'Access-Control-Allow-Methods: POST, GET, OPTIONS, HEAD',
		'Access-Control-Max-Age: 1000',
		
		// Set the headers to prevent Caching
		'Pragma: public', 'Expires: -1',
		'Cache-Control: public, must-revalidate, post-check=0, pre-check=0',
		
		// Set the document type
		'Content-Type: application/json; charset=utf-8',
		
		// Powered-By
		'X-API-Version: ' . __version__
	);
	
	
	global $headers, $result;

	// Set the headers
	foreach ($headers as $line) @header ($line);

	//@header ('Content-Type: application/json', true, $code);
	echo json_encode( utf8ize($result), JSON_PRETTY_PRINT );

	}
	