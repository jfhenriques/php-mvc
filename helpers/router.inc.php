<?php

	
	DEFINE('ROUTES_FILE',		CONF_DIR . '/routes.conf.php' );
	DEFINE('MULTIPART_FILE',	HELPERS_DIR . '/multipart.parser.php' );
	DEFINE('NOT_FOUND_PAGE',	PUBLIC_DIR . '/404.html' );
	
	DEFINE('RESPOND_DISABLED',	0x0);
	DEFINE('RESPOND_HTML',	0x1);
	DEFINE('RESPOND_JSON',	0x2);
	DEFINE('RESPOND_OTHER',	0x3);
	
	
	class Router {
		
		private static $instance = null;
		
		private $url = null;
		private $cachedRoutes = null;
		private $exception = null;
		private $controller = null;
		private $controllerAction = null;
		private $controllerFound = false;
		
		private $responseType = RESPOND_DISABLED;

		const ROUTES_KEY = 'routes';
	
		private function __clone() { }
		
		private function __construct()
		{
			$this->url = ( isset( $_REQUEST['z_url' ] ) && strlen( $_REQUEST['z_url' ] ) > 0 ) ? $_REQUEST['z_url' ] : "" ;
			
			$cc = CommonCache::getInstance();
			//$cc_key = //CommonCache::buildVarName('cached', 'routes');

			$cache = $cc->get( self::ROUTES_KEY );

			$lastMod = @filemtime(ROUTES_FILE) ;
			
			if( $cache === false || !is_array( $cache ) ||
				!isset( $cache['version'] ) || $cache['version'] !== $lastMod )
			{
				include_once(ROUTES_FILE);
				
				$cache = array();
				
				$this->buildRoutes( $GLOBALS['routes'] , $lastMod !== false ? $lastMod : 0 , $cache);

				$cc->set( self::ROUTES_KEY, $cache );
				
				//var_dump( $cache );
			}

			$this->cachedRoutes = $cache ;
 
		}
		
		public static function getInstance()
		{
			if( is_null( static::$instance ) )
				static::$instance = new Router();
				
			return static::$instance;
		}
		
		private static function getNext(&$i, $arr)
		{
			for( ; $i < count( $arr ); $i++)
			{
				if( strlen( $arr[$i] ) > 0 )
					return $arr[$i++];
			}
			
			return false;
		}
		
		public static function add_key_to_array( $key, &$arr )
		{
			if( is_array( $arr ) && !is_null( $key ) )
			{
				if( !isset( $arr[$key] ) || !is_array( $arr[$key] ) )
					$arr[$key] = array();
			}
		}
		
		public static function hasNext($i, $arr)
		{
			for( ; $i < count( $arr ); $i++)
			{
				if( strlen( $arr[$i] ) > 0 )
					return $i;
			}
			
			return false;
		}
		
		
		public function responseType()
		{
			return $this->responseType;
		}
		
		public function foundController()
		{
			return $this->controllerFound ;
		}
		

		
		/**
		 *
		 *	Build routes
		 *
		 */

		private function buildRoutes($routes, $version, &$cached)
		{
			
			function checkInArray($v, $arr)
			{
				return is_null($arr) || in_array( $v, $arr ) ;
			}
			function verifyName( $name )
			{
				return str_replace( array('#', ':'), array('', ''), $name );
			}
			
			function insertMethod( $controller, $name, $method, $action, &$output )
			{
				if( strlen( $name ) == 0 || !is_array($output) )
					return;
					
				$key = '#' . strtoupper( $method ) ;

				Router::add_key_to_array( $name, $output );
				Router::add_key_to_array( $key, $output[$name] );
			
				$output[$name][$key] = array( 'c' => strtolower( $controller ),
											  'a' => $action 		);
			}
			
			function processAtom( $arr, $isResource, $controller, $name, &$output )
			{
				if( !is_array( $arr ) )
					$arr = array();			
				
				$controller = verifyName( isset( $arr['controller'] ) ? $arr['controller'] : $controller ) ;
				
				if( $isResource )
				{
					$id = ":${name}";
								
					$only = ( isset( $arr['only'] ) && count( $arr['only'] ) > 0 ) ? $arr['only'] : null ;
					
					Router::add_key_to_array( $name, $output );
					
					if( checkInArray('index', $only) )
						insertMethod($controller, $name, 'get', 'index', $output);
						
					if( checkInArray('create', $only) )
						insertMethod($controller, $name, 'post', 'create', $output);
					
					if( checkInArray('new', $only) )	
						insertMethod($controller, 'new', 'get', 'mnew', $output[$name]);

					if( checkInArray('show', $only) )
						insertMethod($controller, $id, 'get', 'show', $output[$name]);
						
					if( checkInArray('update', $only) )
						insertMethod($controller, $id, 'put', 'update', $output[$name]);

					if( checkInArray('destroy', $only) )
						insertMethod($controller, $id, 'delete', 'destroy', $output[$name]);
						
					if( checkInArray('edit', $only) )
					{
						Router::add_key_to_array( $id, $output[$name] );
							
						insertMethod($controller, 'edit', 'get', 'edit', $output[$name][$id]);
					}
				}
				else
				{
					if( isset( $arr['action'] ) )
					{
						$via = isset( $arr['via'] ) ? $arr['via'] : 'get' ;
					
						insertMethod($controller, $name, $via, $arr['action'], $output);
					}
				}
				
			}
			
			function processResources($arr, &$output)
			{
				if( !is_array( $arr ) )
					return;
				
				foreach( $arr as $key => $val )
				{
					if( $key[0] == ':' )
					{
						$name = verifyName( substr($key, 1) );
						
						processAtom( $val, true, $name, $name, $output );
						
						Router::add_key_to_array( $name, $output );
							
						if( is_array( $val ) && count( $val ) > 0 )
						{
							Router::add_key_to_array( $key, $output[$name] );
								
							processResources( $val, $output[$name][$key] );
						}
					
					}
				
				}	
			}
			

			
			/***************************************************************************************************
			 *	Processa recursivamente o namespace e os seus recursos
			 ***************************************************************************************************/
			 
			function processNamespace( $arr, &$output )
			{
				if( isset( $arr['namespace'] ) && is_array( $arr['namespace'] ) )
				{
					foreach( $arr['namespace'] as $space )
					{
						if( !is_array( $space ) || !isset( $space['name'] ) )
							continue;

						Router::add_key_to_array( $space['name'], $output );
						
						processNamespace( $space, $output[ $space['name'] ] );
					}
				}
				
				if( isset( $arr['resources'] ) && is_array( $arr['resources'] ) )
					processResources( $arr['resources'], $output );
					
			}

			function processMatches( $arr, &$output )
			{
				if( isset( $arr['matches'] ) && is_array( $arr['matches'] ) )
				{
					foreach( $arr['matches'] as $rule )
					{
						if( !is_array( $rule ) || !isset( $rule['match'] ) )
							continue;

						$controller = null;
						
						$i = 0;
						$val = null;

						$lastLevel = &$output;
						
						$exp = explode('/', $rule['match'] ) ;

						while( ($next = Router::hasNext( $i, $exp ) ) !== false )
						{
							$i = 1 + $next;
							$val = $exp[$next] ;

							if( is_null( $controller ) )
								$controller = $val;

							Router::add_key_to_array( $val, $lastLevel );

							if( Router::hasNext( $i, $exp ) === false )
								processAtom( $rule, false, $controller, $val, $lastLevel );

							$lastLevel = &$lastLevel[$val] ;
						}

						if( !is_null( $val ) )
							processMatches( $rule, $lastLevel );
					}
				}
			}
			
			
			function recursiveArrayClean( &$arr )
			{
				if( !is_array( $arr ) )
					return;
				
				
				foreach( $arr as $k => $v )
				{
					if( is_null( $v ) )
						unset( $arr[ $k ] );
						
					else
					{
						if( is_array( $arr[ $k ] ) )
						{
							if( count( $arr[ $k ] ) > 0 )
								recursiveArrayClean( $arr[ $k ] );
								
							if( count( $arr[ $k ] ) == 0 )
								unset( $arr[ $k ] );
						}
					}
				}
			}
			

			
			/***************************************************************************************************
			 *	Inícia o processo de análise
			 ***************************************************************************************************/
			
			if( !is_array($cached) )
				$cached = array();

				
			$cached['rules'] = array();
			$cached['version'] = $version;
			$cached['root'] = ( isset($routes['root']) && is_string( $routes['root'] ) ) ? $routes['root'] : null;
			
			// Process Namespace / resources
			processNamespace( $routes, $cached['rules'] );

			// Process Matches
			processMatches( $routes, $cached['rules'] );

			// Clean possible empty array tails
			recursiveArrayClean( $cached );
			
		}




		private function is_root( $url )
		{
			return strlen( str_replace(array('/', ' ', "\t"), '', $url) ) == 0 ;
		}
		private function is_default_root_valid($where)
		{
			return @(isset( $where['root'] )
						&& is_string( $where['root'] )
						&& ( strlen( $where['root'] ) > 0 )) ;
		}
		
		public function route()
		{
		
			if( $this->is_default_root_valid( $this->cachedRoutes ) && $this->is_root( $this->url ) )
			{
				header('Location: ' . $this->cachedRoutes['root'], true);
			
				return;
			}
			
			
			$i = 0;
			$next = 0;
			$found = false;
			$lastValue = &$this->cachedRoutes['rules'] ;
			
			$exp = explode('/', $this->url) ;

			
			while( ($next = static::hasNext( $i, $exp )) !== false )
			{
				if( !is_array( $lastValue ) )
					break;
				
				$i = 1 + $next;
				$val = $exp[$next];
				
				// Get format
				if( static::hasNext( $i, $exp ) === false )
				{
					$formatExp = explode( '.', $val , 2 );
					$val = $formatExp[0];
					
					$_REQUEST['z_format'] = strtolower( ( count( $formatExp ) > 1 ) ? $formatExp[1] : "html" ) ;
					
					switch( $_REQUEST['z_format'] )
					{
						case 'html':
							$this->responseType = RESPOND_HTML;
							break;
							
						case 'json':
							$this->responseType = RESPOND_JSON;
							break;
							
						default:
							$this->responseType = RESPOND_OTHER;
							break;
					}
				}
				
				$found = false;
				
				// Existe a chave definida
				if( isset( $lastValue[$val] ) )
				{
					$lastValue = &$lastValue[$val];
					$found = true;
				}
				
				// Em alternativa, se existir uma variável, atribui-se o valor
				// à sua primeira ocurrência
				else
				{	
					foreach( $lastValue as $k => $v )
					{
						if($k[0] == ':')
						{
							$lastValue = &$lastValue[$k];
							$found = true;
							
							$_REQUEST[substr($k, 1)] = $val ;
							
							break;
						}
					}
				}
				
				// Se não foi encontrada nenhuma ocorrência
				// então não existe mais alternativas
				if( !$found )
					break;
			}
			
			if( $found )
			{
				$key = '#' . strtoupper( $_SERVER['REQUEST_METHOD'] );
				
				if( isset( $lastValue[ $key ] ) && is_array( $lastValue[ $key ] ) )
				{
					$act = $lastValue[ $key ] ;
					if( isset( $act['a'] ) && isset( $act['c'] ) )
					{
						if( $key === "#PUT" || $key === "#DELETE" )
						{
							require_once(MULTIPART_FILE);

							parse_raw_http_request();
						}

						$this->controller = $act['c'];
						$this->controllerAction = $act['a'];
						$this->method = $_SERVER['REQUEST_METHOD'];
						
						$this->controllerFound = true;
						
					}
				}
			}
			
			if( $this->controllerFound )
				$this->loadController( $this->controller, $this->controllerAction );
				
			else
			{
				header("HTTP/1.0 404 Not Found");
				header('Content-type: text/html; charset=utf-8', true);
				
				include_once( NOT_FOUND_PAGE );
			}
			
			return $this->controllerFound ;
		}
		
		private function loadController( $controller, $action )
		{
			$className = ucfirst($controller) . "Controller";

			$instance = new $className();
			
			if( !method_exists( $instance , $action) )
				throw new Exception("Method '${action}' does not exits in class '${className}'.");
				
			else
				$instance->$action();
		
		}
		
		public function getControllerName()
		{
			return $this->controller;
		}
	
	}
