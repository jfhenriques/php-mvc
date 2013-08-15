<?php

	
	DEFINE('MULTIPART_FILE',	HELPERS_DIR . 'multipart.parser.php' );
	DEFINE('NOT_FOUND_PAGE',	PUBLIC_DIR . '404.html' );
	
	DEFINE('RESPOND_NONE',		0x0 );
	DEFINE('RESPOND_HTML',		0x1 );
	DEFINE('RESPOND_JSON',		0x2 );
	DEFINE('RESPOND_OTHER',		0x3 );
	
	
	class Router {
		
		private static $instance = null;
		private static $e_Construct = null;
		
		private $url = null;
		private $cachedRoutes = null;
		private $cachedNamedRoutes = null;
		private $exception = null;
		private $controller = null;
		private $controllerAction = null;
		private $controllerFound = false;


		
		private $responseType = RESPOND_NONE;

		const ROUTES_KEY = 'routes';
		const NAMED_ROUTES_KEY = 'named_routes';
	
		private function __clone() { }
		
		private function __construct()
		{
			$this->url = ( isset( $_REQUEST['z_url' ] ) && strlen( $_REQUEST['z_url' ] ) > 0 ) ? $_REQUEST['z_url' ] : "" ;
			
			$cc = CommonCache::getInstance();

			$cache = false;
			$namedRoutes = false;

			if( !VERSION_HAS_CHANGED )
			{
				$cache = $cc->get( self::ROUTES_KEY );
				$namedRoutes = $cc->get( self::NAMED_ROUTES_KEY );
			}
			
			if( $cache === false || $namedRoutes === false ||
				!is_array( $cache ) || !is_array( $namedRoutes ) )
			{
				include_once( ROUTES_FILE );
				
				$cache = array();
				
				$version = ( CACHE_VERSION === false ? @filemtime( ROUTES_FILE ) : CACHE_VERSION ) ;
				$this->buildRoutes( $GLOBALS['routes'] , $cache, $namedRoutes );
				
				$cc->set( self::ROUTES_KEY, $cache );
				$cc->set( self::NAMED_ROUTES_KEY, $namedRoutes );
				$cc->set( CACHE_VERSION_VAR, $version );
				
				//var_dump( $cache, $namedRoutes, $version );
			}

			$this->cachedRoutes = $cache ;
			$this->cachedNamedRoutes = $namedRoutes ;


			// Process onConstruct Events

			EventStack::execAll( self::$e_Construct );
		}
		
		public static function getInstance()
		{
			if( is_null( self::$instance ) )
				self::$instance = new Router();
				
			return self::$instance;
		}

		public static function registerOnConstruct(callable $e)
		{
			if( is_null( self::$e_Construct ) )
				self::$e_Construct = new EventStack();

			self::$e_Construct->register( $e );
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


		/*public function getPath($named_route)
		{
			$route = null;

			if( is_null( $this->cachedNamedRoutes ) ||
				!isset( $this->cachedNamedRoutes[$named_route] ) )
				return false;

			$base = BASE_URI ;
			$route = "/{$base}{$this->cachedNamedRoutes[$named_route]}";

			$num_args = func_num_args();

			if( $num_args == 2 )
				return str_replace( "%1", func_get_arg(1), $route );

			else
			if( $num_args > 2 )
			{
				$needle = array();
				$replace = array();

				for($i = 1; $i < $num_args; $i++)
				{
					$needle[] = "%{$i}" ;
					$replace[] = func_get_arg( $i );
				}

				return str_replace( $needle, $replace, $route );
			}
			
			return $route;
		}*/

		public function getPath($named_route, $arrIn = array(), $format = 'html')
		{
			$route = null;

			if( is_null( $this->cachedNamedRoutes ) ||
				!isset( $this->cachedNamedRoutes[$named_route] ) )
				return false;

			$format = is_null( $format ) ? '' : ".{$format}";

			$route = "/" . BASE_URI . "{$this->cachedNamedRoutes[$named_route]}{$format}";

			$t_elems = ( is_array( $arrIn ) ? count( $arrIn ) : 0 );

			switch( $t_elems )
			{
				case 0:
					return $route;

				case 1:
					return str_replace( "%1", $arrIn[0], $route ) ;

				case 2:
					return str_replace( array('%1', '%2'), $arrIn, $route );

				case 3:
					return str_replace( array('%1', '%2', '%3'), $arrIn, $route );

				case 4:
					return str_replace( array('%1', '%2', '%3', '%4'), $arrIn, $route );

				/*case 5:
					return str_replace( array('%1', '%2', '%3', '%4', '%5'), $arrIn, $route );*/

				default:

			}

			$needle = array();

			for($i = 1; $i <= $t_elems; $i++)
				$needle[] = "%{$i}" ;

			return str_replace( $needle, $arrIn, $route );
		}

		

		
		/**
		 *
		 *	Build routes
		 *
		 */

		private function buildRoutes($routes, &$cached, &$namedRoutes)
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



			function build_named_route_path($named_resource, $name, $isVar = false)
			{
				$name = $isVar ? "%{$name}": $name ;
				return ( $named_resource === '' ? $name : "{$named_resource}/{$name}" ) ;
			}
			function build_named_route_key($name, $named_key)
			{
				return ( empty( $named_key ) ? "$name" : "{$named_key}_{$name}"  ) ;
			}

			function build_named_route($name, &$named_var_count, &$named_key, &$named_resource)
			{
				if( !empty($name) )
				{
					if( $name[0] === ':' )
					{
						$named_var_count++;
						
						//$named_resource .= "/%{$named_var_count}";
						$named_resource = build_named_route_path($named_resource, $named_var_count, true);
					}
					else
					{
						$named_key = build_named_route_key($name, $named_key);
						$named_resource = build_named_route_path($named_resource, $name);
					}
				}
			}
			
			function processAtom( $arr, $isResource, $controller, $name, &$output, &$named_array, $named_var_count = 0, $named_key = '', $named_resource = '')
			{
				if( !is_array( $arr ) )
					$arr = array();			
				
				$controller = verifyName( isset( $arr['controller'] ) ? $arr['controller'] : $controller ) ;
				
				if( $isResource )
				{
					$id = ":${name}";
								
					$only = ( isset( $arr['only'] ) && count( $arr['only'] ) > 0 ) ? $arr['only'] : null ;
					
					Router::add_key_to_array( $name, $output );

					$hasNormal = false;
					$hasPlus = false;

					$named_var_count++;
					$named_resource = $named_resource ;
					//$named_resource_plus = "{$named_resource}/%{$named_var_count}" ;
					$named_resource_plus = build_named_route_path($named_resource, $named_var_count, true);
					
					if( checkInArray('index', $only) )
					{
						insertMethod($controller, $name, 'get', 'index', $output);
						$hasNormal = true;
					}
					
					if( checkInArray('create', $only) )
					{
						insertMethod($controller, $name, 'post', 'create', $output);
						$hasNormal = true;
					}
					
					if( $hasNormal )
						$named_array[$named_key] = $named_resource;

					if( checkInArray('new', $only) )
					{
						insertMethod($controller, 'new', 'get', 'mnew', $output[$name]);
						$named_array["{$named_key}_new"] = build_named_route_path($named_resource_plus, 'new'); //"{$named_resource_plus}/new";
					}

					if( checkInArray('show', $only) )
					{
						insertMethod($controller, $id, 'get', 'show', $output[$name]);
						$hasPlus = true;
					}
						
					if( checkInArray('update', $only) )
					{
						insertMethod($controller, $id, 'put', 'update', $output[$name]);
						$hasPlus = true;
					}

					if( checkInArray('destroy', $only) )
					{
						insertMethod($controller, $id, 'delete', 'destroy', $output[$name]);
						$hasPlus = true;
					}

					if( $hasPlus )
						$named_array["{$named_key}_"] = $named_resource_plus;
						
					if( checkInArray('edit', $only) )
					{
						Router::add_key_to_array( $id, $output[$name] );
							
						insertMethod($controller, 'edit', 'get', 'edit', $output[$name][$id]);
						$named_array["{$named_key}_edit"] = build_named_route_path($named_resource_plus, 'edit'); //"{$named_resource_plus}/edit";
					}
				}
				else
				{
					if( isset( $arr['action'] ) )
					{
						$via = isset( $arr['via'] ) ? $arr['via'] : 'get' ;
					
						insertMethod($controller, $name, $via, $arr['action'], $output);

						$named_key = ( isset( $arr['as'] ) && $arr['as'] !== '' ) ?
											$arr['as'] :
											build_named_route_key($arr['action'], $named_key) ;

						if( !is_null( $named_key ) && $named_key !== false )
							$named_array[$named_key] = $named_resource; // build_named_route_path($named_resource, $name);
					}
				}
				
			}
			
			function processResources($arr, &$output, &$named_array, $named_var_count = 0, $named_key = '', $named_resource = '' )
			{
				if( !is_array( $arr ) )
					return;

				$named_var_count_plus = $named_var_count + 1 ;
				
				foreach( $arr as $key => $val )
				{
					if( $key[0] == ':' )
					{
						$name = verifyName( substr($key, 1) );

						$tmp_named_key = build_named_route_key($name, $named_key);
						$tmp_named_resource = build_named_route_path( $named_resource, $name );
						//$tmp_named_resource = "{$named_resource}/{$name}";
						
						processAtom( $val, true, $name, $name, $output, $named_array, $named_var_count, $tmp_named_key, $tmp_named_resource );
						
						Router::add_key_to_array( $name, $output );
							
						if( is_array( $val ) && count( $val ) > 0 )
						{
							Router::add_key_to_array( $key, $output[$name] );

							//$tmp_named_resource .= "/%{$named_var_count_plus}";
							$tmp_named_resource = build_named_route_path($tmp_named_resource, $named_var_count_plus, true );
							
								
							processResources( $val, $output[$name][$key], $named_array, $named_var_count_plus, $tmp_named_key, $tmp_named_resource );
						}
					
					}
				
				}	
			}
			

			
			/***************************************************************************************************
			 *	Processa recursivamente o namespace e os seus recursos
			 ***************************************************************************************************/
			 
			function processNamespace( $arr, &$output, &$named_array, $named_key = '', $named_resource = '' )
			{
				if( isset( $arr['namespace'] ) && is_array( $arr['namespace'] ) )
				{
					foreach( $arr['namespace'] as $space )
					{
						if( !is_array( $space ) || !isset( $space['name'] ) )
							continue;

						Router::add_key_to_array( $space['name'], $output );

						$tmp_named_key = build_named_route_key($space['name'], $named_key);
						//$tmp_named_resource = "{$named_resource}/{$space['name']}";
						$tmp_named_resource = build_named_route_path( $named_resource, $space['name'] );
						
						processNamespace( $space, $output[ $space['name'] ], $named_array, $tmp_named_key, $tmp_named_resource );
					}
				}
				
				if( isset( $arr['resources'] ) && is_array( $arr['resources'] ) )
					processResources( $arr['resources'], $output, $named_array, 0, $named_key, $named_resource );
					
			}

			function processMatches( $arr, &$output, &$named_array, $named_var_count = 0, $named_key = '', $named_resource = '' )
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
						$tmp_named_var_count = $named_var_count ;
						$tmp_named_key = $named_key;
						$tmp_named_resource = $named_resource;

						$lastLevel = &$output;
						
						$exp = explode('/', $rule['match'] ) ;

						while( ($next = Router::hasNext( $i, $exp ) ) !== false )
						{
							$i = 1 + $next;
							$val = $exp[$next] ;

							if( is_null( $controller ) )
								$controller = $val;

							Router::add_key_to_array( $val, $lastLevel );

							build_named_route($val, $tmp_named_var_count, $tmp_named_key, $tmp_named_resource );

							if( Router::hasNext( $i, $exp ) === false )
							{
								//echo "$val - $tmp_named_key <br>\n";
								processAtom( $rule, false, $controller, $val, $lastLevel, $named_array, $tmp_named_var_count, $tmp_named_key, $tmp_named_resource );
							}

							$lastLevel = &$lastLevel[$val] ;
						}

						if( !is_null( $val ) )
						{
							//build_named_route($val, $tmp_named_var_count, $tmp_named_key, $tmp_named_resource );
							processMatches( $rule, $lastLevel, $named_array, $tmp_named_var_count, $tmp_named_key, $tmp_named_resource );
						}
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
			$cached['root'] = ( isset($routes['root']) && is_string( $routes['root'] ) ) ? $routes['root'] : null;

			$namedRoutes = array();
			
			// Process Namespace / resources
			processNamespace( $routes, $cached['rules'], $namedRoutes );

			// Process Matches
			processMatches( $routes, $cached['rules'], $namedRoutes );

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
						&& $where['root'] ) ;
		}
		
		public function dispatch()
		{
		
			if( $this->is_root( $this->url ) && $this->is_default_root_valid( $this->cachedRoutes ) )
			{
				header('Location: ' . $this->cachedRoutes['root'], true);
			
				exit;
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

					if( count( $formatExp ) !== 2 )
						$_REQUEST['z_format'] = false;

					else
					{
					
						$_REQUEST['z_format'] = strtolower( $formatExp[1] ) ;
					
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
				$this->generate404();

			exit;
			//return $this->controllerFound ;
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


		public function generate404()
		{
			header("HTTP/1.0 404 Not Found");
			header('Content-type: text/html; charset=utf-8', true);
			
			include_once( NOT_FOUND_PAGE );

			exit;
		}
		
		public function getControllerName()
		{
			return $this->controller;
		}
	
	}
