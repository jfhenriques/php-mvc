<?php

	// If sessions are going to be used, uncoment the next line
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
