<?php
	
	DEFINE('API_VERSION', 20130828);



	$init_time = microtime(true);
	
	// Forces the output to be redirected to the buffer,
	// being sent only to the client's browser when all php code has executed
	ob_start();


	/*
	 * Some usefull defines
	 */

		DEFINE('ROOT', __DIR__ );

		defined('MVC_INSTANCE_DIR') or DEFINE('MVC_INSTANCE_DIR', ROOT );
		defined('DS') or define('DS', DIRECTORY_SEPARATOR);

		DEFINE('HELPERS_DIR', ROOT .  DS . 'helpers' . DS );

		

	/*
	 * Include everything needed by the framework
	 */

		require_once( HELPERS_DIR . 'defines.inc.php' );

		require_once( CONF_DIR . 'environment.conf.php' );
		
		require_once( HELPERS_DIR . 'commons.inc.php');

		require_once( HELPERS_DIR . 'router.inc.php');
		require_once( HELPERS_DIR . 'template.inc.php');
		require_once( HELPERS_DIR . 'model.inc.php');
		
		require_once( HELPERS_DIR . 'controller.inc.php');
		
		require_once( HELPERS_DIR  . 'plugin.inc.php');


	/*
	 * Where all the real action happens
	 */

	class Bootstrap
	{

		public static function start()
		{
			
			if( USE_PHPLESS )
			{
				require_once( USE_PHPLESS !== true ? USE_PHPLESS : ( HELPERS_DIR . 'lessc.inc.php' ) );

				Template::compileLessFilesChecked();
			}

			//TODO: Add plugin that loads only when needed
			Plugin::loadPlugins();

			Router::getInstance()->dispatch();

			exit;
		}
	}
