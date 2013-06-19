<?php


	$GLOBALS['routes'] = array(
		
		
		/*****************************************************************************************
		 *	Namespace and resources
		 *	Namespaces can be directly nested
		 *****************************************************************************************/
		 
		'namespace' => array(
			
			array( 'name' => 'api',		// Must not contain slashed nor spaces
				   
				   'resources' => array(
					
						':session' => array( 'only' => array( 'create', 'destroy' ) ),
						
						':user' => array( 'only' => array( 'create', 'index' ) ),						
					),
			),

		),
		
		
		/*****************************************************************************************
		 *	Which uri will be the root
		 *	This will make a call to the uri, by a GET action
		 *****************************************************************************************/
		
		'root' => 'home',
			
		
		
		/*****************************************************************************************
		 *	Aqui são definidos todos a caminhos que sejam necessários,
		 *	e que não respeitem se identifiquem com a lógica dos controladores.
		 *	Pode ser feito o match de caminhos e automaticamente definir variáveis,
		 *	p.ex: ao fazer match disto /qlqrcoisa/home/01-01-1988 nisto "/qlqrcoisa/:page/:data"
		 *	instanciará as variáves page e data com o respectivos valores
		 *****************************************************************************************/
		 
		'matches' => array(

			array( 'match' => '/api',
				   'matches' => array(

				   		array( 'match' => '/test', 'controller' => 'test', 'via' => 'get', 'action' => 'test' ),
				   	),
			),
			
			array( 'match' => '/reset_password/:reset_token', 'controller' => 'user', 'via' => 'get', 'action' => 'reset_password_confirmation' ),
			
		),
	
	);

