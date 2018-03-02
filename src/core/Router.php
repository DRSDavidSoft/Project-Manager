<?php

	/**
	 * File: Router.php
	 * Author: David@Refoua.me
	 * Version: 0.4.9
	 */
	
	class Router {
		
		public $request, $url, $parameters, $modules, $handler;
		
		private static $standardPorts = [80, 443];
		
		public function __construct( $modules = [] ) {
			
			$this->modules = array();
			
			if ( !empty($modules) )
			{
				/** Store passed arguments first */
				$this->loadModules($modules);
			}
			
			/** Store the request information */
			$this->request = new stdClass();
			$this->request->method  = strtoupper($_SERVER['REQUEST_METHOD']);
			$this->request->headers = getallheaders();
			$this->request->body    = file_get_contents('php://input');
			
			/** Process the URL information */
			$serverName = trim(@$_SERVER['SERVER_NAME']); //$_SERVER['HTTP_HOST'];
			$relativeURL = trim(@$_SERVER['REQUEST_URI']);
			$pathInfo = trim(@$_SERVER['PATH_INFO']);
			$findLoc = strlen(trim(@$_SERVER['PATH_INFO'])) == 0 ? FALSE : strpos( trim(urldecode($_SERVER['REQUEST_URI'])), trim(urldecode($_SERVER['PATH_INFO'])) );
			$baseURL = substr(trim($_SERVER['REQUEST_URI']), 0, $findLoc !== FALSE ? $findLoc : strlen($_SERVER['REQUEST_URI']));
			$webDir = preg_replace('@\b' . addslashes(basename($_SERVER['SCRIPT_NAME'])) . '\/?$@iU', '', $baseURL);

			if ( !empty($baseURL) ) $baseURL = rtrim($baseURL, '/') . '/';
			
			if ( empty($_SERVER['REQUEST_SCHEME']) )
				$_SERVER['REQUEST_SCHEME'] = 'http';

			$scheme = strtolower( (
				(@$_SERVER['HTTPS'] == 'on') ||
				(@$_SERVER['REQUEST_SCHEME'] == 'https') ||
				(@$_SERVER['SERVER_PORT'] == '443')
			) ? 'https' : $_SERVER['REQUEST_SCHEME'] );
			
			$port = in_array($port = $_SERVER['SERVER_PORT'], Self::$standardPorts) ? false : intval($port);
			
			$requestParts = array_merge( parse_url($relativeURL), parse_url($pathInfo) );

			/** Store the URL information */
			$this->url = new stdClass();
			$this->url->QUERY_STR = &$requestParts['query'];
			$this->url->PATH_INFO = &$pathInfo;
			$this->url->WWW_DIR  = rtrim($webDir, '/') . '/';
			$this->url->BASE_URL = rtrim($baseURL, '/') . '/';
			$this->url->HOST_URL = rtrim($scheme, '://') . '://' . $serverName . (empty($port) ? '' : ":" . $port) . '/';
			$this->url->FULL_URL = rtrim($this->url->HOST_URL, '/') . $relativeURL;
			$this->url->RELATIVE_URL = $relativeURL;
			$this->url->SSL_USED = ($scheme == 'https') ? true : false;

			$this->url->REFERRER_URL = &$_SERVER['HTTP_REFERER'];
			
			/** Process and store the parameters information */
			$this->parameters = array_merge( $_GET, $_POST ); # Not using `$_REQUEST` because it contains cookies
			
		}
		
		public function getHeader( $headerName ) {
			
			$headers = &$this->request->headers;
			
			$ret = false;
			
			foreach ( $headers as $name=>$value ) {
				if ( strtolower($headerName) == strtolower($name) ) {
					$ret = trim($value);
				}
			}
			
			return $ret;
			
		}

		public function isHeader( $headerName, $reqValue, $valDelim = false ) {
			
			$reqValue = trim($reqValue);
			$headerValue = trim($this->getHeader($headerName));

			$matches = false;
			
			if ( !empty($headerValue) && !empty($reqValue) ) {
				if ($valDelim == false) { if ( strtolower($headerValue) == strtolower($reqValue) ) $matches = true; }
				else foreach ( array_map('trim', explode( trim($valDelim), strtolower($headerValue) )) as $valPart ) {
					if ( strtolower($reqValue) == $valPart ) $matches = true;
				}
			}
			
			return $matches;
			
		}
		
		private function attachModule( $method, $cb ) {

			if ( !is_string($method) )
				throw new Exception("Provided module path is not a string.", 500);

			//if ( strlen($method) > 255 )
			//throw new Exception("Provided module path exceeds the maximum 255 characters.", 500);
			
			if ( is_callable($cb) ) {

				/** Get a path array for `method/example/sub` style string */
				$path = array_filter(explode('/', trim($method, '/')));

				/** Set the current position in the modules list */
				$element = &$this->modules;

				/** Traverse the modules and fill the callback in the right position */
				$depth = count($path);
				foreach ($path as $node) {
					$depth--;
					if ( !empty($node) ) {

						/** Special rule if the current element is a callback */
						if ( !is_array($element) ) {
							$element = [
								'/' => $element
							];
						}

						$element = &$element[strtolower($node)]; // case-insensetive
						if ( empty($element) ) $element = ($depth > 0) ? [] : $cb;
					}
				}

			}

			else throw new Exception("Provided module `$method` has provided an incorrect callback.", 500);

		}
		
		private function buildPath( $path ) {
			
			foreach ($path as &$item) $item = trim($item, '/');
			$address = implode('/', array_filter($path));
			
			/** Builds a `method/example/sub` style string from an array */
			return $address;

		}
		
		private function traverseAddress( $address, $ignoreExts = false ) {

			$cb = false;
			$argVals = [];
			$callpath  = '';

			/** Get a path for `method/example/sub` style string */
			$path = array_filter(explode('/', trim($address, '/')));

			/** Set the current position in the modules list */
			$element = &$this->modules;

			foreach ( $path as $node ) {

				// removes extensions (e.g. replaces result.json, image.png to result, image)
				if ( ($ignoreExts == true) && empty($element[strtolower($node)]) )
					$node = preg_replace('|\.\w+$|iU', '', trim($node)); 

				if ( empty($node) ) continue; else
				if ( is_callable($element) ) $argVals []= $node; else
				if ( empty($element[strtolower($node)]) ) break; else
				{
					$element = $element[strtolower($node)];
					$callpath .= '/' . trim($node);
					$node = '';
				}

			}

			$callpath = trim( $callpath, '/' );

			/** Special rule for default index route */
			if ( !is_callable($element) && isset($element['/']) && is_callable($element['/']) ) {
				$element = $element['/'];
			}

			/** Process the callback */
			if ( is_callable($element) ) $cb = $element;
			
			/** Handle an invalid call */
			else {
				preg_match( '@^/*' . addslashes($callpath) .'(?<method>\/[^\/]+)(?<arguments>(?:\/[^\/]+)*)$@iU', $this->buildPath($path), $matches );
				$basepath = !empty($node) ? preg_replace( "|\/{$node}$|iU", '', $callpath ) : trim($callpath, '/');
				if ( !empty($matches['method']) ) $callpath .= strtolower($matches['method']);
				if ( !empty($matches['arguments']) ) $argVals = array_filter(explode('/', trim($matches['arguments'], '/')));
				$cb = false;

				// d($element);
				throw new Exception("Invalid method called. " . ( !empty($node) ? "The specified method `$node` does not exist in `$basepath`." : "The specified `$callpath` is not callable." ), ( !empty($node) ? 404 : 403 ) );
			}
			
			/** Get argument names for the callback */
			$argNames = array_map( function( $parameter ) { return $parameter->name; }, (new ReflectionFunction($cb))->getParameters() ); /* TODO: $parameter->isOptional */ 

			/** Parse arguments into associated array */
			$arguments = [];
			foreach ($argNames as $name) {
				if ( empty($argVals) ) break; else
				$arguments[$name] = array_shift($argVals);
			}
			
			/** Return the result as an object */
			$match = new stdClass();
			$match->cb = $cb;
			$match->arguments = $arguments;
			$match->callpath  = $callpath;

			return $match;

		}
		
		private function flattenModules( $modules, $prefix = '' ) {
			
			$result = [];
			
			foreach ($modules as $name=>$data) {
				
				/** Determine the type of value */
				if ( is_callable($data) ) $result[$this->buildPath([$prefix, $name])] = $data; else
				if ( is_array($data) ) foreach ($data as $key=>$value) {
					if ( !is_string($key) ) throw new Exception("Provided module name has an incorrect type for `$name`.", 500);
					else {
						if ( is_callable($value) ) $result[$this->buildPath([$prefix, $name, $key])] = $value;
						else $result = array_merge($result, $this->flattenModules($value, $this->buildPath([$name, $key])));
					}
				}
				else throw new Exception("Provided argument `modules` has an incorrect value for `$name`.", 500);
				
			}
			
			return $result;
			
		}

		public function loadModules( $modules ) {

			if ( !is_array($modules) )
				throw new Exception('Provided argument `modules` must be an array.', 500);
			else
			{
				
				$values = $this->flattenModules($modules);
				
				/** Process provided modules */
				foreach ($values as $address=>$module) {
					// Load the module
					$this->attachModule( $address, $module );
				}

			}
			
			return count($this->modules);
			
		}
		
		private $indexModule = '';
		private $indexModuleArgs = [];
		
		public function setIndex( $moduleName = '', $moduleArgs = [], $shouldRedirect = false ) {
			
			// TODO: if shouldRedirect == true, then on reaching http://example.ir/, redirect to http://example.ir/{moduleName}/ and optionally implode($moduleArgs) or sth, example: http://example.ir/page/demo/11
			
			if ( empty($moduleName) || !is_string($moduleName) ) {
				throw new Exception('Provided argument `moduleName` must be a valid string.', 500);
			}
			
			else
			
			if ( !is_array($moduleArgs) ) {
				throw new Exception('Provided argument `moduleArgs` must be a valid array.', 500);
			}
			
			else
			
			{
				
				// TODO: better variables
				$this->indexModule = trim($moduleName);
				$this->indexModuleArgs = $moduleArgs;
				
			}
			
			
		}

		private $errorModuleData = [];

		// TODO: change name
		public function setError( $moduleName = '', $moduleArgs = [] ) {
			
			if ( empty($moduleName) || !is_string($moduleName) ) {
				throw new Exception('Provided argument `moduleName` must be a valid string.', 500);
			}
			
			else
			
			if ( !is_array($moduleArgs) ) {
				throw new Exception('Provided argument `moduleArgs` must be a valid array.', 500);
			}
			
			else
			
			{
				$this->errorModuleData = [trim($moduleName), $moduleArgs];
			}
			
			
		}

		// TODO: ?
		public function setHandler( $callback ) {
			
			if ( is_callable($callback) )
				$this->handler = $callback;

			else throw new Exception("Provided module should be callable.", 500);

		}

		private function getDefaultHandler() {

			$module = new stdClass();

			$module->arguments = [];
			$module->callpath = trim($this->url->PATH_INFO, '/');

			if ( is_callable($this->handler) ) {
				$module->cb = $this->handler;
				return $module;
			}

			else return false;

		}

		
		// TODO:
		public function setIndex_cb( $callback ) {
			// on reaching `/`, execute $callback
		}

		public function matchRequest( $normalizeURL = true ) {
			
			/** Check if we should load the index module */
			if ( $this->url->PATH_INFO === "" && !empty($this->indexModule) ) {
				$this->url->PATH_INFO = $this->indexModule;
				
				$this->parameters = array_merge( $this->parameters, $this->indexModuleArgs );
				$normalizeURL = false;
			}

			// TODO: 
			if ( $default = $this->getDefaultHandler() ) $module = $default;
			else try {
				/** Find the current module */
				$module = $this->traverseAddress(urldecode($this->url->PATH_INFO));
			}

			catch (Exception $e) {

				$errModule = &$this->errorModuleData[0];
				if ( !empty($errModule) && is_string($errModule) ) {

					$this->parameters = [
						'callpath'      => $this->url->PATH_INFO,
						'parameters'    => $this->parameters,
						'error_message' => $e->getMessage(),
						'error_code'    => $e->getCode()
					];

					if ( is_array($this->errorModuleData[1]) ) {
						$this->parameters = array_merge( $this->parameters, $this->errorModuleData[1] );
					}

					$module = $this->traverseAddress( $errModule );

					/** If we're loading an error page, we wouldn't want to redirect to it */
					/** TODO: unless an ASP.NET style redirect_on_error is set, which I personally think is ugly af */
					$normalizeURL = false;

					if ( $e->getCode() >= 400 && $e->getCode() <= 500 ) {
						// This may be an HTTP error code, sent it to the browser
						http_response_code( $e->getCode() );
					}

				}

				else throw $e;

			}
			
			//if ( empty($module) && $default = $this->getDefaultHandler() )
			//	$module = $default;
			
			$redirect = '';
			
			/** Normalize URL and Redirect */
			if ( $normalizeURL == true )
			{
				$callpath = trim($module->callpath, '/');
				
				$currenturl = ltrim($this->url->PATH_INFO, '/') . ( empty($this->url->QUERY_STR) ? ( substr($this->url->PATH_INFO, -1) === '?' ? '?' : '' ) : '?' . $this->url->QUERY_STR );
				$actualurl = $callpath . ( empty($module->arguments) ? '/' : '/' . implode('/', array_map('urlencode', $module->arguments)) )
					. ( empty($_GET) ? '' : '?' . trim(preg_replace('@\b\=(?:\&|$)@iU', '&', http_build_query ($_GET)), '&') );
					
				if ( !empty($currenturl) && urldecode($currenturl) != urldecode($actualurl) ) {
					$redirect = $actualurl;
				}

				/** Replace excessive slashes with just one */
				$baseURL = preg_replace( '|\/+|', '/', $this->url->BASE_URL);

				if ( $baseURL !==  $this->url->BASE_URL ) {
					$this->url->BASE_URL = $baseURL;
					if ( empty($redirect) ) $redirect = $actualurl; // To make sure it redirects
				}

			}
			
			/** Check if we need to redirect to a URL */
			if ( !empty($redirect) )
			{
				/** Can not redirect in case of content already being sent to the browser */
				if ( headers_sent() )
					throw new Exception("Can not send a redirect output, because response body output has been already started.", 502);
				
				/** Process redirects */
				else
				{
					header ( 'Content-Type: text/plain' );
					header ( "Location: {$this->url->BASE_URL}$redirect", true, 301 );
					exit( "This API has moved to: \n{$this->url->BASE_URL}$redirect" );
				}
			}
			
			/*******************************************
			 ***   CAUTION:   EXPERIMENTAL SECTION   ***
			 *******************************************/
			 
			// d( $module->cb, $arguments );
			
			/** Includes all of the arguments and parameters */
			//$module->arguments = $arguments;
			$module->parameters = $this->parameters;

			/** Append HTTP parameters to the arguments */
			$module->arguments = array_merge( $module->arguments, $this->parameters ); // TODO: remove this
			
			/** Bind the arguments to the callback */
			$cb = $module->cb->bindTo( $module );
			
			/** Call module's callback */
			$ret = call_user_func_array( $cb, $module->arguments );
			
			//if ( !is_callable($module->cb) || !empty($redirect) ) list($success, $message, $code) = array(false, 'Module not found.', 404);
			//else {
			//	@list($success, $message, $content) = array_pad((array)$module($parameters), 3, null);
			//}

			// TODO: matched module info
			//d( $module->callpath, $arguments );
			
			return $ret;

		}
		
		
		/** ----------------------------------------------- */
		/** Debug purposes only */
		public function getRequest( $type = false ) {
			return json_decode( json_encode( $this->request), $type );
		}
		
		public function getURL( $type = false ) {
			return json_decode( json_encode( $this->url), $type );
		}
		
		public function getParameters( $type = false ) {
			return json_decode( json_encode( $this->parameters), $type );
		}

		public function getModules( $type = false ) {
			return json_decode( json_encode( $this->modules), $type );
		}
		/** ----------------------------------------------- */
		
		
	}
	
	/* TODO:
		$auth = ['username'=>&$_SERVER['PHP_AUTH_USER'], 'password'=>&$_SERVER['PHP_AUTH_PW']];
		if (!empty($auth['username'])) $parameters['username'] = $auth['username'];
	*/
	
	//================================================
	// -------- Sandboxing New Functions Area --------
	//================================================
	
	if ( basename($_SERVER['PHP_SELF']) == basename(__FILE__) ) {
		
		header("Content-Type: text/plain"); // Required for the Demo instead of <pre> and htmlentities();
		
		$request = new Router(  );
		
		$host = $request->getHeader( 'host' );
		//var_dump($host);
		
		$info = $request->getRequest( true );
		//var_dump($info);
		
		$info = $request->getURL( true );
		//var_dump($info);

		$info = $this->getModules( true );
		//var_dumpd($info);
		
		$info = $request->getParameters( true );
		//var_dump($info);
		
	}
	
	return;


	/**
	 * EXAMPLE valid `$modules` array
	 */
	
	$modules = [
		'tree/cat1' => function($arg1, $arg2) {
			}, 

		'tree/cat2' => [
		
			'list1/' => [
			
				'/sub1/' =>
				function($arg1, $arg2) {
				}, 
				
				'/sub2/' =>
				function($arg1, $arg2) {
				}, 
				
			]
			
		],

		'tree/cat3' => [ '*/sub3' =>
			function($arg1, $arg2) {
			}
		],

		'tree' => [ 'cat4' =>
			function($arg1, $arg2) {
			}
		],
		
		
		'tree/main' => [
			'sub01/' => [
				'/method_a/' =>
				function($parg_01, $parg_02 = '') {
					d($this, func_get_args(), "Shit it works!!");
				}
			],
		],

		'test' => function($args, $testicle = 'balls') {
				global $parameters, $work_space;
				$success = true;
				$message = 'Service works. API version: ' . __version__;
				$content = [  ];
				
				return [$success, $message, $content];
			},

	];
	
	$request = new Router($modules);
	$request->matchRequest();
	
	exit;
