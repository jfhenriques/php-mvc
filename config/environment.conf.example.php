<?php

	/* 
	 *	Must always be set to false on prodution environments
	 */
	DEFINE('DEVELOPMENT_ENVIRONMENT', false);
	
	
	/*
	 *	PDO configuration
	 */
	DEFINE('PDO_DATABASE', 'mysql:host=127.0.0.1;dbname=!!DB_NAME_HERE!!;charset=UTF-8' );
	DEFINE('PDO_USERNAME', '!!DB_USER_HERE!!');
	DEFINE('PDO_PASSWORD', '!!DB_PASS_HERE!!');
	
	
	/*
	 *	Which type of cache to use
	 *
	 *	COMMON_CACHE_DISABLED	: Should never be used
	 *  COMMON_CACHE_AUTO		: Tries first APC and then Memcache(d) 
	 *  COMMON_CACHE_APC		: Force PHP APC
	 *  COMMON_CACHE_MEMCACHED	: Force Memcache(d)
	 *
	 */
	DEFINE( 'COMMON_CACHE_SET_MODE'	, COMMON_CACHE_APC );
	
	
	/*
	 *	Memcache(d) server configuration
	 */
	DEFINE( 'MEMCACHED_SERVER_ADDR'	,	'127.0.0.1' );
	DEFINE( 'MEMCACHED_SERVER_PORT'	,	11211 );
	
	
	/*
	 *	Forces flush of memcache(d) or APC. Usefull for test purposes
	 */
	DEFINE( 'COMMON_CACHE_FORCE_FLUSH'	,	false );

	
	/*
	 *	This variable should be set to something unique, so that
	 * 	cached variables/arrays/etc can be distinguished from other applications
	 * 	who are using the cache
	 */
	 
	DEFINE( 'COMMON_CACHE_VAR_PREFIX'	,	'cc' );
	
	
	/*
	 *	Validity of authentication tokens until they die
	 */
	DEFINE( 'TOKEN_VALIDITY', 604800 ); // 3600*24*7


	/*
	 *	E-mail from address, used by Controller::sendCustomMail(...)
	 */
	DEFINE( 'MAIL_FROM_ADDRESS', 'noreply@localhost' );


	/*
	 *	Leave empy if the website is in the root (namespaces don't count)
	 *	If not empty, BASE_URI must start and do not end with a slash
	 */
	DEFINE( 'BASE_URI', '' );


	/*
	 *	If there is an alternative path to access static content, set this to true
	 *
	 *		If the framework is being used as an API (for ex: Android),
	 *		you must set this to true and define BASE_STATIC_URI
	 *		or yon't be able to access content like images.
	 */
	DEFINE( 'USE_STATIC_URI', true );


	/*
	 *	The base uri or schema+host+uri to access the content
	 */
	DEFINE( 'BASE_STATIC_URI', "http://{$_SERVER['SERVER_NAME']}/" . BASE_URI );

