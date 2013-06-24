<?php


	call_user_func(function()
	{

		/**********************************************************************************
		 *	Error treatment
		 **********************************************************************************/

		error_reporting( E_ALL | E_STRICT );
		
		date_default_timezone_set('Europe/Lisbon');
		

		//ini_set('display_errors', DEVELOPMENT_ENVIRONMENT !== false);
		ini_set('display_errors', true);


		function my_exception_handler($exception)
		{
			require_once( HELPERS_DIR .'/print_error.inc.php' );
			
			error_log( $exception );
			dumpException( $exception );
			
			exit(1);
		}
		set_exception_handler("my_exception_handler");
		
		set_error_handler(function($errno, $errstr, $errfile, $errline ) {
			
			$exc = new ErrorException($errstr, $errno, 0, $errfile, $errline);

			if ( (ini_get('error_reporting') & $errno) !== 0 )
				my_exception_handler( $exc );

			else
				error_log($exc);
		});

		register_shutdown_function(function() {
			$lErr = error_get_last();

			if( DEVELOPMENT_ENVIRONMENT === true && isset($GLOBALS['init_time']) )
			{
				$took = 1000 * (microtime(true) - $GLOBALS['init_time']);
				header("Execution-time: " . $took);
			}
			
			if ( !is_null( $lErr ) && ( $lErr['type'] & ( E_ERROR | E_USER_ERROR | E_PARSE ) ) !== 0 )
			{
				$exc = new ErrorException($lErr['message'], $lErr['type'], 0, $lErr['file'], $lErr['line']);
				my_exception_handler( $exc );
			}
		});

		ini_set('log_errors', 1);
		ini_set('ignore_repeated_errors', 1);
		ini_set('error_log', TMP_DIR . '/error.log.txt' );
		
		
		/**********************************************************************************
		 *	Common memcached
		 **********************************************************************************/	
		
		class CommonCache {
			
			static private $instance = null;
			private $TYPE = COMMON_CACHE_SET_MODE;
			
			private $mc = null;
			
			private function __clone() { }
			private function __construct()
			{
				$apc_avail   = extension_loaded('apc') && ini_get('apc.enabled') ;
				$memc_avail  = class_exists("Memcache", false) ;
				$memcd_avail = class_exists("Memcached", false) ;
				
				if( COMMON_CACHE_SET_MODE === COMMON_CACHE_AUTO )
					$this->TYPE = ( $apc_avail ? COMMON_CACHE_APC :
										( ( $memcd_avail || $memc_avail ) ?
													COMMON_CACHE_MEMCACHED :
													COMMON_CACHE_DISABLED ) );
				
				if( $this->TYPE === COMMON_CACHE_MEMCACHED )
				{
					if( $memcd_avail )
						$this->mc = new Memcached();

					elseif( $memc_avail  )
						$this->mc = new Memcache();
						
					if( is_null( $this->mc ) )
					{
						$this->TYPE = COMMON_CACHE_DISABLED ;
						
						throw new Exception("No Memcache(d) found in your php installation");
					}
					
					$this->mc->addServer(MEMCACHED_SERVER_ADDR, MEMCACHED_SERVER_PORT);
				}
				elseif( $this->TYPE === COMMON_CACHE_APC )
				{
					if( !$apc_avail )
						$this->TYPE = COMMON_CACHE_DISABLED ;
				}
				
				if( COMMON_CACHE_FORCE_FLUSH )
				{
					switch( $this->TYPE )
					{
						case COMMON_CACHE_APC:
							apc_clear_cache('user');
							apc_clear_cache('opcode');
							break;
							
						case COMMON_CACHE_MEMCACHED:
							$this->mc->flush();
							break;
					}
				}
				
				
			}
			
			public static function getInstance()
			{
				if( is_null( self::$instance ) )
					self::$instance = new CommonCache();
					
				return self::$instance;
			}

			public static function buildVarName($var_pref, $var)
			{
				return "${var_pref}.${var}";
			}
			
			public function getMemcached()
			{
				return $this->mc;
			}
			
			public function get($var)
			{
				$var0 = COMMON_CACHE_VAR_PREFIX . ".{$var}" ;

				switch( $this->TYPE )
				{
					case COMMON_CACHE_APC:
						return apc_fetch( $var0 );
						
					case COMMON_CACHE_MEMCACHED:
						return $this->mc->get( $var0 ) ;
						
					default:
						return false;
				}
			}
			
			public function delete($var)
			{
				$var0 = COMMON_CACHE_VAR_PREFIX . ".{$var}" ;

				switch( $this->TYPE )
				{
					case COMMON_CACHE_APC:
						return apc_delete( $var0 );
						
					case COMMON_CACHE_MEMCACHED:
						return $this->mc->delete( $var0 ) ;
						
					default:
						return false;
				}
			}
			
			/*public function get($var)
			{
				$st = microtime( true );
				$v = $this->_get($var);
				print( 'Time: ' . (1000*(microtime(true)-$st)) . " ms\n<br>" );
				return $v;
			}*/
			public function set($var, $val)
			{
				$var0 = COMMON_CACHE_VAR_PREFIX . ".{$var}" ;
				
				switch( $this->TYPE )
				{
					case COMMON_CACHE_APC:
						return apc_store( $var0, $val );
						
					case COMMON_CACHE_MEMCACHED:
						return $this->mc->set($var0, $val) ;
						
					default:
						return false;
				}
			}
		
		}
		
		/**********************************************************************************
		 *	Class autoloader
		 **********************************************************************************/
		
		class AutoLoader {
			
			static private $instance = null;
			const NAMES_KEY = "names";
			const PLUGINS_KEY = "plugins";
			
			private $cachedNames = array();
			private $cachedPlugins = array();
			private $cc = null;
			
			private $class_search_path = array( array( 'name' => 'model',
													   'path' => MODELS_DIR  ),
												array( 'name' => 'controller',
													   'path' => CONTROLLERS_DIR,
													   'incName' => true ),
												array( 'name' => 'plugin',
													   'path' => PLUGINS_DIR,
													   'incName' => true,
													   'isPlugin' => true ) );
			
			private function __clone() { }
			private function __construct()
			{
				$this->cc = CommonCache::getInstance();

				$this->cachedNames = $this->cc->get( self::NAMES_KEY );
				$this->cachedPlugins = $this->cc->get( self::PLUGINS_KEY );

				if(    $this->cachedNames === false || !is_array( $this->cachedNames )
					|| $this->cachedPlugins === false || !is_array( $this->cachedPlugins ) )
				{
					$this->getLists($this->cachedNames, $this->cachedPlugins);

					$this->cacheArrays();
				}
			}
			
			public static function getInstance()
			{
				if( is_null( static::$instance ) )
					static::$instance = new AutoLoader();
					
				return static::$instance;
			}

			public function getCachedNames()
			{
				return $this->cachedNames;
			}
			public function getCachedPlugins()
			{
				return $this->cachedPlugins;
			}
			
			private function cacheArrays()
			{
				$this->cc->set( self::NAMES_KEY , $this->cachedNames );
				$this->cc->set( self::PLUGINS_KEY , $this->cachedPlugins );
			}
			private function getLists( &$output, &$plugins )
			{
				$output = array();
				$plugins = array();
				
				foreach( $this->class_search_path as $arr )
				{
				
					if( !is_array( $arr ) || !isset( $arr['name'] ) || !isset( $arr['path'] ) )
						continue;

					$incName = ( isset( $arr['incName'] ) && $arr['incName'] === true );
					$isPlugin = ( isset( $arr['isPlugin'] ) && $arr['isPlugin'] === true );
					
					$name = strtolower( $arr['name'] );
					$classType = ucfirst( $name );
					$dir = "{$arr['path']}/";
					
					if( $handle = @opendir( $dir ) )
					{
						while ( false !== ($entry = readdir($handle)) )
						{
							if ( $entry[0] != '.' )
							{
								$exp = explode('.', $entry, 3);
								
								if( count( $exp ) == 3 && strtolower($exp[1]) == $name )
								{
									$key = ucfirst( $exp[0] ) . ( $incName ? $classType : '' ) ;
									$file = $dir . $entry ;
									
									$output[$key] = $file;

									if( $isPlugin )
										$plugins[] = $key;
								}	
							}
						}
						@closedir($handle);
					}
				}

				return $output;
			}
			
			public function loadClass($name)
			{
				$loaded = false;

				if( isset( $this->cachedNames[$name] ) )
				{
					$file = $this->cachedNames[$name] ;
					if( !is_readable( $file ) || !is_file( $file ) )
					{
						unset( $this->cachedNames[$name] );
						$this->cacheArrays();
					}
					else
					{
						include_once( $file );
						
						$loaded = class_exists( $name ) ;
					}
				}
				
				if( !$loaded )
					throw new Exception("Class '$name' not found in autoload list!");
			}
		
		}
		
		$GLOBALS['_class_autoloader_'] = AutoLoader::getInstance();

		spl_autoload_register(function($className) {
		
			$GLOBALS['_class_autoloader_']->loadClass( $className );
			
		});

	});
