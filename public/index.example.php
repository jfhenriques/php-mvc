<?php

	/*
	 *	If you are going to use sessions, session_start() must be set here.
	 *	To use a custom session name, session_name() must be set before session_start()
	 *
	 *	You can use the builtin session management mechanism, enable it with the flag USE_PHP_SESSIONS in the config
	 *
	 */
	//session_name('_changeme_session');
	//session_start();

	/*
	 *	If intended to use with a common MVC distribution,
	 *  you must define MVC_INSTANCE_DIR with the absolute path
	 *  where to search for the controllers, config, views and plugins
	 *
	 *	DEFINE('MVC_INSTANCE_DIR', dirname(__DIR__));
	 *
	 */
	
	require('../bootstrap.php');


	Bootstrap::start();
