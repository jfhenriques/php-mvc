<?php

	/* 
	 *	Must always be set to false on prodution environments
	 */
	DEFINE('DEVELOPMENT_ENVIRONMENT', false );


	/* 
	 *	Where to retreive the routes from
	 */
	DEFINE('ROUTE_EXTRACT', isset( $_SERVER['REDIRECT_PHP_MVC_ROUTE'] ) ?
									$_SERVER['REDIRECT_PHP_MVC_ROUTE']
									: null );
	
	
	/*
	 *	PDO configuration
	 */
	DEFINE('PDO_DATABASE', 'mysql:host=127.0.0.1;dbname=!!DB_NAME_HERE!!;charset=UTF-8' );
	DEFINE('PDO_USERNAME', '!!DB_USER_HERE!!');
	DEFINE('PDO_PASSWORD', '!!DB_PASS_HERE!!');


	/*
	 *	Use php session management mechanism
	 *
	 *	session_start() must be set in the init of index.php
	 */
	DEFINE( 'USE_PHP_SESSIONS', true );
	
	
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
	 *	Forces memcache(d) or APC to ALWAYS flush their cache before being used.
	 *	This is only usefull for development purposes, and setting this to true
	 *	on production environments may lead to catastrophic events.
	 *	Lets say you using memcached for cache, and using it too for php session handling,
	 *	flushing the whole cache will make all other applications' clients to loose their session data.
	 *	The imaginary scenarios that can come out because of flushing the cache are endless.
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


	/*
	 *	Wether or not to check the last modification of the ROUTES file.
	 *	If disabled, you need to manually flush your server cache every 
	 *	you change this file and deploy it to your server.
	 */
	DEFINE( 'MTIME_ROUTES_FILE', true );


	/*
	 *	If set to true, all your cache will be flushed when the ROUTES file change.
	 *	Usefull for making your application be aware of new controllers/plugins
	 *	that have been added to your application and you don't want to flush manually.
	 *
	 *	Requires the flag 'MTIME_ROUTES_FILE' set to true
	 *	
	 *	If using git/svn/etc, and you save your ROUTES file without changing anything only to update the mtime,
	 *	your file will not be sent to the repo, and when you pull the changes on the production
	 *	environment the application will not be aware that a flush is needed. You may want to
	 *	had a comment with some kind of version number on the routes file, and increase it when needed
	 *	before commiting to the repo.
	 *	The ROUTES file was choosen for mtime check, as you will need to update the routes file when adding
	 *	a new controller.
	 *
	 *	This does not flush the whole cache as in 'COMMON_CACHE_FORCE_FLUSH', only deletes internally used
	 *	variables.
	 *	If you're using cached queries or any other kind of variable set with CommonCache they will remain untouched.
	 */
	DEFINE( 'FLUSH_CACHE_ON_ROUTES_CHANGE', true );


	/*
	 *	Use phpless: http://leafo.net/lessphp
	 *	
	 *	You can set to true to use the destributed version of phpless or set it to the full path
	 *	of your own version. Setting it to false disables it.
	 *
	 *	Its recommended that you use the javascript version of less in development environments,
	 *	and set this to true only in production.
	 */
	DEFINE( 'USE_PHPLESS', false );


	/*
	 *	Use the builtin less mechanism (provided by phpless) to compile each less file and concat in a single css file.
	 *
	 *	The resulting file will only be compiled if the mtime of the ROUTES file has changed (needs 'MTIME_ROUTES_FILE')
	 *	or the resulting css file is deleted. The ROUTES file was choosen, excluding the need to add yet another mtime check
	 *	to the overall performance, as probably it will be changed when you send your updates do the production environment.
	 *
	 *	As the framework is designed to be the most efficient possible, the use of less in a production
	 *	environment is highly discouraged. For instance, for each less file, aditional computation needs
	 *	to be made on the client browser. A way arround is pre-compililing each less file in its corresponding css.
	 *	For added performance boost, its a good practi to concat them all in a single css file.
	 *
	 *	The less files will be searched on 'less' directory, located at the root of your project.
	 *
	 *	You can provide each less file, separated by comma.
	 */
	DEFINE( 'LESS_FILES', '' );


	/*
	 *	If 'USE_PHPLESS' is true and a list of files is provided,
	 *	you can specify the name of the resulting compiled file, created in the public dir.
	 */
	DEFINE( 'RESULTING_CSS', 'styles.css' );


	/*
	 *	Always recompile less files in development enviornoment
	 */
	DEFINE( 'DEV_ALWAYS_RECOMPILE_LESS', false );
