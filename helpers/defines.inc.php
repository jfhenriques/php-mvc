<?php


	DEFINE('CONF_DIR', 			MVC_INSTANCE_DIR . DS . 'config' . DS );
	DEFINE('CONTROLLERS_DIR',	MVC_INSTANCE_DIR . DS . 'controllers' . DS );
	DEFINE('MODELS_DIR',		MVC_INSTANCE_DIR . DS . 'models' . DS );
	DEFINE('VIEWS_DIR',			MVC_INSTANCE_DIR . DS . 'views' . DS );
	DEFINE('PLUGINS_DIR',		MVC_INSTANCE_DIR . DS . 'plugins' . DS );
	DEFINE('PUBLIC_DIR',		MVC_INSTANCE_DIR . DS . 'public' . DS );
	DEFINE('TMP_DIR',			MVC_INSTANCE_DIR . DS . 'tmp' . DS );

	DEFINE('ROUTES_FILE',		CONF_DIR . 'routes.conf.php' );


	/*
	 *	Description of the types of cache available
	 */
	DEFINE( 'COMMON_CACHE_DISABLED'	, 0 );	/* Should never be used */
	DEFINE( 'COMMON_CACHE_AUTO'		, 1 );	/* Automatic choose between APC e Memcache(d) */
	DEFINE( 'COMMON_CACHE_APC'		, 2 );	/* Force PHP APC */
	DEFINE( 'COMMON_CACHE_MEMCACHED', 3 );	/* Force Memcache(d) */


	DEFINE( 'CACHE_VERSION_VAR', 'r.version' );
