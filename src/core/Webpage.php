<?php
	
	/**
	 * File: Webpage.php
	 * Author: David@Refoua.me
	 * Version: 0.1.1
	 */
	

	class Webpage 
	{
		
		protected $doc;
		
		public function __construct( $file ) {
			if ( empty( $file = realpath($file) ) )
				throw new Exception('Template file does not exist.'); else
			if ( empty( $template = file_get_contents( $file ) ) )
				throw new Exception('Template file can not be empty.'); else
			{
				$template = preg_replace( '|[\r\n]+|i', "\n", $template );
				
				$this->doc = new DOMDocument('1.0');
				$this->doc->preserveWhiteSpace = false;
				$this->doc->loadHTML( $template );
				
				$root = $this->loadElement( $this->doc, 'html' );
				
				$head = $this->loadElement( $root, 'head' );
				$body = $this->loadElement( $root, 'body' );
				$title = $this->loadElement( $head, 'title' );
			}
		}
		
		private function loadElement( $parent, $tagname ) {
			
			$list = $parent->getElementsByTagName($tagname);
			if ( $list->length === 0 ) {
				$element = $parent->appendChild( $this->doc->createElement($tagname) );
			} else {
				$element = $list->item(0);
			}
			
			return $element;
			
		}
		
		public $sortEnabled = true;
		
		private function sortHead() {
			
			if ( empty($sortEnabled) ) return;
			
			$head = $this->loadElement( $this->doc, 'head' ); $nodes = [];
			while ($head->hasChildNodes()) $nodes []= $head->removeChild($head->firstChild);
			
			function elScore( $el, $score = 0 ) {
				
				if ( $el instanceof DOMElement ) { // and not DOMComment
					
					if ( $el->hasAttribute('charset') == 'title' )  $score += 1000 - 10;
					if ( $el->getAttribute('name') == 'viewport' )  $score += 1000 - 20;
					if ( stripos( trim($el->getAttribute('http-equiv')), trim('X-UA-Compatible') ) === 0 )  $score += 1000 - 30;
					
					if ( $el->tagName == 'title' )  $score += 2000 - 10;
					if ( $el->tagName == 'base' )   $score += 2000 - 20;
					if ( $el->tagName == 'meta' )   $score += 2000 - 30;
					if ( $el->tagName == 'link' )   $score += 2000 - 40;
					
					
					if ( $el->tagName == 'script' ) $score += 1000 - 80;
					if ( $el->tagName == 'style' )  $score += 1000 - 90;
					
				}
				
				return $score;
			}
			
			usort( $nodes, function($a, $b) { return elScore($b) - elScore($a); });
			foreach( $nodes as $node ) $head->appendChild( $node );
			
		}
		
		public function setTitle( $str ) {
			$str = trim($str);
			$list = $this->doc->getElementsByTagName('title');
			if ( $list->length === 1 ) {
				$list->item(0)->nodeValue = htmlentities($str);
			}
		}
		
		public function addScript( $url, $type = 'text/javascript', $defer = false, $async = false ) {
			
			$url = trim($url);
			
			$head = $this->loadElement( $this->doc, 'head' ); $found = false;
			foreach ( $head->getElementsByTagName('script') as $node )
				if ( (trim( $node->getAttribute('src') )) == $url ) { $found = true; break; }
			
			if ( empty($found) ) $node = $head->appendChild( $this->doc->createElement('script') );
			
			$node->setAttribute( 'src', $url );
			$node->setAttribute( 'type', $type );
			
			if ( !empty($defer) ) $node->setAttribute( 'defer', 'defer' );
			if ( !empty($async) ) $node->setAttribute( 'async', 'async' );
		
		}
		
		public function addStylesheet( $url, $rel = 'stylesheet' ) {
			
			$url = trim($url);
			
			$head = $this->loadElement( $this->doc, 'head' ); $found = false;
			foreach ( $head->getElementsByTagName('link') as $node )
				if ( (trim( $node->getAttribute('href') )) == $url ) { $found = true; break; }
			
			if ( empty($found) ) $node = $head->appendChild( $this->doc->createElement('link') );
			
			$node->setAttribute( 'href', $url );
			$node->setAttribute( 'rel', $rel );
		
		}
		
		public function setCharset( $charset ) {
			
			$charset = trim($charset);
			
			$head = $this->loadElement( $this->doc, 'head' ); $found = false;
			foreach ( $head->getElementsByTagName('meta') as $node )
				if ( $node->hasAttribute('charset') ) { $found = true; break; }
			
			if ( empty($found) ) $node = $head->appendChild( $this->doc->createElement('meta') );
			
			$node->setAttribute( 'charset', $charset );
		}
		
		public function setMeta( $name, $content ) {
			
			$name = strtolower( trim($name) );
			$content = trim($content);
			$found = false;
			
			$head = $this->loadElement( $this->doc, 'head' );
			foreach ( $head->getElementsByTagName('meta') as $node ) {
				if ( strtolower(trim( $node->getAttribute('name') )) == $name ) {
					$node->setAttribute( 'content', $content );
					$found = true; break;
				}
			}
			
			if ( !$found ) {
				$node = $head->appendChild( $this->doc->createElement('meta') );
				$node->setAttribute( 'name', $name );
				$node->setAttribute( 'content', $content );
			}
			
		}
		
		public function setBase( $url ) {
			
			$url = trim($url);
			$found = false;
			
			$head = $this->loadElement( $this->doc, 'head' );
			foreach ( $head->getElementsByTagName('base') as $node ) {
				$node->setAttribute( 'href', $url );
				$found = true; break;
			}
			
			if ( !$found ) {
				$node = $head->appendChild( $this->doc->createElement('base') );
				$node->setAttribute( 'href', $url );
			}
			
		}
		
		public function getSection( $element, $minify = false ) {
			$this->sortHead();
			$this->doc->formatOutput = true;
			
			$obj = $this->doc->getElementsByTagName($element)[0];
			$html = $obj->ownerDocument->saveHTML($obj);
			
			if ( $minify ) {
				$search = [ '/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s' ];
				$replace = [ '>', '<', '\\1' ];
				$html = preg_replace($search, $replace, $html);
			}
			
			return $html;
		}
		
		public function loadBody( $html ) {
			
			$frag = $this->doc->createDocumentFragment();
			$frag->appendXML($html);

			$body = $this->doc->getElementsByTagName('body');
			if ( !empty($body) && 0 < $body->length ) {
				$el = $body->item(0);
				$el->appendChild($frag);
			}
		
		}
		
		// build - render - generate - save - get - output - create
		public function generateHTML( $minify = false ) {
			$this->sortHead();
			$this->doc->formatOutput = true;
			$html = $this->doc->saveHTML();
			
			if ( $minify ) {
				$search = [ '/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s' ];
				$replace = [ '>', '<', '\\1' ];
				$html = preg_replace($search, $replace, $html);
			}
			
			return $html;
		}
		
	}
	
	
	//================================================
	// -------- Sandboxing New Functions Area --------
	//================================================
	
	if ( basename($_SERVER['PHP_SELF']) == basename(__FILE__) ) {
		
		header("Content-Type: text/plain"); // Required for the Demo instea of <pre> and htmlentities();
		$document = new Webpage( dirname(__FILE__) . '/../.work_space/template.txt' );
		$document->setCharset("utf-8");
		$document->setTitle("New Page Example");
		
		$document->addScript( 'https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js' );
		$document->addStylesheet( 'https://fonts.googleapis.com/css?family=Roboto' );
		$document->setMeta( 'viewport', 'width=device-width, initial-scale=1.0' );
		$document->setMeta( 'theme-color', '#000000' );
		$document->setMeta( 'author', 'David Refoua' );
		
		echo $document->generateHTML( false );
		
	}
	
	// TODO: use https://github.com/wasinger/html-pretty-min/blob/master/PrettyMin.php
	// TODO: use http://www.devnetwork.net/viewtopic.php?f=50&t=83337
	
/*
usort( $nodes, function($a, $b) {
	return  elScore($b) - elScore($a);
	$scores = [ elScore($a), elScore($b) ];
	return $scores[0] == $scores[1] ? 0 : $scores[0] < $scores [1];
});
*/
	
?>
