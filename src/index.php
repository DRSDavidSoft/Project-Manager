<?php
	
	# Application Name and Version
	define ('APP_NAME', 'Project Manager');
	define ('VERSION',  '0.3.0b2-dev');
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
			
				$name = $args['page']; // array_shift($args);
				// +d($name, $args);
				
				if ( is_dir( $dir = __DIR__ . '/pages/' . $name . '/' ) ) {
					
					$name = $args['id'];
					$args['id'] = $args['subpage'];
					unset($args['subpage']);
					
					if ( is_file( $file = $dir . $name . '.php' ) ) require $file;
					else list($success, $msg, $result) = [ false, 'Page not found', basename($file) ];
					
				}
				
				else 
				{
					if ( is_file( $file = __DIR__ . '/pages/' . $name . '.php' ) ) require $file;
					else list($success, $msg, $result) = [ false, 'Page not found', basename($file) ];
				}
				
				if ( empty($page) ) list($success, $msg, $result) = [ false, 'Page data empty (report to David@Refoua.me)', basename($file) ];
				else { exit(); }
			
				return [$success, $msg, $result];
			},
		'action' => function($args) {
				global $parameters, $work_space;
				
				# Hold page data
				$page = [];
			
				$name = $args['page']; // array_shift($args);
				// +d($name, $args);
				
				if ( realpath( $file = __DIR__ . '/actions/' . $name . '.php' ) ) require $file;
				else list($success, $msg, $result) = [ false, 'Action not found', basename($file) ];
			
				return [$success, $msg, $result];
			}
	];

	$argNames = 'page:id:subpage';
	
	require realpath( $ROOT_DIR . '/core/Request.php' );
	return;
	
?>
