<?php
	
	# Application Name and Version
	define ('APP_NAME', 'Project Manager');
	define ('VERSION',  '0.3.0b1-dev');
	define ('AUTHOR', 'DRSDavidSoft');
	
	# Include the required files
	$ROOT_DIR = realpath( __DIR__ );
	require_once realpath( $ROOT_DIR . '/loader.php' );
	if ( realpath( $Kint = $ROOT_DIR . '/.work_space/_kint/Kint.class.php' ) ) include $Kint;

	# Make a MySQL Database connection
	$db = dbInit( "mysql:host=localhost;port=3306;charset=utf8", null, 'main', 'letmein' ); // DSN, leave null, username, password
	dbSelect( $db, 'todo_manager', true ); // Database Name
	
	$modules = [
		'test' => function($args) {
				global $parameters, $work_space;
				$success = true;
				$message = 'Service works. API version: ' . __version__;
				$content = [  ];
				
				return [$success, $message, $content];
			},
		'page' => function($args) {
				global $parameters, $work_space;
				
				# Hold page data
				$page = [];
			
				$name = array_shift($args);
				// +d($name, $args);
				
				if ( realpath( $file = __DIR__ . '/pages/' . $name . '.php' ) ) require $file;
				else list($success, $msg, $result) = [ false, 'Page not found', basename($file) ];
				
				if ( empty($page) ) list($success, $msg, $result) = [ false, 'Page data empty (create issue on GitHub)', basename($file) ];
				else { exit(); }
			
				return [$success, $msg, $result];
			}
	];
	
	require realpath( $ROOT_DIR . '/core/Request.php' );
	return;
	
?>