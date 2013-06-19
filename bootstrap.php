<?php
	
	DEFINE('API_VERSION', 20130619);



	$init_time = microtime(true);
	
	// Forces the output to be redirected to the buffer,
	// being sent only to the client's browser when all php code has executed
	ob_start();


	/*
	 * Some usefull defines
	 */
		
		DEFINE('ROOT', dirname(__FILE__) );

		if( !defined('MVC_INSTANCE_DIR') )
			define('MVC_INSTANCE_DIR', ROOT);

		DEFINE('HELPERS_DIR',		ROOT . '/helpers');


	/*
	 * Include everything needed by the framework
	 */

		require_once( HELPERS_DIR . '/defines.inc.php' );

		require_once( CONF_DIR . '/environment.conf.php' );
		
		require_once( HELPERS_DIR . '/commons.inc.php');
		
		require_once( HELPERS_DIR . '/router.inc.php');
		require_once( HELPERS_DIR . '/template.inc.php');
		require_once( HELPERS_DIR . '/model.inc.php');
		
		require_once( HELPERS_DIR . '/controller.inc.php');
		
		require_once( HELPERS_DIR . '/plugin.inc.php');


	/*
	 * Start the real thing
	 */

		Plugin::loadPlugins();

		$router = Router::getInstance();
		$router->route();
