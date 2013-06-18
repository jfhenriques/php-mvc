<?php


	DEFINE('CONF_DIR', 			MVC_INSTANCE_DIR . '/config');
	DEFINE('CONTROLLERS_DIR',	MVC_INSTANCE_DIR . '/controllers');
	DEFINE('MODELS_DIR',		MVC_INSTANCE_DIR . '/models');
	DEFINE('VIEWS_DIR',			MVC_INSTANCE_DIR . '/views');
	DEFINE('PLUGINS_DIR',		MVC_INSTANCE_DIR . '/plugins');
	DEFINE('PUBLIC_DIR',		MVC_INSTANCE_DIR . '/public');
	DEFINE('TMP_DIR',			MVC_INSTANCE_DIR . '/tmp');


	/*
	 *	Description of the types of cache available
	 */
	DEFINE( 'COMMON_CACHE_DISABLED'	, 0 );	/* Should never be used */
	DEFINE( 'COMMON_CACHE_AUTO'		, 1 );	/* Automatic choose between APC e Memcache(d) */
	DEFINE( 'COMMON_CACHE_APC'		, 2 );	/* Force PHP APC */
	DEFINE( 'COMMON_CACHE_MEMCACHED', 3 );	/* Force Memcache(d) */
